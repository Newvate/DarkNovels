<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();

function sync_videos_schema(PDO $conexion): void
{
    if (!table_exists($conexion, 'videos')) {
        $conexion->exec(
            "CREATE TABLE IF NOT EXISTS videos (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                book_id INT UNSIGNED NULL DEFAULT NULL,
                title VARCHAR(180) NOT NULL,
                description TEXT NULL,
                video_path VARCHAR(255) NOT NULL,
                status ENUM('draft', 'published') NOT NULL DEFAULT 'published',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB"
        );
        return;
    }

    try {
        $constraints = $conexion->query(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'videos'
               AND COLUMN_NAME = 'book_id'
               AND REFERENCED_TABLE_NAME IS NOT NULL"
        )->fetchAll();

        foreach ($constraints as $constraint) {
            $name = str_replace('`', '``', $constraint['CONSTRAINT_NAME']);
            $conexion->exec("ALTER TABLE videos DROP FOREIGN KEY `{$name}`");
        }
    } catch (Throwable $e) {
        // Some hosts restrict information_schema; the direct ALTER below may still work.
    }

    try {
        $conexion->exec('ALTER TABLE videos MODIFY book_id INT UNSIGNED NULL DEFAULT NULL');
        $conexion->exec('UPDATE videos SET book_id = NULL WHERE book_id = 0');
    } catch (Throwable $e) {
        // Keep the admin page usable even if the host blocks schema changes.
    }
}

sync_videos_schema($conexion);

function register_uploaded_video_files(PDO $conexion): void
{
    $uploadDir = __DIR__ . '/../uploads/videos';
    if (!is_dir($uploadDir)) {
        return;
    }

    $files = glob($uploadDir . '/*.{mp4,webm,ogv,ogg,mov}', GLOB_BRACE);
    if (!$files) {
        return;
    }

    $existingRows = [];
    try {
        $existingRows = $conexion->query('SELECT video_path FROM videos')->fetchAll();
    } catch (Throwable $e) {
        return;
    }

    $existingPaths = [];
    $existingNames = [];
    foreach ($existingRows as $row) {
        $path = trim((string) ($row['video_path'] ?? ''));
        if ($path === '') {
            continue;
        }

        $existingPaths[] = $path;
        $existingNames[] = basename(parse_url($path, PHP_URL_PATH) ?: $path);
    }

    $fallbackBookId = null;
    try {
        $fallbackBookId = $conexion->query('SELECT id FROM books ORDER BY id ASC LIMIT 1')->fetchColumn();
        $fallbackBookId = $fallbackBookId ? (int) $fallbackBookId : null;
    } catch (Throwable $e) {
        $fallbackBookId = null;
    }

    $insertWithoutBook = $conexion->prepare(
        'INSERT INTO videos (book_id, title, description, video_path, status) VALUES (NULL, ?, "", ?, "published")'
    );
    $insertWithBook = $conexion->prepare(
        'INSERT INTO videos (book_id, title, description, video_path, status) VALUES (?, ?, "", ?, "published")'
    );

    foreach ($files as $file) {
        $relativePath = 'uploads/videos/' . basename($file);
        if (in_array($relativePath, $existingPaths, true) || in_array(basename($file), $existingNames, true)) {
            continue;
        }

        try {
            $insertWithoutBook->execute([
                pathinfo($file, PATHINFO_FILENAME),
                $relativePath,
            ]);
        } catch (Throwable $e) {
            if ($fallbackBookId) {
                try {
                    $insertWithBook->execute([
                        $fallbackBookId,
                        pathinfo($file, PATHINFO_FILENAME),
                        $relativePath,
                    ]);
                } catch (Throwable $inner) {
                    // If the host still blocks inserts, the public page can still show the file directly.
                }
            }
        }
    }
}

register_uploaded_video_files($conexion);

function save_video_upload($field, $currentPath = '')
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return $currentPath;
    }

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se pudo subir el video. Revisa el tamano permitido por el hosting.');
    }

    $originalName = $_FILES[$field]['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowed = array('mp4', 'webm', 'ogv', 'ogg', 'mov');

    if (!in_array($extension, $allowed, true)) {
        throw new Exception('Formato no valido. Usa MP4, WEBM, OGV, OGG o MOV.');
    }

    $targetDir = __DIR__ . '/../uploads/videos';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $fileName = 'video-' . date('YmdHis') . '-' . mt_rand(1000, 9999) . '.' . $extension;
    $targetPath = $targetDir . '/' . $fileName;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $targetPath)) {
        throw new Exception('No se pudo guardar el video. Verifica que exista uploads/videos.');
    }

    return 'uploads/videos/' . $fileName;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $conexion->prepare('SELECT video_path FROM videos WHERE id = ?');
        $stmt->execute(array($id));
        $video = $stmt->fetch();

        $delete = $conexion->prepare('DELETE FROM videos WHERE id = ?');
        $delete->execute(array($id));

        if ($video && !empty($video['video_path']) && substr($video['video_path'], 0, 4) !== 'http') {
            $filePath = __DIR__ . '/../' . $video['video_path'];
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }

        set_flash('Video eliminado.');
        redirect('admin/videos.php');
    }

    if ($action === 'update_visible') {
        $videoId = (int) ($_POST['id'] ?? 0);
        $bookId = (int) ($_POST['book_id'] ?? 0);
        $status = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        try {
            if ($videoId <= 0) {
                throw new Exception('No se encontro el video que intentas editar.');
            }

            if ($title === '') {
                throw new Exception('Escribe un titulo para el video.');
            }

            try {
                $stmt = $conexion->prepare('UPDATE videos SET book_id = ?, title = ?, description = ?, status = ? WHERE id = ?');
                $stmt->execute(array($bookId > 0 ? $bookId : null, $title, $description, $status, $videoId));
            } catch (Throwable $e) {
                $stmt = $conexion->prepare('UPDATE videos SET title = ?, description = ?, status = ? WHERE id = ?');
                $stmt->execute(array($title, $description, $status, $videoId));
            }

            $check = $conexion->prepare('SELECT id FROM videos WHERE id = ?');
            $check->execute(array($videoId));
            if (!$check->fetchColumn()) {
                throw new Exception('No se encontro el video que intentas editar.');
            }

            set_flash('Titulo visible actualizado.');
        } catch (Exception $e) {
            set_flash($e->getMessage(), 'danger');
        }

        redirect('admin/videos.php?edit=' . $videoId);
    }

    try {
        $videoPath = save_video_upload('video_file', trim($_POST['current_video_path'] ?? ''));
        if ($videoPath === '') {
            throw new Exception('Selecciona un video para subir.');
        }

        $bookId = (int) ($_POST['book_id'] ?? 0);
        $status = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';
        $title = trim($_POST['title'] ?? '');

        if ($title === '') {
            throw new Exception('Escribe un titulo para el video.');
        }

        $data = array(
            $bookId > 0 ? $bookId : null,
            $title,
            trim($_POST['description'] ?? ''),
            $videoPath,
            $status,
        );

        if (!empty($_POST['id'])) {
            $videoId = (int) $_POST['id'];
            $data[] = $videoId;
            try {
                $stmt = $conexion->prepare('UPDATE videos SET book_id = ?, title = ?, description = ?, video_path = ?, status = ? WHERE id = ?');
                $stmt->execute($data);
            } catch (Throwable $e) {
                $fallbackData = array($title, trim($_POST['description'] ?? ''), $videoPath, $status, $videoId);
                $stmt = $conexion->prepare('UPDATE videos SET title = ?, description = ?, video_path = ?, status = ? WHERE id = ?');
                $stmt->execute($fallbackData);
            }

            $check = $conexion->prepare('SELECT title FROM videos WHERE id = ?');
            $check->execute(array($videoId));
            $savedTitle = $check->fetchColumn();
            if ($savedTitle === false) {
                throw new Exception('No se encontro el video que intentas editar.');
            }

            set_flash('Video actualizado.');
            redirect('admin/videos.php?edit=' . $videoId);
        } else {
            try {
                $stmt = $conexion->prepare('INSERT INTO videos (book_id, title, description, video_path, status) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute($data);
            } catch (Throwable $e) {
                if ($bookId <= 0) {
                    $fallbackBookId = $conexion->query('SELECT id FROM books ORDER BY id ASC LIMIT 1')->fetchColumn();
                    if ($fallbackBookId) {
                        $data[0] = (int) $fallbackBookId;
                        $stmt = $conexion->prepare('INSERT INTO videos (book_id, title, description, video_path, status) VALUES (?, ?, ?, ?, ?)');
                        $stmt->execute($data);
                    } else {
                        throw $e;
                    }
                } else {
                    throw $e;
                }
            }
            set_flash('Video publicado.');
        }
    } catch (Exception $e) {
        set_flash($e->getMessage(), 'danger');
    }

    redirect('admin/videos.php');
}

$edit = null;
if (!empty($_GET['edit'])) {
    $stmt = $conexion->prepare('SELECT * FROM videos WHERE id = ?');
    $stmt->execute(array((int) $_GET['edit']));
    $edit = $stmt->fetch();
}

$books = $conexion->query('SELECT id, title FROM books ORDER BY title')->fetchAll();
$videos = $conexion->query(
    'SELECT v.*, b.title AS book_title
     FROM videos v
     LEFT JOIN books b ON b.id = v.book_id
     ORDER BY v.created_at DESC'
)->fetchAll();

$pageTitle = 'Gestion de videos';
require __DIR__ . '/../includes/header.php';
?>

<section class="section-pad admin-page">
    <div class="container content-grid">
        <div>
            <h1>Videos</h1>
            <a class="btn btn-outline-light mb-3" href="<?= url('pages/videos.php') ?>" target="_blank"><i class="bi bi-eye"></i> Ver pagina publica</a>
            <div class="table-responsive data-panel">
                <table class="table table-dark table-hover align-middle">
                    <thead><tr><th>Video</th><th>Titulo</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($videos as $video): ?>
                        <tr>
                            <td><video class="admin-table-video" src="<?= e(upload_or_url($video['video_path'], '')) ?>" muted preload="metadata"></video></td>
                            <td><?= e($video['title']) ?></td>
                            <td><?= e($video['status']) ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-light" href="<?= url('admin/videos.php?edit=' . $video['id']) ?>">Editar</a>
                                <form method="post" class="d-inline" onsubmit="return confirm('Eliminar video?')">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $video['id'] ?>">
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
            <h2><?= $edit ? 'Editar' : 'Nuevo' ?> video</h2>
            <p class="text-secondary">Los videos publicados se muestran en la pagina publica de videos.</p>
                <form method="post" <?= $edit ? '' : 'enctype="multipart/form-data"' ?>>
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="<?= $edit ? 'update_visible' : 'save' ?>">
                    <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
                    <input type="hidden" name="current_video_path" value="<?= e($edit['video_path'] ?? '') ?>">

                    <label class="form-label">Libro relacionado <span class="text-secondary">(opcional)</span></label>
                    <select class="form-select" name="book_id">
                        <option value="">Sin libro</option>
                        <?php foreach ($books as $book): ?>
                            <option value="<?= (int) $book['id'] ?>" <?= (int) ($edit['book_id'] ?? 0) === (int) $book['id'] ? 'selected' : '' ?>><?= e($book['title']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="form-label mt-3">Titulo del video</label>
                    <input class="form-control" name="title" value="<?= e($edit['title'] ?? '') ?>" required>

                    <label class="form-label mt-3">Descripcion</label>
                    <textarea class="form-control" name="description" rows="4"><?= e($edit['description'] ?? '') ?></textarea>

                    <?php if (!empty($edit['video_path'])): ?>
                        <label class="form-label mt-3">Video guardado</label>
                        <small class="text-secondary d-block mt-2">Archivo actual: <?= e(basename($edit['video_path'])) ?></small>
                        <video class="admin-video-preview" controls src="<?= e(upload_or_url($edit['video_path'], '')) ?>"></video>
                    <?php else: ?>
                        <label class="form-label mt-3">Subir video desde el dispositivo</label>
                        <input class="form-control" type="file" name="video_file" accept=".mp4,.webm,.ogv,.ogg,.mov" required>
                        <small class="text-secondary d-block mt-2">Formatos: MP4, WEBM, OGV, OGG o MOV.</small>
                    <?php endif; ?>

                    <label class="form-label mt-3">Estado</label>
                    <select class="form-select" name="status">
                        <option value="published" <?= ($edit['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Publicado</option>
                        <option value="draft" <?= ($edit['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Borrador</option>
                    </select>

                    <button class="btn btn-danger w-100 mt-3" type="submit"><?= $edit ? 'Guardar titulo visible' : 'Guardar video' ?></button>
                </form>
        </aside>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
