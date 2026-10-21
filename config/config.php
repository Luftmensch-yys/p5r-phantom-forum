<?php
declare(strict_types=1);

/**
 * p5r 项目配置文件
 *
 * 敏感信息（数据库密码等）请勿提交到版本库。
 * 生产环境建议通过环境变量覆盖，或使用 .env 文件。
 */

return [
    // ── 数据库 ──────────────────────────────
    'db' => [
        'host'     => getenv('DB_HOST')     ?: 'localhost',
        'username' => getenv('DB_USER')     ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',  // ⚠️ 请尽快改为强密码
        'dbname'   => getenv('DB_NAME')     ?: 'p5r',
        'charset'  => 'utf8mb4',
    ],

    // ── 应用 ──────────────────────────────
    'app' => [
        'name'        => 'TALK',
        'base_url'    => '/',              // 部署到子目录时改为 /子目录名/
        'session_name' => 'P5R_SESSION',
        'avatar_default' => 'joker.png',
        'avatar_dir'   => __DIR__ . '/../uploadPic',
        'avatar_url'   => 'uploadPic',
    ],

    // ── 安全 ──────────────────────────────
    // CSRF token 有效期（秒），0 = 跟随 Session
    'csrf_lifetime' => 0,
];
