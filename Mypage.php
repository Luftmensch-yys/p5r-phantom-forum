<?php
declare(strict_types=1);

include 'auth.php';
include 'conn.php';

$uid = require_login();
$msg = '';

if (is_post_request()) {
    $action = post_string('action');

    if ($action === 'update_profile') {
        $newName = post_string('username');
        if ($newName === '') {
            $msg = 'Username cannot be empty.';
        } else {
            $stmt = mysqli_prepare($conn, 'UPDATE users SET username = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'si', $newName, $uid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $_SESSION['username'] = $newName;
            setcookie('current_username', $newName, time() + 86400 * 7, '/');
            redirect('Mypage.php');
        }
    }

    if ($action === 'upload_avatar') {
        if (!isset($_FILES['avatar_file']) || $_FILES['avatar_file']['error'] !== UPLOAD_ERR_OK) {
            $msg = 'Please choose a valid image file.';
        } else {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo((string)$_FILES['avatar_file']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExtensions, true)) {
                $msg = 'Only jpg, jpeg, png and gif are allowed.';
            } else {
                // 验证是真实图片（防止伪装扩展名的脚本文件）
                $imageInfo = @getimagesize((string)$_FILES['avatar_file']['tmp_name']);
                if ($imageInfo === false) {
                    $msg = 'Uploaded file is not a valid image.';
                } elseif ((int)$_FILES['avatar_file']['size'] > 2 * 1024 * 1024) { // 限制 2MB
                    $msg = 'Image file too large. Max 2MB.';
                } else {
                    $saveName = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $targetPath = __DIR__ . DIRECTORY_SEPARATOR . 'uploadPic' . DIRECTORY_SEPARATOR . $saveName;

                if (move_uploaded_file((string)$_FILES['avatar_file']['tmp_name'], $targetPath)) {
                    $stmt = mysqli_prepare($conn, 'UPDATE users SET avatar = ? WHERE id = ?');
                    mysqli_stmt_bind_param($stmt, 'si', $saveName, $uid);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    $_SESSION['avatar'] = $saveName;
                    redirect('Mypage.php');
                }

                $msg = 'Avatar upload failed.';
            }
        }
    }
}}

$userStmt = mysqli_prepare($conn, 'SELECT id, username, email, avatar FROM users WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($userStmt, 'i', $uid);
mysqli_stmt_execute($userStmt);
$userResult = mysqli_stmt_get_result($userStmt);
$info = mysqli_fetch_assoc($userResult) ?: [];
mysqli_stmt_close($userStmt);

$myTalk = mysqli_prepare($conn, 'SELECT content, create_time FROM messages WHERE uid = ? ORDER BY create_time DESC');
mysqli_stmt_bind_param($myTalk, 'i', $uid);
mysqli_stmt_execute($myTalk);
$talkResult = mysqli_stmt_get_result($myTalk);
$talkList = mysqli_fetch_all($talkResult, MYSQLI_ASSOC);
mysqli_stmt_close($myTalk);

$avatar = !empty($info['avatar']) ? 'uploadPic/' . $info['avatar'] : 'uploadPic/joker.png';
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人中心</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/p5.js/1.9.0/p5.min.js"></script>
    <script src="js/stars.js"></script>
    <style>
        /* ── 基础 & 画布 ───────────────────── */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html {
            min-height: 100%;
            background: rgb(180, 0, 0);
        }
        html, body {
            min-height: 100vh;
            width: 100%;
            overflow-x: hidden;
        }
        body {
            background: transparent;
            color: #fff;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        canvas,
        canvas.p5Canvas {
            position: fixed;
            inset: 0;
            width: 100% !important;
            height: 100% !important;
            max-width: none;
            display: block;
            z-index: -1;
            pointer-events: none;
        }

        /* ── 页面容器 ───────────────────────── */
        .page-wrap {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            padding: 30px 20px;
            box-sizing: border-box;
        }
        .box {
            max-width: 760px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.3);
            padding: 26px;
            border-radius: 12px;
            position: relative;
        }

        /* ── 头部信息区 ─────────────────────── */
        .head {
            display: flex;
            gap: 24px;
            align-items: center;
            margin-bottom: 24px;
            border-bottom: 1px solid #666;
            padding-bottom: 18px;
            flex-wrap: wrap;
        }
        .head img {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ffda07;
            flex-shrink: 0;
        }
        .info h2 { color: #ffda07; margin-bottom: 6px; font-size: 20px; }
        .info p { color: #ccc; margin: 3px 0; font-size: 14px; }

        /* ── 表单 ───────────────────────────── */
        .form-item { margin: 14px 0; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .form-item label { white-space: nowrap; font-size: 15px; }
        input[type="text"] { padding: 8px 12px; border-radius: 4px; border: none; width: auto; max-width: 220px; }
        button {
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            background: #000;
            color: #fff;
            cursor: pointer;
            font-size: 14px;
        }
        button:hover { background: #222; }

        /* ── 发言列表 ───────────────────────── */
        .talk-title { font-size: 17px; color: #ffda07; margin: 32px 0 14px 0; border-left: 4px solid #ffda07; padding-left: 10px; }
        .talk-item { background: rgba(255, 255, 255, 0.1); padding: 11px; border-radius: 6px; margin-bottom: 8px; word-break: break-word; }
        .time { font-size: 12px; color: #aaa; margin-top: 4px; }

        a { color: #ccc; text-decoration: none; margin-right: 14px; }
        .back { margin-bottom: 18px; display: inline-block; font-size: 15px; }
        .notice { color: #ffd6d6; margin-top: 6px; font-size: 13px; }

        /* ── 手机端适配 ─────────────────────── */
        @media (max-width: 600px) {
            .page-wrap { padding: 16px 10px; }
            .box { padding: 16px 14px; border-radius: 8px; }
            .head { flex-direction: column; text-align: center; gap: 14px; }
            .head img { width: 88px; height: 88px; }
            .info h2 { font-size: 18px; }
            .info p { font-size: 13px; }
            .form-item { flex-direction: column; align-items: stretch; }
            input[type="text"] { max-width: none; width: 100%; box-sizing: border-box; }
            button { width: 100%; padding: 10px; }
            .talk-title { font-size: 15px; margin-top: 24px; }
            .back { font-size: 14px; }
        }
    </style>
</head>
<body>
    <div class="page-wrap">
        <div class="box">
            <a class="back" href="index.php">返回聊天页</a>

            <div class="head">
                <img src="<?= htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') ?>?t=<?= time() ?>" alt="头像">
                <div class="info">
                    <h2><?= htmlspecialchars((string)($info['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>
                    <p>UID: <?= (int)($info['id'] ?? 0) ?></p>
                    <p>Email: <?= htmlspecialchars((string)($info['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>

            <form method="post">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-item">
                    <label>修改昵称：</label>
                    <input type="text" name="username" value="<?= htmlspecialchars((string)($info['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit">保存修改</button>
                </div>
            </form>

            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_avatar">
                <div class="form-item">
                    <label>更换头像：</label>
                    <input type="file" name="avatar_file" accept="image/*">
                    <button type="submit">上传</button>
                    <?php if ($msg !== ''): ?>
                        <span class="notice"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
            </form>

            <div class="talk-title">我的全部发言</div>
            <?php if (empty($talkList)): ?>
                <p style="color:#ccc;">暂时还没有发言记录。</p>
            <?php else: ?>
                <?php foreach ($talkList as $t): ?>
                    <div class="talk-item">
                        <?= htmlspecialchars((string)$t['content'], ENT_QUOTES, 'UTF-8') ?>
                        <div class="time"><?= htmlspecialchars((string)$t['create_time'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
