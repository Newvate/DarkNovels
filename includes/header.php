<?php
$pageTitle = $pageTitle ?? APP_NAME;
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= APP_NAME ?></title>
    <meta name="theme-color" content="#090909">
    <link rel="manifest" href="<?= url('manifest.json') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark fixed-top dn-navbar">
    <div class="container">
        <a class="navbar-brand logo" href="<?= url('index.php') ?>">DarkNovels</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-label="Abrir menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link" href="<?= url('index.php') ?>">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('pages/library.php') ?>">Biblioteca</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('pages/videos.php') ?>">Videos</a></li>
                <?php if (!is_admin()): ?>
                    <li class="nav-item ms-lg-2"><a class="btn btn-danger btn-sm px-3" href="<?= url('login.php') ?>"><i class="bi bi-shield-lock"></i> Administrador</a></li>
                <?php endif; ?>
                <?php if (is_admin()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('admin/index.php') ?>">Admin</a></li>
                    <li class="nav-item"><a class="btn btn-outline-light btn-sm" href="<?= url('logout.php') ?>"><i class="bi bi-box-arrow-right"></i> Salir</a></li>
                <?php elseif (is_logged_in()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('user/dashboard.php') ?>">Mi cuenta</a></li>
                    <li class="nav-item"><a class="btn btn-outline-light btn-sm" href="<?= url('logout.php') ?>"><i class="bi bi-box-arrow-right"></i> Salir</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="site-main">
<?php if ($flash): ?>
    <div class="container flash-wrap">
        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
            <?= e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    </div>
<?php endif; ?>
