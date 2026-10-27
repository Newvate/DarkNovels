<?php
require_once __DIR__ . '/../includes/init.php';

$pageTitle = 'Biblioteca';
$search = trim($_GET['q'] ?? '');
$category = (int) ($_GET['category'] ?? 0);

$categories = $conexion->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$where = ["b.status = 'published'"];
$params = [];

if ($search !== '') {
    $where[] = '(b.title LIKE ? OR b.synopsis LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($category > 0) {
    $where[] = 'b.category_id = ?';
    $params[] = $category;
}

$stmt = $conexion->prepare(
    "SELECT b.*, c.name AS category_name,
        (SELECT ROUND(AVG(rating), 1) FROM ratings r WHERE r.book_id = b.id) AS avg_rating,
        (SELECT COUNT(*) FROM chapters ch WHERE ch.book_id = b.id AND ch.status = 'published') AS chapters_count
     FROM books b
     LEFT JOIN categories c ON c.id = b.category_id
     WHERE " . implode(' AND ', $where) . '
     ORDER BY b.created_at DESC'
);
$stmt->execute($params);
$books = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<section class="page-hero compact">
    <div class="container">
        <p class="eyebrow">Explora</p>
        <h1>Biblioteca DarkNovels</h1>
    </div>
</section>

<section class="section-pad pt-4">
    <div class="container">
        <form class="filter-bar" method="get">
            <input class="form-control" type="search" name="q" value="<?= e($search) ?>" placeholder="Buscar por titulo o sinopsis">
            <select class="form-select" name="category">
                <option value="0">Todas las categorias</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= $category === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-danger" type="submit"><i class="bi bi-search"></i></button>
        </form>

        <div class="book-grid mt-4">
            <?php foreach ($books as $book): ?>
                <article class="book-card">
                    <a href="<?= url('pages/book.php?id=' . $book['id']) ?>">
                        <img src="<?= e(cover_or_url($book['cover_image'])) ?>" alt="Portada de <?= e($book['title']) ?>">
                    </a>
                    <div class="book-card-body">
                        <span class="badge text-bg-danger"><?= e($book['category_name'] ?? 'Sin categoria') ?></span>
                        <h3><a href="<?= url('pages/book.php?id=' . $book['id']) ?>"><?= e($book['title']) ?></a></h3>
                        <p><?= e(excerpt($book['synopsis'], 120)) ?></p>
                        <small><i class="bi bi-star-fill text-warning"></i> <?= e($book['avg_rating'] ?? 'Sin votos') ?> · <?= (int) $book['chapters_count'] ?> capitulos · Lectura gratuita</small>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if (!$books): ?>
            <div class="empty-state">No encontramos novelas con esos filtros.</div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
