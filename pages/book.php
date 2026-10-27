<?php
require_once __DIR__ . '/../includes/init.php';

$bookId = (int) ($_GET['id'] ?? 0);

$stmt = $conexion->prepare(
    "SELECT b.*, c.name AS category_name,
        (SELECT ROUND(AVG(rating), 1) FROM ratings r WHERE r.book_id = b.id) AS avg_rating
     FROM books b
     LEFT JOIN categories c ON c.id = b.category_id
     WHERE b.id = ? AND b.status = 'published'"
);
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book) {
    http_response_code(404);
    die('Novela no encontrada.');
}

$chaptersStmt = $conexion->prepare("SELECT * FROM chapters WHERE book_id = ? AND status = 'published' ORDER BY chapter_number ASC");
$chaptersStmt->execute([$bookId]);
$chapters = $chaptersStmt->fetchAll();

$videos = [];
if (table_exists($conexion, 'videos')) {
    $videosStmt = $conexion->prepare("SELECT * FROM videos WHERE book_id = ? AND status = 'published' ORDER BY created_at DESC");
    $videosStmt->execute([$bookId]);
    $videos = $videosStmt->fetchAll();
}

$pageTitle = $book['title'];
require __DIR__ . '/../includes/header.php';
?>

<section class="book-hero" style="background-image: linear-gradient(90deg, rgba(0,0,0,.92), rgba(0,0,0,.58)), url('<?= e(url('assets/img/darknovels-hero.png')) ?>')">
    <div class="container book-hero-grid">
        <img class="book-cover" src="<?= e(cover_or_url($book['cover_image'])) ?>" alt="Portada de <?= e($book['title']) ?>">
        <div>
            <span class="badge text-bg-danger"><?= e($book['category_name'] ?? 'Sin categoria') ?></span>
            <h1><?= e($book['title']) ?></h1>
            <p class="lead"><?= e($book['synopsis']) ?></p>
            <p class="text-secondary"><i class="bi bi-star-fill text-warning"></i> <?= e($book['avg_rating'] ?? 'Sin votos') ?> · <?= count($chapters) ?> capitulos · <?= count($videos) ?> videos</p>
            <div class="d-flex flex-wrap gap-2">
                <?php if ($chapters): ?>
                    <a class="btn btn-danger" href="<?= url('reader/chapter.php?id=' . $chapters[0]['id']) ?>"><i class="bi bi-play-fill"></i> Leer ahora</a>
                <?php endif; ?>
                <?php if (is_admin()): ?>
                    <a class="btn btn-outline-light" href="<?= url('admin/books.php?edit=' . $book['id']) ?>"><i class="bi bi-pencil"></i> Editar libro</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="section-pad">
    <div class="container content-grid">
        <div>
            <div class="section-head">
                <h2>Capitulos</h2>
            </div>
            <div class="chapter-list">
                <?php foreach ($chapters as $chapter): ?>
                    <a href="<?= url('reader/chapter.php?id=' . $chapter['id']) ?>">
                        <span>Capitulo <?= (int) $chapter['chapter_number'] ?>: <?= e($chapter['title']) ?></span>
                        <small><?= reading_time($chapter['content']) ?> min</small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <aside class="side-panel">
            <h3>Lectura libre</h3>
            <p>No necesitas iniciar sesion para leer esta novela. El acceso privado es solo para administrar publicaciones.</p>
            <?php if (is_admin()): ?>
                <a class="btn btn-danger w-100" href="<?= url('admin/chapters.php') ?>">Agregar capitulos</a>
                <a class="btn btn-outline-light w-100 mt-2" href="<?= url('admin/videos.php') ?>">Agregar videos</a>
            <?php endif; ?>
        </aside>
    </div>
</section>

<?php if ($videos): ?>
<section class="section-pad pt-0">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Multimedia</p>
                <h2>Videos</h2>
            </div>
        </div>
        <div class="video-grid">
            <?php foreach ($videos as $video): ?>
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
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
