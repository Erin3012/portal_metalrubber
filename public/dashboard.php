<?php
require_once __DIR__ . '/../app/sso.php';
$user = require_login();
$stmt = db()->prepare("SELECT m.* FROM modules m JOIN role_modules rm ON rm.module_id=m.id WHERE rm.role_id=? ORDER BY m.sort_order");
$stmt->execute([(int)$user['role_id']]);
$allowedSlugs = array_column($stmt->fetchAll(), 'slug');
$all = db()->query('SELECT * FROM modules ORDER BY sort_order')->fetchAll();
layout_start('Panel principal', $user);
?>
<section class="hero"><div><p class="eyebrow">Portal central Metalrubber</p><h1>Todo listo para trabajar</h1><p>Accede a las herramientas que corresponden a tu rol.</p></div><div class="user-chip"><strong><?= e($user['name']) ?></strong><span><?= e($user['role_name']) ?> · <?= e($user['email']) ?></span></div></section>
<p class="section-label">Módulos disponibles</p>
<section class="module-grid" aria-label="Módulos del portal">
<?php foreach ($all as $module): $allowed = in_array($module['slug'], $allowedSlugs, true); $available = (bool)$module['available']; ?>
<a class="module-card <?= $allowed && $available ? 'available' : 'locked' ?> <?= $module['slug'] === 'maintenance' ? 'accent' : '' ?>" <?= $allowed && $available ? 'href="' . e(sso_module_start_url($module['slug'])) . '"' : 'aria-disabled="true"' ?>><span class="module-mark"><?= e(str_pad((string)$module['sort_order'], 2, '0', STR_PAD_LEFT)) ?></span><h3><?= e($module['name']) ?></h3><p><?= e($module['description']) ?></p><span class="module-action"><?= !$allowed ? 'No autorizado' : (!$available ? 'Próximamente' : 'Abrir módulo →') ?></span></a>
<?php endforeach; ?>
</section>
<?php if (($user['role_slug'] ?? '') === 'admin'): ?><a class="admin-link" href="<?= e(app_url('admin/users.php')) ?>">Administrar usuarios →</a><?php endif; ?>
<?php layout_end();
