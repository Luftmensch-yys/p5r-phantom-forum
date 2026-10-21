<?php
declare(strict_types=1);

$config = require __DIR__ . '/config/config.php';
$db = $config['db'];

$conn = mysqli_connect($db['host'], $db['username'], $db['password'], $db['dbname']);

if (!$conn) {
    // 生产环境不暴露连接细节，仅记录到错误日志
    error_log('Database connection failed');
    die('系统繁忙，请稍后再试。');
}

mysqli_set_charset($conn, $db['charset']);
