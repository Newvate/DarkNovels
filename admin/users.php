<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) $_POST['id'];
    if ($id !== (int) current_user()['id']) {
        $conexion->prepare('UPDATE users SET role = ?, status = ? WHERE id = ?')
            ->execute([$_POST['role'], $_POST['status'], $id]);
        set_flash('Usuario actualizado.');
    }
    redirect('admin/users.php');
}

$users = $conexion->query('SELECT id, name, email, role, status, created_at, last_login_at FROM users ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Usuarios';
require __DIR__ . '/../includes/header.php';
?>

<section class="section-pad admin-page">
    <div class="container">
        <h1>Usuarios</h1>
        <div class="table-responsive data-panel">
            <table class="table table-dark table-hover align-middle">
                <thead><tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th>Ultimo acceso</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= e($user['name']) ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td><?= e($user['role']) ?></td>
                        <td><?= e($user['status']) ?></td>
                        <td><?= e($user['last_login_at'] ?? '-') ?></td>
                        <td>
                            <form method="post" class="d-flex gap-2">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                <select class="form-select form-select-sm" name="role">
                                    <option value="reader" <?= $user['role'] === 'reader' ? 'selected' : '' ?>>Lector</option>
                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="premium_author" <?= $user['role'] === 'premium_author' ? 'selected' : '' ?>>Autor premium</option>
                                </select>
                                <select class="form-select form-select-sm" name="status">
                                    <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Activo</option>
                                    <option value="blocked" <?= $user['status'] === 'blocked' ? 'selected' : '' ?>>Bloqueado</option>
                                </select>
                                <button class="btn btn-sm btn-danger" type="submit">Guardar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
