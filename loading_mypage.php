<?php
declare(strict_types=1);

include 'auth.php';

require_login();
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>Loading...</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #000;
        }
        .loading-container {
            position: relative;
            z-index: 2;
        }
        .logo {
            max-width: 300px;
            max-height: 300px;
            width: auto;
            height: auto;
            animation: flipY 2s ease-in-out 3;
        }
        @keyframes flipY {
            0% {
                transform: perspective(400px) rotateY(0deg);
            }
            50% {
                transform: perspective(400px) rotateY(180deg);
            }
            100% {
                transform: perspective(400px) rotateY(360deg);
            }
        }
        .fade-out {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            z-index: 1;
            animation: fadeOut 3s ease-in-out forwards;
        }
        @keyframes fadeOut {
            0% {
                opacity: 1;
            }
            80% {
                opacity: 1;
            }
            100% {
                opacity: 0;
                pointer-events: none;
            }
        }
    </style>
</head>
<body>
    <div class="fade-out"></div>
    <div class="loading-container">
        <img src="img/logo.png" alt="Logo" class="logo">
    </div>

    <script>
        setTimeout(() => {
            window.location.href = 'Mypage.php';
        }, 2000);
    </script>
</body>
</html>
