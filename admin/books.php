<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();

function upload_book_image(string $field, string $folder, string $currentPath = ''): string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $currentPath;
    }

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir la imagen.');
    }

    if ($_FILES[$field]['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('La imagen no debe pesar mas de 5 MB.');
    }

    $imageInfo = getimagesize($_FILES[$field]['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if (!$imageInfo || empty($allowed[$imageInfo['mime']])) {
        throw new RuntimeException('Formato no valido. Usa JPG, PNG, WEBP o GIF.');
    }

    $targetDir = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }

    $fileName = $folder . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$imageInfo['mime']];
    $targetPath = $targetDir . '/' . $fileName;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $targetPath)) {
        throw new RuntimeException('No se pudo guardar la imagen en uploads.');
    }

    return 'uploads/' . $folder . '/' . $fileName;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        $conexion->prepare('DELETE FROM books WHERE id = ?')->execute([(int) $_POST['id']]);
        set_flash('Novela eliminada.');
        redirect('admin/books.php');
    }

    try {
        $coverImage = trim($_POST['cover_image'] ?? '') ?: trim($_POST['current_cover_image'] ?? '');
        $bannerImage = trim($_POST['banner_image'] ?? '') ?: trim($_POST['current_banner_image'] ?? '');
        $coverImage = upload_book_image('cover_file', 'covers', $coverImage);
        $bannerImage = upload_book_image('banner_file', 'banners', $bannerImage);
    } catch (RuntimeException $e) {
        set_flash($e->getMessage(), 'danger');
        redirect('admin/books.php' . (!empty($_POST['id']) ? '?edit=' . (int) $_POST['id'] : ''));
    }

    $data = [
        trim($_POST['title'] ?? ''),
        trim($_POST['synopsis'] ?? ''),
        (int) ($_POST['category_id'] ?? 0) ?: null,
        $coverImage,
        $bannerImage,
        $_POST['status'] ?? 'published',
    ];

    if (!empty($_POST['id'])) {
        $data[] = (int) $_POST['id'];
        $conexion->prepare('UPDATE books SET title = ?, synopsis = ?, category_id = ?, cover_image = ?, banner_image = ?, status = ? WHERE id = ?')->execute($data);
        set_flash('Novela actualizada.');
    } else {
        $conexion->prepare('INSERT INTO books (title, synopsis, category_id, cover_image, banner_image, status) VALUES (?, ?, ?, ?, ?, ?)')->execute($data);
        set_flash('Novela creada.');
    }
    redirect('admin/books.php');
}

$edit = null;
if (!empty($_GET['edit'])) {
    $stmt = $conexion->prepare('SELECT * FROM books WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch();
}

$categories = $conexion->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$books = $conexion->query('SELECT b.*, c.name category_name FROM books b LEFT JOIN categories c ON c.id = b.category_id ORDER BY b.created_at DESC')->fetchAll();

$pageTitle = 'Gestion de novelas';
require __DIR__ . '/../includes/header.php';
?>

<section class="section-pad admin-page">
    <div class="container content-grid">
        <div>
            <h1>Novelas</h1>
            <div class="table-responsive data-panel">
                <table class="table table-dark table-hover align-middle">
                    <thead><tr><th>Portada</th><th>Titulo</th><th>Categoria</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($books as $book): ?>
                        <tr>
                            <td>
                                <img class="admin-table-cover" src="<?= e(upload_or_url($book['cover_image'], 'https://images.unsplash.com/photo-1519682337058-a94d519337bc?auto=format&fit=crop&w=400&q=80')) ?>" alt="Portada de <?= e($book['title']) ?>">
                            </td>
                            <td><?= e($book['title']) ?></td>
                            <td><?= e($book['category_name'] ?? '-') ?></td>
                            <td><?= e($book['status']) ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-light" href="<?= url('admin/books.php?edit=' . $book['id']) ?>">Editar</a>
                                <form method="post" class="d-inline" onsubmit="return confirm('Eliminar novela?')">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $book['id'] ?>">
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
            <h2><?= $edit ? 'Editar' : 'Nueva' ?> novela</h2>
            <p class="text-secondary">Las novelas publicadas aparecen automaticamente en la biblioteca publica.</p>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
                <input type="hidden" name="current_cover_image" value="<?= e($edit['cover_image'] ?? '') ?>">
                <input type="hidden" name="current_banner_image" value="<?= e($edit['banner_image'] ?? '') ?>">
                <label class="form-label">Titulo</label>
                <input class="form-control" name="title" value="<?= e($edit['title'] ?? '') ?>" required>
                <label class="form-label mt-3">Sinopsis</label>
                <textarea class="form-control" name="synopsis" rows="5"><?= e($edit['synopsis'] ?? '') ?></textarea>
                <label class="form-label mt-3">Categoria</label>
                <select class="form-select" name="category_id">
                    <option value="">Sin categoria</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>" <?= (int) ($edit['category_id'] ?? 0) === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-3">Subir portada</label>
                <input class="form-control" type="file" name="cover_file" accept="image/jpeg,image/png,image/webp,image/gif">
                <?php if (!empty($edit['cover_image'])): ?>
                    <img class="admin-image-preview" src="<?= e(upload_or_url($edit['cover_image'], '')) ?>" alt="Portada actual">
                <?php endif; ?>
                <label class="form-label mt-3">O pegar URL de portada</label>
                <input class="form-control" name="cover_image" value="<?= e($edit['cover_image'] ?? '') ?>" placeholder="https://...">
                <label class="form-label mt-3">Subir banner</label>
                <input class="form-control" type="file" name="banner_file" accept="image/jpeg,image/png,image/webp,image/gif">
                <?php if (!empty($edit['banner_image'])): ?>
                    <img class="admin-banner-preview" src="<?= e(upload_or_url($edit['banner_image'], '')) ?>" alt="Banner actual">
                <?php endif; ?>
                <label class="form-label mt-3">O pegar URL de banner</label>
                <input class="form-control" name="banner_image" value="<?= e($edit['banner_image'] ?? '') ?>" placeholder="https://...">
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
