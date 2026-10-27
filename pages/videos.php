<?php
require_once __DIR__ . '/../includes/init.php';

$pageTitle = 'Videos';
$videos = [];
$videosError = '';

try {
    if (table_exists($conexion, 'videos')) {
        $videos = $conexion->query(
            "SELECT v.*, b.title AS book_title
             FROM videos v
             LEFT JOIN books b ON b.id = v.book_id
             ORDER BY v.created_at DESC"
        )->fetchAll();
    }
} catch (Throwable $e) {
    $videosError = 'No se pudieron cargar los videos.';
}

require __DIR__ . '/../includes/header.php';
?>

<section class="page-hero compact">
    <div class="container">
        <p class="eyebrow">Multimedia</p>
        <h1>Videos</h1>
    </div>
</section>

<section class="section-pad pt-4">
    <div class="container">
        <?php if ($videos): ?>
            <div class="video-grid">
                <?php foreach ($videos as $video): ?>
                    <article class="video-card">
                        <video controls preload="metadata" src="<?= e(upload_or_url($video['video_path'], '')) ?>"></video>
                        <div class="video-card-body">
                            <h3><?= e($video['title']) ?></h3>
                            <?php if (!empty($video['description'])): ?>
                                <p><?= e($video['description']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($video['book_title'])): ?>
                                <small class="text-secondary d-block mt-2">Relacionado: <?= e($video['book_title']) ?></small>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php elseif ($videosError): ?>
            <div class="empty-state"><?= e($videosError) ?></div>
        <?php else: ?>
            <div class="empty-state">Aun no hay videos subidos.</div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
