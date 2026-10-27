<?php
require_once __DIR__ . '/../includes/init.php';

$chapterId = (int) ($_GET['id'] ?? 0);

$stmt = $conexion->prepare(
    "SELECT ch.*, b.title AS book_title, b.id AS book_id
     FROM chapters ch
     JOIN books b ON b.id = ch.book_id
     WHERE ch.id = ? AND ch.status = 'published' AND b.status = 'published'"
);
$stmt->execute([$chapterId]);
$chapter = $stmt->fetch();

if (!$chapter) {
    http_response_code(404);
    die('Capitulo no encontrado.');
}

$prevStmt = $conexion->prepare('SELECT id FROM chapters WHERE book_id = ? AND status = "published" AND chapter_number < ? ORDER BY chapter_number DESC LIMIT 1');
$prevStmt->execute([$chapter['book_id'], $chapter['chapter_number']]);
$prev = $prevStmt->fetch();

$nextStmt = $conexion->prepare('SELECT id FROM chapters WHERE book_id = ? AND status = "published" AND chapter_number > ? ORDER BY chapter_number ASC LIMIT 1');
$nextStmt->execute([$chapter['book_id'], $chapter['chapter_number']]);
$next = $nextStmt->fetch();

if (is_admin()) {
    $progress = $conexion->prepare('INSERT INTO reading_progress (user_id, book_id, chapter_id, progress_percent) VALUES (?, ?, ?, 0) ON DUPLICATE KEY UPDATE chapter_id = VALUES(chapter_id), updated_at = NOW()');
    $progress->execute([current_user()['id'], $chapter['book_id'], $chapter['id']]);
}

$pageTitle = $chapter['title'];
require __DIR__ . '/../includes/header.php';
?>

<article class="reader-shell">
    <header class="reader-header">
        <a href="<?= url('pages/book.php?id=' . $chapter['book_id']) ?>"><i class="bi bi-arrow-left"></i> <?= e($chapter['book_title']) ?></a>
        <h1>Capitulo <?= (int) $chapter['chapter_number'] ?>: <?= e($chapter['title']) ?></h1>
        <p><?= reading_time($chapter['content']) ?> min de lectura</p>
        <div class="reader-tools">
            <button class="btn btn-sm btn-outline-light" data-reader-theme="dark">Oscuro</button>
            <button class="btn btn-sm btn-outline-light" data-reader-theme="light">Claro</button>
            <button class="btn btn-sm btn-outline-light" data-reader-theme="paper">Pergamino</button>
            <button class="btn btn-sm btn-outline-light" data-font-step="-1">A-</button>
            <button class="btn btn-sm btn-outline-light" data-font-step="1">A+</button>
        </div>
    </header>
    <div class="reading-progress"><span></span></div>
    <div class="chapter-content" id="chapterContent">
        <?= $chapter['content'] ?>
    </div>
    <nav class="chapter-nav">
        <?php if ($prev): ?><a class="btn btn-outline-light" href="<?= url('reader/chapter.php?id=' . $prev['id']) ?>">Anterior</a><?php endif; ?>
        <?php if ($next): ?><a class="btn btn-danger" href="<?= url('reader/chapter.php?id=' . $next['id']) ?>">Siguiente</a><?php endif; ?>
    </nav>
    <section class="reader-donation">
        <i class="bi bi-heart-fill"></i>
        <div>
            <h2>¿Te gusto este capitulo?</h2>
            <p>Apoya la lectura libre con una donacion opcional. No necesitas registrarte.</p>
        </div>
        <a class="btn btn-danger" href="<?= url('donations.php?context=' . rawurlencode($chapter['book_title'] . ' - Capitulo ' . $chapter['chapter_number'])) ?>"><i class="bi bi-heart"></i> Donar</a>
    </section>
</article>

<?php require __DIR__ . '/../includes/footer.php'; ?>
