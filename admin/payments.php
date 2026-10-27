<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();

ensure_book_purchases_table($conexion);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? '';
    $userId = (int) ($_POST['user_id'] ?? 0);
    $bookId = (int) ($_POST['book_id'] ?? 0);

    if ($action === 'grant' && $userId > 0 && $bookId > 0) {
        $notes = trim($_POST['notes'] ?? '');
        $stmt = $conexion->prepare(
            'INSERT INTO book_purchases (user_id, book_id, amount, payment_method, payment_phone, notes, granted_by)
             VALUES (?, ?, ?, "Yape", ?, ?, ?)
             ON DUPLICATE KEY UPDATE amount = VALUES(amount), payment_method = VALUES(payment_method), payment_phone = VALUES(payment_phone), notes = VALUES(notes), granted_by = VALUES(granted_by), created_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([$userId, $bookId, BOOK_PRICE_SOLES, YAPE_PHONE, $notes, current_user()['id']]);
        set_flash('Acceso autorizado para el libro.');
    }

    if ($action === 'revoke' && $userId > 0 && $bookId > 0) {
        $stmt = $conexion->prepare('DELETE FROM book_purchases WHERE user_id = ? AND book_id = ?');
        $stmt->execute([$userId, $bookId]);
        set_flash('Acceso retirado.', 'warning');
    }

    redirect('admin/payments.php');
}

$users = $conexion->query('SELECT id, name, email FROM users WHERE role <> "admin" AND status = "active" ORDER BY name')->fetchAll();
$books = $conexion->query('SELECT id, title FROM books WHERE status = "published" ORDER BY title')->fetchAll();
$purchases = $conexion->query(
    'SELECT bp.*, u.name, u.email, b.title AS book_title, admin.name AS admin_name
     FROM book_purchases bp
     JOIN users u ON u.id = bp.user_id
     JOIN books b ON b.id = bp.book_id
     LEFT JOIN users admin ON admin.id = bp.granted_by
     ORDER BY bp.created_at DESC'
)->fetchAll();

$pageTitle = 'Pagos';
require __DIR__ . '/../includes/header.php';
?>

<section class="section-pad admin-page">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Yape <?= e(YAPE_PHONE) ?></p>
                <h1>Autorizar pagos</h1>
            </div>
            <a class="btn btn-outline-light" href="<?= url('admin/index.php') ?>">Volver</a>
        </div>

        <div class="content-grid admin-payment-grid">
            <form class="side-panel" method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="grant">
                <h3>Dar acceso</h3>
                <p>Despues de confirmar el Yape de S/ <?= e(BOOK_PRICE_SOLES) ?>, elige el lector y el libro que pago.</p>

                <label class="form-label">Lector</label>
                <select class="form-select" name="user_id" required>
                    <option value="">Seleccionar lector</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= (int) $user['id'] ?>"><?= e($user['name']) ?> - <?= e($user['email']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="form-label mt-3">Libro</label>
                <select class="form-select" name="book_id" required>
                    <option value="">Seleccionar libro</option>
                    <?php foreach ($books as $book): ?>
                        <option value="<?= (int) $book['id'] ?>"><?= e($book['title']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="form-label mt-3">Nota opcional</label>
                <input class="form-control" name="notes" placeholder="Operacion Yape, fecha o comentario">

                <button class="btn btn-danger w-100 mt-4" type="submit">Autorizar lectura</button>
            </form>

            <div class="data-panel">
                <h3>Accesos autorizados</h3>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Lector</th>
                                <th>Libro</th>
                                <th>Monto</th>
                                <th>Fecha</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($purchases as $purchase): ?>
                            <tr>
                                <td>
                                    <?= e($purchase['name']) ?><br>
                                    <small class="text-secondary"><?= e($purchase['email']) ?></small>
                                </td>
                                <td>
                                    <?= e($purchase['book_title']) ?><br>
                                    <small class="text-secondary"><?= e($purchase['notes'] ?: 'Sin nota') ?></small>
                                </td>
                                <td>S/ <?= e((string) $purchase['amount']) ?></td>
                                <td><?= e($purchase['created_at']) ?></td>
                                <td>
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="revoke">
                                        <input type="hidden" name="user_id" value="<?= (int) $purchase['user_id'] ?>">
                                        <input type="hidden" name="book_id" value="<?= (int) $purchase['book_id'] ?>">
                                        <button class="btn btn-sm btn-outline-light" type="submit">Quitar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$purchases): ?>
                            <tr><td colspan="5" class="text-center text-secondary">Aun no hay pagos autorizados.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
