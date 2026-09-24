<?php
require_once __DIR__ . '/../app/auth.php';
if (current_user()) redirect('dashboard.php');
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = (string)($_POST['email'] ?? '');
    try {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !login_user($email, (string)($_POST['password'] ?? ''))) $error = 'El correo o la contraseña no son correctos.';
        else redirect('dashboard.php');
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
        $error = 'El servicio no está disponible en este momento. Intenta nuevamente más tarde.';
    }
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Ingresar | <?= e(app_name()) ?></title><link rel="stylesheet" href="<?= e(app_url('assets/app.css')) ?>"></head><body><main class="auth-page"><section class="auth-card"><div class="auth-brand"><img src="<?= e(app_url('assets/logo-metalrubber.png')) ?>" alt="Metalrubber"></div><p class="eyebrow">Acceso central</p><h1>Portal de acceso</h1><p class="intro">Una sola puerta para los módulos autorizados de Metalrubber.</p><?= alert_html($error) ?><form method="post" novalidate><?= csrf_field() ?><label class="field"><span>Correo electrónico</span><input type="email" name="email" autocomplete="username" required autofocus></label><label class="field"><span>Contraseña</span><input type="password" name="password" autocomplete="current-password" required></label><button type="submit">Ingresar al portal</button></form><div class="auth-footer"><a href="https://metalrubber.cl">Volver al sitio corporativo</a><span class="muted">Acceso protegido</span></div></section></main></body></html>
