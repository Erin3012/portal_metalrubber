<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/sso.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'method_not_allowed']); exit; }
$clientId = (string)($_POST['client_id'] ?? ''); $secret = (string)($_POST['client_secret'] ?? ''); $code = (string)($_POST['code'] ?? '');
$client = sso_client_config($clientId);
if (!$client || $client['secret_value'] === '' || !hash_equals($client['secret_value'], $secret) || !preg_match('/^[A-Za-z0-9_-]{40,64}$/', $code)) { http_response_code(401); echo json_encode(['error' => 'invalid_client_or_code']); exit; }
$pdo = db();
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT s.id,u.id user_id,u.name,u.email,u.active,r.slug role_slug FROM sso_codes s JOIN users u ON u.id=s.user_id JOIN roles r ON r.id=u.role_id WHERE s.code_hash=? AND s.client_id=? AND s.used_at IS NULL AND s.expires_at>NOW() FOR UPDATE');
    $stmt->execute([hash('sha256', $code), $clientId]); $identity = $stmt->fetch();
    if (!$identity || !(int)$identity['active']) { $pdo->rollBack(); http_response_code(401); echo json_encode(['error' => 'invalid_or_expired_code']); exit; }
    $pdo->prepare('UPDATE sso_codes SET used_at=NOW() WHERE id=?')->execute([(int)$identity['id']]);
    $pdo->commit(); audit('sso_exchanged', (int)$identity['user_id'], $clientId);
    echo json_encode(['user_id' => (int)$identity['user_id'], 'name' => $identity['name'], 'email' => $identity['email'], 'role' => $identity['role_slug']], JSON_UNESCAPED_UNICODE); exit;
} catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); error_log($e->getMessage()); http_response_code(500); echo json_encode(['error' => 'sso_unavailable']); exit; }
