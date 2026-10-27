<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        $conexion->prepare('DELETE FROM chapters WHERE id = ?')->execute([(int) $_POST['id']]);
        set_flash('Capitulo eliminado.');
        redirect('admin/chapters.php');
    }

    $data = [
        (int) $_POST['book_id'],
        (int) $_POST['chapter_number'],
        trim($_POST['title'] ?? ''),
        $_POST['content'] ?? '',
        $_POST['status'] ?? 'published',
    ];

    if (!empty($_POST['id'])) {
        $data[] = (int) $_POST['id'];
        $conexion->prepare('UPDATE chapters SET book_id = ?, chapter_number = ?, title = ?, content = ?, status = ? WHERE id = ?')->execute($data);
        set_flash('Capitulo actualizado.');
    } else {
        $conexion->prepare('INSERT INTO chapters (book_id, chapter_number, title, content, status) VALUES (?, ?, ?, ?, ?)')->execute($data);
        set_flash('Capitulo creado.');
    }
    redirect('admin/chapters.php');
}

$edit = null;
if (!empty($_GET['edit'])) {
    $stmt = $conexion->prepare('SELECT * FROM chapters WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch();
}

$books = $conexion->query('SELECT id, title FROM books ORDER BY title')->fetchAll();
$chapters = $conexion->query('SELECT ch.*, b.title book_title FROM chapters ch JOIN books b ON b.id = ch.book_id ORDER BY b.title, ch.chapter_number')->fetchAll();

$pageTitle = 'Gestion de capitulos';
require __DIR__ . '/../includes/header.php';
?>

<section class="section-pad admin-page">
    <div class="container content-grid">
        <div>
            <h1>Capitulos</h1>
            <div class="table-responsive data-panel">
                <table class="table table-dark table-hover align-middle">
                    <thead><tr><th>Novela</th><th>Capitulo</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($chapters as $chapter): ?>
                        <tr>
                            <td><?= e($chapter['book_title']) ?></td>
                            <td><?= (int) $chapter['chapter_number'] ?>. <?= e($chapter['title']) ?></td>
                            <td><?= e($chapter['status']) ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-light" href="<?= url('admin/chapters.php?edit=' . $chapter['id']) ?>">Editar</a>
                                <form method="post" class="d-inline" onsubmit="return confirm('Eliminar capitulo?')">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $chapter['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <aside class="side-panel">
            <h2><?= $edit ? 'Editar' : 'Nuevo' ?> capitulo</h2>
            <p class="text-secondary">Los capitulos publicados se muestran dentro de su novela para cualquier visitante.</p>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
                <label class="form-label">Novela</label>
                <select class="form-select" name="book_id" required>
                    <?php foreach ($books as $book): ?>
                        <option value="<?= (int) $book['id'] ?>" <?= (int) ($edit['book_id'] ?? 0) === (int) $book['id'] ? 'selected' : '' ?>><?= e($book['title']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-3">Numero</label>
                <input class="form-control" type="number" name="chapter_number" min="1" value="<?= e((string) ($edit['chapter_number'] ?? 1)) ?>" required>
                <label class="form-label mt-3">Titulo</label>
                <input class="form-control" name="title" value="<?= e($edit['title'] ?? '') ?>" required>
                <label class="form-label mt-3">Contenido HTML</label>
                <textarea class="form-control code-editor" name="content" rows="12"><?= e($edit['content'] ?? '') ?></textarea>
                <label class="form-label mt-3">Estado</label>
                <select class="form-select" name="status">
                    <option value="published" <?= ($edit['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Publicado</option>
                    <option value="draft" <?= ($edit['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Borrador</option>
                </select>
                <button class="btn btn-danger w-100 mt-3" type="submit">Guardar</button>
            </form>
        </aside>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
