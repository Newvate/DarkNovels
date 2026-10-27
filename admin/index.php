<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();

$stats = [
    'books' => safe_count($conexion, 'books'),
    'chapters' => safe_count($conexion, 'chapters'),
    'videos' => safe_count($conexion, 'videos'),
    'payments' => safe_count($conexion, 'book_purchases'),
];

$pageTitle = 'Panel administrativo';
require __DIR__ . '/../includes/header.php';
?>

<section class="page-hero compact">
    <div class="container">
        <p class="eyebrow">Administrador</p>
        <h1>Panel DarkNovels</h1>
    </div>
</section>

<section class="section-pad">
    <div class="container">
        <div class="admin-actions">
            <a href="<?= url('admin/books.php') ?>" class="btn btn-danger"><i class="bi bi-book"></i> Novelas</a>
            <a href="<?= url('admin/chapters.php') ?>" class="btn btn-danger"><i class="bi bi-file-richtext"></i> Capitulos</a>
            <a href="<?= url('admin/videos.php') ?>" class="btn btn-danger"><i class="bi bi-camera-reels"></i> Videos</a>
            <a href="<?= url('admin/payments.php') ?>" class="btn btn-danger"><i class="bi bi-cash-coin"></i> Pagos</a>
            <a href="<?= url('admin/users.php') ?>" class="btn btn-danger"><i class="bi bi-people"></i> Usuarios</a>
        </div>
        <div class="stats-grid mt-4">
            <?php foreach ($stats as $label => $value): ?>
                <div class="stat-card">
                    <span><?= e($label) ?></span>
                    <strong><?= (int) $value ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
