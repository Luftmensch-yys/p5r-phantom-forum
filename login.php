<?php
declare(strict_types=1);

include 'auth.php';
include 'conn.php';

if (!empty($_SESSION['user_id'])) {
    redirect('loading.php');
}

$error = '';
$email = '';

if (is_post_request()) {
    $email = post_string('email');
    $password = post_string('password');

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id, username, email, password, avatar FROM users WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result) ?: null;
        mysqli_stmt_close($stmt);

        if ($user === null) {
            redirect('register.php?email=' . urlencode($email));
        }

        if (!password_verify($password, (string)$user['password'])) {
            $error = 'Invalid email or password.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = (string)$user['username'];
            $_SESSION['email'] = (string)$user['email'];
            $_SESSION['avatar'] = (string)($user['avatar'] ?? '');
            redirect('loading.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-size: cover;
            background-position: center;
            margin: 0;
            overflow: hidden;
            background-image: url(img/11-3.png), url('img/8.jpg');
        }

        .login-box {
            width: 90%;
            max-width: 650px;
            min-height: 380px;
            background-image: url(img/login.png);
            background-size: cover;
            background-position: center;
            padding: 100px 50px 20px 70px;
            border-radius: 5px;
            position: relative;
            z-index: 20;
            box-sizing: border-box;
        }

        label {
            display: block;
            margin: 18px auto 4px auto;
            color: white;
            font-weight: bold;
            font-size: 15px;
        }

        input[type="text"],
        input[type="password"] {
            width: 85%;
            padding: 9px;
            margin: 0 auto auto 25px;
            box-sizing: border-box;
            background-color: transparent;
            border: none;
            border-bottom: 2px solid #ffffff;
            outline: none;
            color: #fff;
            font-size: 15px;
        }

        input[type="submit"] {
            margin: 14px auto auto 25px;
            padding: 10px 22px;
            background-color: #D92323;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 45%;
            font-size: 15px;
        }

        input[type="submit"]:hover {
            background-color: #c32020;
        }

        input[type="reset"] {
            margin-top: 6px;
            padding: 10px 22px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 45%;
            font-size: 15px;
        }

        a {
            text-decoration: none;
            display: block;
            margin: 10px auto auto -25px;
            text-align: center;
            color: white;
            font-size: 14px;
        }

        .error {
            color: #ffd6d6;
            margin: 8px 0 0 25px;
            font-size: 13px;
        }

        /* ── 手机端适配 ───────────────────── */
        @media (max-width: 600px) {
            .login-box {
                width: 94%;
                max-width: none;
                padding: 80px 28px 16px 30px;
                min-height: 320px;
                border-radius: 3px;
            }

            label { font-size: 14px; }

            input[type="text"],
            input[type="password"],
            input[type="submit"],
            input[type="reset"] {
                margin-left: 12px;
                width: 88%;
            }

            input[type="text"],
            input[type="password"] {
                font-size: 15px;
                padding: 10px 4px;
            }

            a { margin-left: -12px; }
            .error { margin-left: 12px; }
        }
    </style>
</head>
<body>
<div class="login-box">
    <form action="" method="post">
        <label for="email">Email:</label>
        <input type="text" id="email" name="email" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
        <br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <br>
        <input type="submit" value="Login">
        <input type="reset" value="Reset">
    </form>
    <?php if ($error !== ''): ?>
        <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <a href="register.php">Don't have an account? Register here</a>
</div>
</body>
</html>
