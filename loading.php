<?php
declare(strict_types=1);

include 'auth.php';

require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loading</title>
    <style>
        body { margin: 0; overflow: hidden; background: black; }
        video { width: 100vw; height: 100vh; object-fit: cover; }
    </style>
</head>
<body>
    <video id="loading-video" autoplay muted>
        <source src="video/loading.mp4" type="video/mp4">
        Your browser does not support video playback.
    </video>
    <script>
        const video = document.getElementById('loading-video');
        video.addEventListener('ended', () => {
            window.location.href = 'index.php';
        });
    </script>
</body>
</html>
