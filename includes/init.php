<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/conexion.php';

define('APP_NAME', 'DarkNovels');
define('BOOK_PRICE_SOLES', '10.06');
define('YAPE_PHONE', '+51923844622');

function dn_starts_with(string $value, string $prefix): bool
{
    return substr($value, 0, strlen($prefix)) === $prefix;
}

function dn_ends_with(string $value, string $suffix): bool
{
    if ($suffix === '') {
        return true;
    }

    return substr($value, -strlen($suffix)) === $suffix;
}

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$baseUrl = '';
if (dn_starts_with($scriptName, '/Darknovels/')) {
    $baseUrl = '/Darknovels';
}
define('BASE_URL', $baseUrl);

if (!headers_sent() && dn_ends_with($_SERVER['SCRIPT_NAME'] ?? '', '.php')) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    return is_logged_in() && current_user()['role'] === 'admin';
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('Inicia sesion para continuar.', 'warning');
        redirect('login.php');
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        die('Acceso restringido al administrador.');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        die('Token de seguridad invalido.');
    }
}

function set_flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function get_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function excerpt(?string $text, int $length = 160): string
{
    $plain = trim(strip_tags($text ?? ''));
    if (strlen($plain) <= $length) {
        return $plain;
    }

    return substr($plain, 0, $length - 3) . '...';
}

function reading_time(?string $html): int
{
    $words = str_word_count(strip_tags($html ?? ''));
    return max(1, (int) ceil($words / 220));
}

function upload_or_url(?string $path, string $fallback): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return $fallback;
    }

    if (dn_starts_with($path, 'http://') || dn_starts_with($path, 'https://')) {
        return $path;
    }

    return url($path);
}

function safe_media_url(?string $path, string $fallback): string
{
    $path = trim((string) $path);
    $lowerPath = strtolower($path);

    // Some external image hosts return an error page as an image. Keep the catalog usable.
    if ($path === '' || strpos($lowerPath, 'mgbb') !== false) {
        return url($fallback);
    }

    return upload_or_url($path, url($fallback));
}

function cover_or_url(?string $path): string
{
    return safe_media_url($path, 'assets/img/darknovels-hero.png');
}

function refresh_session_user(PDO $conexion, int $userId): void
{
    $stmt = $conexion->prepare('SELECT id, name, email, role, avatar, status FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if ($user && $user['status'] === 'active') {
        $_SESSION['user'] = $user;
    }
}

function table_exists(PDO $conexion, string $table): bool
{
    try {
        $stmt = $conexion->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

function safe_count(PDO $conexion, string $table): int
{
    if (!table_exists($conexion, $table)) {
        return 0;
    }

    return (int) $conexion->query("SELECT COUNT(*) total FROM {$table}")->fetch()['total'];
}

function ensure_book_purchases_table(PDO $conexion): void
{
    $conexion->exec(
        "CREATE TABLE IF NOT EXISTS book_purchases (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            book_id INT UNSIGNED NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 10.06,
            payment_method VARCHAR(40) NOT NULL DEFAULT 'Yape',
            payment_phone VARCHAR(20) NOT NULL DEFAULT '+51923844622',
            notes VARCHAR(255) NULL,
            granted_by INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_book_purchase (user_id, book_id),
            CONSTRAINT fk_book_purchases_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_book_purchases_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
            CONSTRAINT fk_book_purchases_admin FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB"
    );
}

function user_has_book_access(PDO $conexion, int $userId, int $bookId): bool
{
    if ($userId <= 0 || $bookId <= 0) {
        return false;
    }

    ensure_book_purchases_table($conexion);
    $stmt = $conexion->prepare('SELECT 1 FROM book_purchases WHERE user_id = ? AND book_id = ? LIMIT 1');
    $stmt->execute([$userId, $bookId]);
    return (bool) $stmt->fetchColumn();
}
