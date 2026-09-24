<?php
require_once __DIR__ . '/../../app/bootstrap.php';
$admin = require_admin(); if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin/users.php'); verify_csrf();
$id = (int)($_POST['id'] ?? 0); $name = trim((string)($_POST['name'] ?? '')); $email = strtolower(trim((string)($_POST['email'] ?? ''))); $roleId = (int)($_POST['role_id'] ?? 0); $active = (int)($_POST['active'] ?? 0) === 1 ? 1 : 0; $password = (string)($_POST['password'] ?? '');
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $roleId < 1 || (!$id && strlen($password) < 10) || ($password !== '' && strlen($password) < 10)) { flash('error', 'Completa todos los datos correctamente. La contraseña debe tener al menos 10 caracteres.'); redirect('admin/user-form.php' . ($id ? '?id=' . $id : '')); }
if ($id === (int)$admin['id']) $active = 1;
try {
    $pdo = db(); if ($id) { $sql = 'UPDATE users SET name=?, email=?, role_id=?, active=?'; $values = [$name, $email, $roleId, $active]; if ($password !== '') { $sql .= ', password_hash=?'; $values[] = password_hash($password, PASSWORD_DEFAULT); } $sql .= ' WHERE id=?'; $values[] = $id; $pdo->prepare($sql)->execute($values); audit('user_updated', (int)$admin['id'], 'user_id=' . $id); } else { $stmt = $pdo->prepare('INSERT INTO users(role_id,name,email,password_hash,active) VALUES(?,?,?,?,?)'); $stmt->execute([$roleId, $name, $email, password_hash($password, PASSWORD_DEFAULT), $active]); audit('user_created', (int)$admin['id'], 'user_id=' . $pdo->lastInsertId()); }
    flash('notice', 'Usuario guardado correctamente.');
} catch (Throwable $e) { flash('error', $e->getCode() === '23000' ? 'Ese correo ya está registrado.' : 'No fue posible guardar el usuario.'); }
redirect('admin/users.php');
