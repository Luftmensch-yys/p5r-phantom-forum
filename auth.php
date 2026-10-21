<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    // 安全 Cookie：HttpOnly 防 JS 窃取、SameSite=Lax 防 CSRF、HTTPS 时自动 Secure
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
               || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    session_set_cookie_params(0, '/; SameSite=Lax', '', $isHttps, true);
    session_start();
}

// ── 路由 / 请求辅助 ──────────────────────

function redirect(string $path)
{
    header('Location: ' . $path);
    exit;
}

function require_login(): int
{
    $uid = (int)($_SESSION['user_id'] ?? 0);
    if ($uid <= 0) {
        redirect('login.php');
    }

    return $uid;
}

function is_post_request(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function post_string(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

function post_int(string $key): int
{
    return (int)($_POST[$key] ?? 0);
}

function is_ajax_request(): bool
{
    return strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
}

// ── CSRF 防护 ───────────────────────────

/**
 * 生成或返回当前 CSRF token（存入 Session）。
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

/**
 * 输出隐藏的 <input type="hidden" name="_token" ...> 标签。
 */
function csrf_field()
{
    echo '<input type="hidden" name="_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * 校验 POST 请求中的 _token 是否合法。
 *
 * @return bool
 */
function csrf_verify(): bool
{
    $token = (string)($_POST['_token'] ?? '');

    if ($token === '') {
        return false;
    }

    $stored = (string)($_SESSION['_csrf_token'] ?? '');

    // 使用 hash_equals 防时序攻击
    return hash_equals($stored, $token);
}
