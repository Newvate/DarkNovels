<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Inicio';
$featured = [];
$latestVideos = [];
$indexError = '';

try {
    $featured = $conexion->query(
        "SELECT b.*, c.name AS category_name,
            (SELECT COUNT(*) FROM chapters ch WHERE ch.book_id = b.id AND ch.status = 'published') AS chapters_count
         FROM books b
         LEFT JOIN categories c ON c.id = b.category_id
         WHERE b.status = 'published'
         ORDER BY b.created_at DESC
         LIMIT 6"
    )->fetchAll();
} catch (Throwable $e) {
    $indexError = 'No se pudieron cargar las novelas destacadas.';
}

try {
    if (table_exists($conexion, 'videos')) {
        $latestVideos = $conexion->query(
            "SELECT *
             FROM videos
             ORDER BY created_at DESC
             LIMIT 6"
        )->fetchAll();
    }
} catch (Throwable $e) {
    $latestVideos = [];
}

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container hero-content">
        <p class="eyebrow">Accion / Suspenso / Misterio / Fantasia</p>
        <h1>Donde nacen las historias que nadie se atreve a contar.</h1>
        <p class="lead">Una plataforma responsive para publicar, administrar y leer novelas originales con una experiencia cinematografica.</p>
        <div class="hero-actions">
            <a href="<?= url('pages/library.php') ?>" class="btn btn-danger btn-lg"><i class="bi bi-book"></i> Comenzar a leer</a>
        </div>
    </div>
</section>

<section class="section-pad">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Biblioteca</p>
                <h2>Novelas destacadas</h2>
            </div>
            <a href="<?= url('pages/library.php') ?>" class="link-light">Ver biblioteca <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="book-grid">
            <?php foreach ($featured as $book): ?>
                <article class="book-card">
                    <a href="<?= url('pages/book.php?id=' . $book['id']) ?>">
                        <img src="<?= e(cover_or_url($book['cover_image'])) ?>" alt="Portada de <?= e($book['title']) ?>">
                    </a>
                    <div class="book-card-body">
                        <span class="badge text-bg-danger"><?= e($book['category_name'] ?? 'Sin categoria') ?></span>
                        <h3><a href="<?= url('pages/book.php?id=' . $book['id']) ?>"><?= e($book['title']) ?></a></h3>
                        <p><?= e(excerpt($book['synopsis'], 110)) ?></p>
                        <small><i class="bi bi-journal-text"></i> <?= (int) $book['chapters_count'] ?> capitulos · Lectura gratuita</small>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if ($indexError): ?>
            <div class="empty-state"><?= e($indexError) ?></div>
        <?php endif; ?>
        <?php if (!$featured && !$indexError): ?>
            <div class="empty-state">Aun no hay novelas publicadas.</div>
        <?php endif; ?>
    </div>
</section>

<section class="section-pad pt-0">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Multimedia</p>
                <h2>Videos recientes</h2>
            </div>
            <a href="<?= url('pages/videos.php') ?>" class="link-light">Ver videos <i class="bi bi-arrow-right"></i></a>
        </div>
        <?php if ($latestVideos): ?>
            <div class="video-grid">
                <?php foreach ($latestVideos as $video): ?>
                    <article class="video-card">
                        <video controls preload="metadata" src="<?= e(upload_or_url($video['video_path'], '')) ?>"></video>
                        <div class="video-card-body">
                            <h3><?= e($video['title']) ?></h3>
                            <?php if (!empty($video['description'])): ?>
                                <p><?= e($video['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">Aun no hay videos subidos.</div>
        <?php endif; ?>
    </div>
</section>

<section class="feature-band">
    <div class="container feature-grid">
        <div>
            <i class="bi bi-eye"></i>
            <h3>Lectura publica</h3>
            <p>Cualquier visitante puede ver libros y leer capitulos sin iniciar sesion.</p>
        </div>
        <div>
            <i class="bi bi-shield-lock"></i>
            <h3>Acceso privado</h3>
            <p>Solo el administrador entra al panel para publicar contenido.</p>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
