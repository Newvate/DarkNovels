<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo '<pre>';
echo "DarkNovels diagnostico\n";
echo "PHP: " . PHP_VERSION . "\n\n";

$requiredFiles = [
    'config/conexion.php',
    'includes/init.php',
    'index.php',
    'login.php',
    'admin/index.php',
    'admin/videos.php',
    'pages/book.php',
];

foreach ($requiredFiles as $file) {
    echo (is_file(__DIR__ . '/' . $file) ? 'OK   ' : 'FALTA') . " {$file}\n";
}

echo "\nProbando conexion...\n";

try {
    require __DIR__ . '/config/conexion.php';
    echo "OK conexion PDO\n";

    $tables = ['users', 'books', 'chapters', 'categories', 'videos'];
    foreach ($tables as $table) {
        try {
            $stmt = $conexion->query("SELECT COUNT(*) total FROM {$table}");
            $row = $stmt->fetch();
            echo "OK tabla {$table}: " . (int) $row['total'] . " registros\n";
        } catch (Throwable $e) {
            echo "ERROR tabla {$table}: " . $e->getMessage() . "\n";
        }
    }

    echo "\nProbando carga de init.php...\n";
    require __DIR__ . '/includes/init.php';
    echo "OK init.php\n";

    echo "\nProbando consulta del index...\n";
    try {
        $featured = $conexion->query(
            "SELECT b.*, c.name AS category_name,
                (SELECT COUNT(*) FROM chapters ch WHERE ch.book_id = b.id AND ch.status = 'published') AS chapters_count
             FROM books b
             LEFT JOIN categories c ON c.id = b.category_id
             WHERE b.status = 'published'
             ORDER BY b.created_at DESC
             LIMIT 6"
        )->fetchAll();
        echo "OK consulta index: " . count($featured) . " novelas destacadas\n";
    } catch (Throwable $e) {
        echo "ERROR consulta index: " . $e->getMessage() . "\n";
    }

    echo "\nProbando usuario admin...\n";
    try {
        $stmt = $conexion->prepare("SELECT id, email, role, status FROM users WHERE role = 'admin' LIMIT 1");
        $stmt->execute();
        $admin = $stmt->fetch();
        if ($admin) {
            echo "OK admin encontrado: " . $admin['email'] . " / " . $admin['role'] . " / " . $admin['status'] . "\n";
        } else {
            echo "ERROR no hay usuario admin en la tabla users\n";
        }
    } catch (Throwable $e) {
        echo "ERROR admin: " . $e->getMessage() . "\n";
    }
} catch (Throwable $e) {
    echo "ERROR GENERAL: " . $e->getMessage() . "\n";
}

echo '</pre>';
