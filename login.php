<?php
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect(is_admin() ? 'admin/index.php' : 'user/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conexion->prepare('SELECT id, name, email, password, role, avatar, status FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && $user['status'] === 'active' && password_verify($password, $user['password'])) {
        unset($user['password']);
        $_SESSION['user'] = $user;
        $conexion->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);
        redirect($user['role'] === 'admin' ? 'admin/index.php' : 'user/dashboard.php');
    }

    set_flash('Correo o contrasena incorrectos.', 'danger');
}

$pageTitle = 'Iniciar sesion';
require __DIR__ . '/includes/header.php';
?>

<section class="auth-screen">
    <form class="auth-card" method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <p class="eyebrow">Cuenta DarkNovels</p>
        <h1>Iniciar sesion</h1>
        <label class="form-label">Correo</label>
        <input class="form-control" type="email" name="email" required autocomplete="off" autocapitalize="none" spellcheck="false">
        <label class="form-label mt-3">Contrasena</label>
        <input class="form-control" type="password" name="password" required autocomplete="new-password">
        <button class="btn btn-danger w-100 mt-4" type="submit">Entrar</button>
        <p class="mt-3 mb-0"><a href="<?= url('index.php') ?>">Volver al sitio publico</a></p>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
