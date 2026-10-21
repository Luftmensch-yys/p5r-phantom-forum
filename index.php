<?php
declare(strict_types=1);

include 'auth.php';
include 'conn.php';

$uid = require_login();
$messageError = '';
$voteOptions = ['存在', '不存在'];

$userStmt = mysqli_prepare($conn, 'SELECT username, avatar FROM users WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($userStmt, 'i', $uid);
mysqli_stmt_execute($userStmt);
$userResult = mysqli_stmt_get_result($userStmt);
$userInfo = mysqli_fetch_assoc($userResult) ?: [];
mysqli_stmt_close($userStmt);

$username = (string)($userInfo['username'] ?? ($_SESSION['username'] ?? ''));
$userAvatar = !empty($userInfo['avatar']) ? 'uploadPic/' . $userInfo['avatar'] : 'uploadPic/joker.png';
$_SESSION['username'] = $username;
$_SESSION['avatar'] = (string)($userInfo['avatar'] ?? '');

$adminStmt = mysqli_prepare($conn, 'SELECT id FROM admins WHERE user_id = ? LIMIT 1');
mysqli_stmt_bind_param($adminStmt, 'i', $uid);
mysqli_stmt_execute($adminStmt);
$adminResult = mysqli_stmt_get_result($adminStmt);
$is_admin = mysqli_fetch_assoc($adminResult) !== null;
mysqli_stmt_close($adminStmt);

if (is_post_request()) {
    $action = post_string('action');

    if ($action === 'message') {
        // CSRF 校验（AJAX 请求也校验）
        if (!csrf_verify()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => '安全验证失败，请刷新页面重试。']);
            exit;
        }

        $content = post_string('content');
        $replyId = post_int('reply_id');

        if ($content === '') {
            $messageError = '请输入消息内容';
        } else {
            if ($replyId > 0) {
                $replyStmt = mysqli_prepare($conn, 'SELECT id FROM messages WHERE id = ? LIMIT 1');
                mysqli_stmt_bind_param($replyStmt, 'i', $replyId);
                mysqli_stmt_execute($replyStmt);
                $replyResult = mysqli_stmt_get_result($replyStmt);
                $replyExists = mysqli_fetch_assoc($replyResult) !== null;
                mysqli_stmt_close($replyStmt);

                if (!$replyExists) {
                    $replyId = 0;
                }
            }

            $messageStmt = mysqli_prepare($conn, 'INSERT INTO messages (uid, username, content, reply_id) VALUES (?, ?, ?, ?)');
            mysqli_stmt_bind_param($messageStmt, 'issi', $uid, $username, $content, $replyId);
            mysqli_stmt_execute($messageStmt);
            mysqli_stmt_close($messageStmt);

            if (is_ajax_request()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true]);
                exit;
            }

            redirect('index.php');
        }

        if (is_ajax_request()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => $messageError]);
            exit;
        }
    }

    if ($action === 'vote') {
        // 投票也加 CSRF 校验
        if (!csrf_verify()) {
            // 非 AJAX 表单提交，直接跳转并提示
            $messageError = '安全验证失败，请刷新页面后重新投票。';
        } else {
            $voteOption = post_string('vote');

            if (in_array($voteOption, $voteOptions, true)) {
                $voteStmt = mysqli_prepare($conn, 'INSERT INTO votes (user_id, vote_option) VALUES (?, ?) ON DUPLICATE KEY UPDATE vote_option = VALUES(vote_option)');
                mysqli_stmt_bind_param($voteStmt, 'is', $uid, $voteOption);
                mysqli_stmt_execute($voteStmt);
                mysqli_stmt_close($voteStmt);
            }
        }

        redirect('index.php');
    }
}

$currentVoteStmt = mysqli_prepare($conn, 'SELECT vote_option FROM votes WHERE user_id = ? LIMIT 1');
mysqli_stmt_bind_param($currentVoteStmt, 'i', $uid);
mysqli_stmt_execute($currentVoteStmt);
$currentVoteResult = mysqli_stmt_get_result($currentVoteStmt);
$userVote = mysqli_fetch_assoc($currentVoteResult)['vote_option'] ?? null;
mysqli_stmt_close($currentVoteStmt);

$voteCountStmt = mysqli_prepare($conn, 'SELECT vote_option, COUNT(*) AS total FROM votes GROUP BY vote_option');
mysqli_stmt_execute($voteCountStmt);
$voteCountResult = mysqli_stmt_get_result($voteCountStmt);

$totalVotes = 0;
$existVotes = 0;
$notExistVotes = 0;

while ($row = mysqli_fetch_assoc($voteCountResult)) {
    $count = (int)$row['total'];
    $totalVotes += $count;

    if ($row['vote_option'] === '存在') {
        $existVotes = $count;
    }

    if ($row['vote_option'] === '不存在') {
        $notExistVotes = $count;
    }
}
mysqli_stmt_close($voteCountStmt);

$existPercent = $totalVotes > 0 ? (int)round(($existVotes / $totalVotes) * 100) : 0;
$notExistPercent = $totalVotes > 0 ? (int)round(($notExistVotes / $totalVotes) * 100) : 0;

$messagesResult = mysqli_query(
    $conn,
    'SELECT m.*, u.avatar, u.username AS latest_username
     FROM messages m
     LEFT JOIN users u ON m.uid = u.id
     ORDER BY m.create_time DESC, m.id DESC'
);

$messages = [];
while ($row = mysqli_fetch_assoc($messagesResult)) {
    $messages[] = $row;
}

$msgMap = [];
foreach ($messages as $item) {
    $msgMap[(int)$item['id']] = $item;
}

$group = [];
foreach ($messages as $message) {
    $rootId = (int)$message['id'];
    $temp = $message;

    while ((int)$temp['reply_id'] > 0 && isset($msgMap[(int)$temp['reply_id']])) {
        $temp = $msgMap[(int)$temp['reply_id']];
        $rootId = (int)$temp['id'];
    }

    if (!isset($group[$rootId])) {
        $group[$rootId] = [];
    }

    $group[$rootId][] = $message;
}

$finalGroups = [];
foreach ($messages as $message) {
    $messageId = (int)$message['id'];
    if ((int)$message['reply_id'] === 0 && isset($group[$messageId])) {
        usort($group[$messageId], static function (array $a, array $b): int {
            return strtotime((string)$a['create_time']) <=> strtotime((string)$b['create_time']);
        });
        $finalGroups[] = $group[$messageId];
        unset($group[$messageId]);
    }
}

foreach ($group as $remaining) {
    usort($remaining, static function (array $a, array $b): int {
        return strtotime((string)$a['create_time']) <=> strtotime((string)$b['create_time']);
    });
    $finalGroups[] = $remaining;
}

$group = $finalGroups;
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TALK</title>

    <!-- 外部样式表 -->
    <link rel="stylesheet" href="css/style.css">

    <!-- p5.js 星空背景 -->
    <script src="js/p5.min.js"></script>
    <script src="js/stars.js"></script>
</head>
<body>
    <div id="container">
        <button class="menu-toggle" id="menuToggle" aria-label="打开菜单">&#9776;</button>
        <div class="sidebar collapsed">
            <img src="<?= htmlspecialchars($userAvatar, ENT_QUOTES, 'UTF-8') ?>?t=<?= time() ?>" alt="pic">
            <h3><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></h3>
            <a href="loading_mypage.php">My page</a>
            <a href="logout.php">Log out</a>
        </div>

        <div class="content">
            <!-- ═══ 投票区 ═══ -->
            <div class="vote-section">
                <div class="vote-question">
                    <img src="img/Q.png" alt="投票题目">
                </div>

                <div class="vote-status">
                    已有 <?= $totalVotes ?> 人参与投票
                    <?php if ($userVote): ?>
                        <br><small style="color:#ffda07;">你当前选择的是：<?= htmlspecialchars((string)$userVote, ENT_QUOTES, 'UTF-8') ?></small>
                    <?php endif; ?>
                </div>

                <div class="vote-progress">
                    <div class="vote-progress-inner">
                        <div class="vote-bar-exist" style="width: <?= $existPercent ?>%;"></div>
                        <div class="vote-bar-not-exist" style="width: <?= $notExistPercent ?>%;"></div>
                    </div>
                    <div class="vote-percentages">
                        <?= $existPercent ?>%
                    </div>
                </div>

                <form method="post" style="display:contents;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="vote">
                    <input type="hidden" name="vote" id="vote_input">
                    <div class="vote-options">
                        <div class="vote-option" onclick="submitVote('存在')">
                            <img src="img/vote-yes.png" alt="存在">
                        </div>
                        <div class="vote-option" onclick="submitVote('不存在')">
                            <img src="img/vote-no.png" alt="不存在">
                        </div>
                    </div>
                </form>
            </div>

            <?php if ($messageError !== '' || !empty($_GET['csrf_error'])): ?>
                <div class="error-msg"><?= htmlspecialchars($messageError ?: '安全验证失败，请刷新页面重试。', ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <!-- ═══ 聊天区 ═══ -->
            <div class="chat-box" id="chatArea">
                <?php foreach ($group as $thread): ?>
                    <?php foreach ($thread as $message): ?>
                        <?php
                        $baseClass = (int)$message['reply_id'] === 0 ? 'msg-main' : 'msg-reply';
                        $variantClass = ((int)$message['reply_id'] === 0) ? ('b' . mt_rand(0, 1)) : '';
                        ?>
                        <div class="<?= $baseClass ?> <?= $variantClass ?>" id="msg-<?= (int)$message['id'] ?>">
                            <img class="msg-avatar"
                                 src="<?= htmlspecialchars(!empty($message['avatar']) ? 'uploadPic/' . $message['avatar'] : 'uploadPic/joker.png', ENT_QUOTES, 'UTF-8') ?>?t=<?= time() ?>"
                                 alt="avatar">
                            <div class="msg-content">
                                <span class="user"><?= htmlspecialchars((string)($message['latest_username'] ?: $message['username']), ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="time"><?= htmlspecialchars((string)$message['create_time'], ENT_QUOTES, 'UTF-8') ?></span>

                                <?php if ((int)$message['reply_id'] > 0 && isset($msgMap[(int)$message['reply_id']])): ?>
                                    <?php $target = $msgMap[(int)$message['reply_id']]; ?>
                                    <div class="reply-info">
                                        回复 @<?= htmlspecialchars((string)$target['username'], ENT_QUOTES, 'UTF-8') ?>：
                                        <?= htmlspecialchars(mb_substr((string)$target['content'], 0, 28), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>

                                <p style="margin:5px 0;"><?= htmlspecialchars((string)$message['content'], ENT_QUOTES, 'UTF-8') ?></p>

                                <div style="margin-top:6px;">
                                    <a href="javascript:void(0)"
                                       class="reply-btn"
                                       data-reply-id="<?= (int)$message['id'] ?>"
                                       data-username="<?= htmlspecialchars(json_encode((string)$message['username'], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS), ENT_QUOTES, 'UTF-8') ?>">回复</a>
                                    <?php if ($is_admin || (int)$message['uid'] === $uid): ?>
                                        <a href="javascript:void(0)"
                                           class="delete-btn"
                                           data-delete-id="<?= (int)$message['id'] ?>">删除</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>

            <!-- ═══ 回复提示 + 发送表单 ═══ -->
            <div class="reply-tip" id="reply_tip">
                正在回复 <span id="reply_user"></span>
                <span class="reply-cancel">取消</span>
            </div>

            <form method="post" class="send-form hidden" id="sendForm">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="message">
                <input type="hidden" name="reply_id" id="reply_id" value="0">
                <input class="send-input" type="text" name="content" id="input_content" placeholder="输入消息..." autocomplete="off" required>
                <button class="send-btn" type="submit">发送</button>
            </form>

            <button class="back-to-top" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">^</button>
        </div>
    </div>

    <!-- 外部交互脚本（事件委托读取 data-* 属性） -->
    <script src="js/app.js"></script>
</body>
</html>
