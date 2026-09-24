<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/sso.php';
$user = require_login();
$clientId = (string)($_GET['client_id'] ?? '');
$state = (string)($_GET['state'] ?? '');
$client = sso_client_config($clientId);
if (!$client || $client['callback_url'] === '' || !preg_match('/^[a-f0-9]{32,128}$/', $state)) { http_response_code(400); exit('Solicitud SSO no válida.'); }
$allowed = db()->prepare('SELECT 1 FROM role_modules rm JOIN modules m ON m.id=rm.module_id WHERE rm.role_id=? AND m.slug=? AND m.available=1');
$allowed->execute([(int)$user['role_id'], $client['module']]);
if (!$allowed->fetchColumn()) { audit('sso_authorize_denied', (int)$user['id'], $clientId); http_response_code(403); exit('No tienes permisos para este módulo.'); }
$cleanup = db()->prepare('DELETE FROM sso_codes WHERE expires_at < NOW() OR used_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)'); $cleanup->execute();
$rawCode = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
$stmt = db()->prepare('INSERT INTO sso_codes(code_hash,user_id,client_id,state_value,expires_at) VALUES(?,?,?,?,DATE_ADD(NOW(), INTERVAL 60 SECOND))');
$stmt->execute([hash('sha256', $rawCode), (int)$user['id'], $clientId, $state]);
audit('sso_authorized', (int)$user['id'], $clientId);
redirect($client['callback_url'] . '?code=' . rawurlencode($rawCode) . '&state=' . rawurlencode($state));
