<?php
declare(strict_types=1);

// Prueba estática mínima ejecutable con: php tests/smoke.php
$root = dirname(__DIR__);
$required = ['app/bootstrap.php', 'app/auth.php', 'public/index.php', 'public/login.php', 'public/install.php', 'public/dashboard.php', 'public/logout.php', 'public/admin/users.php', 'public/admin/user-form.php', 'public/admin/user-save.php', 'database/schema.sql'];
foreach ($required as $file) {
    if (!is_file($root . '/' . $file)) throw new RuntimeException('Falta: ' . $file);
}
echo "Estructura del portal: OK\n";
