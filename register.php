<?php
declare(strict_types=1);

include 'auth.php';
include 'conn.php';

$prefill_email = '';
$username = '';
$error = '';
$success = '';

if (isset($_GET['email']) && is_string($_GET['email'])) {
    $prefill_email = trim($_GET['email']);
}

if (is_post_request()) {
    $username = post_string('username');
    $password = post_string('password');
    $confirm_password = post_string('confirm_password');
    $email = post_string('email');
    $prefill_email = $email;

    if ($username === '' || $password === '' || $confirm_password === '' || $email === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $check = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($check, 's', $email);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);
        $exists = mysqli_fetch_assoc($result) !== null;
        mysqli_stmt_close($check);

        if ($exists) {
            $error = 'Email already exists.';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, 'INSERT INTO users (username, password, email) VALUES (?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'sss', $username, $password_hash, $email);

            if (mysqli_stmt_execute($stmt)) {
                $success = 'Registration successful. Please login.';
                $username = '';
                $prefill_email = '';
            } else {
                $error = 'Registration failed. Please try again.';
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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

        .register-box {
            width: 90%;
            max-width: 650px;
            min-height: 400px;
            background-image: url(img/register.png);
            background-size: cover;
            background-position: center;
            padding: 90px 50px 20px 70px;
            border-radius: 5px;
            position: relative;
            z-index: 20;
            box-sizing: border-box;
        }

        label {
            display: block;
            margin: 6px auto 4px 10px;
            color: white;
            font-weight: bold;
            font-size: 15px;
        }

        input[type="text"],
        input[type="password"] {
            width: 80%;
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
            width: 42%;
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
            width: 42%;
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

        .notice {
            margin: 8px 0 0 25px;
            font-size: 13px;
        }

        .notice.error { color: #ffd6d6; }
        .notice.success { color: #d9ffd9; }

        /* ── 手机端适配 ───────────────────── */
        @media (max-width: 600px) {
            .register-box {
                width: 94%;
                max-width: none;
                padding: 72px 26px 16px 30px;
                min-height: 360px;
                border-radius: 3px;
            }

            label { font-size: 14px; margin-left: 6px; }

            input[type="text"],
            input[type="password"],
            input[type="submit"],
            input[type="reset"] {
                margin-left: 12px;
            }

            input[type="text"],
            input[type="password"] {
                width: 88%;
                font-size: 15px;
                padding: 9px 4px;
            }

            a { margin-left: -12px; }
            .notice { margin-left: 12px; }
        }
    </style>
</head>
<body>
<div class="register-box">
    <form action="" method="post">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>">
        <br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <br>
        <label for="confirm_password">Confirm Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
        <br>
        <label for="email">Email:</label>
        <input type="text" id="email" name="email" required value="<?= htmlspecialchars($prefill_email, ENT_QUOTES, 'UTF-8') ?>">
        <br>
        <input type="submit" value="Register">
        <input type="reset" value="Reset">
    </form>
    <?php if ($error !== ''): ?>
        <div class="notice error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="notice success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <a href="login.php">Already have an account? Login here</a>
</div>
</body>
</html>
