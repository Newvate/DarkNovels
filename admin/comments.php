<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) $_POST['id'];
    $action = $_POST['action'] ?? '';

    if (in_array($action, ['approved', 'rejected'], true)) {
        $conexion->prepare('UPDATE comments SET status = ? WHERE id = ?')->execute([$action, $id]);
        set_flash('Comentario moderado.');
    }

    if ($action === 'delete') {
        $conexion->prepare('DELETE FROM comments WHERE id = ?')->execute([$id]);
        set_flash('Comentario eliminado.');
    }

    redirect('admin/comments.php');
}

$comments = $conexion->query(
    'SELECT c.*, u.name, ch.title chapter_title, b.title book_title
     FROM comments c
     JOIN users u ON u.id = c.user_id
     JOIN chapters ch ON ch.id = c.chapter_id
     JOIN books b ON b.id = ch.book_id
     ORDER BY c.created_at DESC'
)->fetchAll();

$pageTitle = 'Comentarios';
require __DIR__ . '/../includes/header.php';
?>

<section class="section-pad admin-page">
    <div class="container">
        <h1>Comentarios</h1>
        <div class="data-panel">
            <?php foreach ($comments as $comment): ?>
                <div class="moderation-row">
                    <div>
                        <strong><?= e($comment['name']) ?></strong>
                        <span class="badge text-bg-secondary"><?= e($comment['status']) ?></span>
                        <p><?= e($comment['content']) ?></p>
                        <small><?= e($comment['book_title']) ?> / <?= e($comment['chapter_title']) ?></small>
                    </div>
                    <form method="post" class="d-flex flex-wrap gap-2">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= (int) $comment['id'] ?>">
                        <button class="btn btn-sm btn-outline-success" name="action" value="approved">Aprobar</button>
                        <button class="btn btn-sm btn-outline-warning" name="action" value="rejected">Rechazar</button>
                        <button class="btn btn-sm btn-outline-danger" name="action" value="delete">Eliminar</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
