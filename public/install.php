<?php
require_once __DIR__ . '/../app/bootstrap.php';
$error = null; $complete = false;
try { $complete = (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0; } catch (Throwable $e) { $error = 'La base de datos aún no está preparada. Importa database/schema.sql y vuelve a intentar.'; }
if (!$complete && !$error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $installKey = env_value('INSTALL_KEY');
    if ($installKey === '' || !hash_equals($installKey, (string)($_POST['install_key'] ?? ''))) $error = 'La clave de instalación no es válida.';
    $name = trim((string)($_POST['name'] ?? '')); $email = strtolower(trim((string)($_POST['email'] ?? ''))); $password = (string)($_POST['password'] ?? '');
    if (!$error && ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10)) $error = 'Completa los datos. La contraseña debe tener al menos 10 caracteres.';
    if (!$error) try {
        $pdo = db(); $pdo->beginTransaction();
        $role = $pdo->query("SELECT id FROM roles WHERE slug='admin'")->fetchColumn();
        if (!$role) throw new RuntimeException('No se encontró el rol administrador.');
        $stmt = $pdo->prepare('INSERT INTO users(role_id,name,email,password_hash) VALUES(?,?,?,?)');
        $stmt->execute([(int)$role, $name, $email, password_hash($password, PASSWORD_DEFAULT)]); $id = (int)$pdo->lastInsertId();
        $pdo->commit(); audit('first_admin_created', $id); $complete = true; $message = 'Administrador creado correctamente. Ya puedes iniciar sesión.';
    } catch (Throwable $e) { if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack(); $error = $e->getCode() === '23000' ? 'Ese correo ya está registrado.' : 'No fue posible crear el administrador.'; }
}
if ($complete && !isset($message)) $message = 'La instalación ya fue completada. El instalador permanece bloqueado.';
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalación | <?= e(app_name()) ?></title><link rel="stylesheet" href="<?= e(app_url('assets/app.css')) ?>"></head><body><main class="auth-page"><section class="auth-card"><div class="auth-brand"><img src="<?= e(app_url('assets/logo-metalrubber.png')) ?>" alt="Metalrubber"></div><p class="eyebrow">Configuración inicial</p><h1>Crear administrador</h1><p class="intro">Este paso solo está disponible antes de crear el primer usuario.</p><?= alert_html($error) ?><?= alert_html($message ?? null, 'success') ?><?php if (!$complete && !$error): ?><form method="post"><?= csrf_field() ?><label class="field"><span>Clave de instalación</span><input type="password" name="install_key" required autocomplete="off"></label><label class="field"><span>Nombre completo</span><input name="name" required autocomplete="name"></label><label class="field"><span>Correo electrónico</span><input type="email" name="email" required autocomplete="email"></label><label class="field"><span>Contraseña</span><input type="password" name="password" minlength="10" required autocomplete="new-password"><small class="form-note">Usa al menos 10 caracteres.</small></label><button type="submit">Crear administrador</button></form><?php endif; ?><div class="auth-footer"><a href="<?= e(app_url('login.php')) ?>">Ir al inicio de sesión</a><span class="muted">Instalación segura</span></div></section></main></body></html>
