<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/conn.php';

header('Content-Type: text/html; charset=utf-8');

$sqlList = [
    'users' => "
        CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            avatar VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'admins' => "
        CREATE TABLE IF NOT EXISTS admins (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_admin_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'messages' => "
        CREATE TABLE IF NOT EXISTS messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            uid INT UNSIGNED NOT NULL,
            username VARCHAR(50) NOT NULL,
            content TEXT NOT NULL,
            reply_id INT UNSIGNED NOT NULL DEFAULT 0,
            create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_messages_uid (uid),
            KEY idx_messages_reply_id (reply_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'votes' => "
        CREATE TABLE IF NOT EXISTS votes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            vote_option ENUM('存在', '不存在') NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_vote (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
];

echo '<h2>p5r 数据库初始化结果</h2>';

foreach ($sqlList as $tableName => $sql) {
    if (mysqli_query($conn, $sql)) {
        echo '<p>表 ' . htmlspecialchars($tableName, ENT_QUOTES, 'UTF-8') . ' 创建成功或已存在。</p>';
    } else {
        echo '<p>表 ' . htmlspecialchars($tableName, ENT_QUOTES, 'UTF-8') . ' 创建失败：' . htmlspecialchars(mysqli_error($conn), ENT_QUOTES, 'UTF-8') . '</p>';
    }
}

mysqli_close($conn);
?>
