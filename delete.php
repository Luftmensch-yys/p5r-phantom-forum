<?php
declare(strict_types=1);

include 'auth.php';
include 'conn.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_post_request()) {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'method not allowed']);
    exit;
}

$uid = require_login();
$id = post_int('id');

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'invalid id']);
    exit;
}

$is_admin = false;
$adminStmt = mysqli_prepare($conn, 'SELECT id FROM admins WHERE user_id = ? LIMIT 1');
mysqli_stmt_bind_param($adminStmt, 'i', $uid);
mysqli_stmt_execute($adminStmt);
$adminResult = mysqli_stmt_get_result($adminStmt);
$is_admin = mysqli_fetch_assoc($adminResult) !== null;
mysqli_stmt_close($adminStmt);

function delete_message_recursive(mysqli $conn, int $messageId)
{
    $childStmt = mysqli_prepare($conn, 'SELECT id FROM messages WHERE reply_id = ?');
    mysqli_stmt_bind_param($childStmt, 'i', $messageId);
    mysqli_stmt_execute($childStmt);
    $childResult = mysqli_stmt_get_result($childStmt);

    while ($row = mysqli_fetch_assoc($childResult)) {
        delete_message_recursive($conn, (int)$row['id']);
    }

    mysqli_stmt_close($childStmt);

    $deleteStmt = mysqli_prepare($conn, 'DELETE FROM messages WHERE id = ?');
    mysqli_stmt_bind_param($deleteStmt, 'i', $messageId);
    mysqli_stmt_execute($deleteStmt);
    mysqli_stmt_close($deleteStmt);
}

if (!$is_admin) {
    $ownerStmt = mysqli_prepare($conn, 'SELECT id FROM messages WHERE id = ? AND uid = ? LIMIT 1');
    mysqli_stmt_bind_param($ownerStmt, 'ii', $id, $uid);
    mysqli_stmt_execute($ownerStmt);
    $ownerResult = mysqli_stmt_get_result($ownerStmt);
    $allowed = mysqli_fetch_assoc($ownerResult) !== null;
    mysqli_stmt_close($ownerStmt);

    if (!$allowed) {
        echo json_encode(['success' => false, 'error' => 'not authorized']);
        exit;
    }
}

delete_message_recursive($conn, $id);
echo json_encode(['success' => true]);
?>
