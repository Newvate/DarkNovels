<?php
require_once __DIR__ . '/../includes/init.php';
require_login();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    if (mb_strlen($name) >= 3) {
        $conexion->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([$name, $user['id']]);
        refresh_session_user($conexion, (int) $user['id']);
        set_flash('Perfil actualizado.');
        redirect('user/dashboard.php');
    }
}

$favorites = $conexion->prepare(
    'SELECT b.* FROM favorites f JOIN books b ON b.id = f.book_id WHERE f.user_id = ? ORDER BY f.created_at DESC'
);
$favorites->execute([$user['id']]);

ensure_book_purchases_table($conexion);
$purchasedBooks = $conexion->prepare(
    'SELECT b.*, bp.created_at AS purchased_at
     FROM book_purchases bp
     JOIN books b ON b.id = bp.book_id
     WHERE bp.user_id = ?
     ORDER BY bp.created_at DESC'
);
$purchasedBooks->execute([$user['id']]);
$purchasedBooks = $purchasedBooks->fetchAll();

$progress = $conexion->prepare(
    'SELECT rp.*, b.title AS book_title, ch.title AS chapter_title, ch.chapter_number
     FROM reading_progress rp
     JOIN books b ON b.id = rp.book_id
     JOIN chapters ch ON ch.id = rp.chapter_id
     WHERE rp.user_id = ?
     ORDER BY rp.updated_at DESC'
);
$progress->execute([$user['id']]);

$pageTitle = 'Mi lectura';
require __DIR__ . '/../includes/header.php';
?>

<section class="page-hero compact">
    <div class="container">
        <p class="eyebrow">Biblioteca personal</p>
        <h1>Hola, <?= e($user['name']) ?></h1>
    </div>
</section>

<section class="section-pad">
    <div class="container content-grid">
        <div>
            <h2>Continuar lectura</h2>
            <div class="chapter-list">
                <?php foreach ($progress as $item): ?>
                    <a href="<?= url('reader/chapter.php?id=' . $item['chapter_id']) ?>">
                        <span><?= e($item['book_title']) ?> · Capitulo <?= (int) $item['chapter_number'] ?>: <?= e($item['chapter_title']) ?></span>
                        <small><?= e($item['updated_at']) ?></small>
                    </a>
                <?php endforeach; ?>
            </div>

            <h2 class="mt-5">Libros autorizados</h2>
            <div class="book-grid compact-grid">
                <?php foreach ($purchasedBooks as $book): ?>
                    <article class="book-card">
                        <a href="<?= url('pages/book.php?id=' . $book['id']) ?>">
                            <img src="<?= e(cover_or_url($book['cover_image'])) ?>" alt="Portada de <?= e($book['title']) ?>">
                        </a>
                        <div class="book-card-body">
                            <h3><a href="<?= url('pages/book.php?id=' . $book['id']) ?>"><?= e($book['title']) ?></a></h3>
                            <small>Autorizado: <?= e($book['purchased_at']) ?></small>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if (!$purchasedBooks): ?>
                    <p class="text-secondary">Cuando un pago sea autorizado, el libro aparecera aqui.</p>
                <?php endif; ?>
            </div>

            <h2 class="mt-5">Favoritos</h2>
            <div class="book-grid compact-grid">
                <?php foreach ($favorites as $book): ?>
                    <article class="book-card">
                        <a href="<?= url('pages/book.php?id=' . $book['id']) ?>">
                            <img src="<?= e(cover_or_url($book['cover_image'])) ?>" alt="Portada de <?= e($book['title']) ?>">
                        </a>
                        <div class="book-card-body">
                            <h3><a href="<?= url('pages/book.php?id=' . $book['id']) ?>"><?= e($book['title']) ?></a></h3>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
        <aside class="side-panel">
            <h3>Perfil</h3>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label class="form-label">Nombre</label>
                <input class="form-control" name="name" value="<?= e($user['name']) ?>" required>
                <button class="btn btn-danger w-100 mt-3" type="submit">Actualizar</button>
            </form>
        </aside>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
