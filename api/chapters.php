<?php
require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

$bookId = (int) ($_GET['book_id'] ?? 0);
$stmt = $conexion->prepare(
    "SELECT id, book_id, chapter_number, title, content, created_at
     FROM chapters
     WHERE status = 'published' AND (? = 0 OR book_id = ?)
     ORDER BY book_id, chapter_number"
);
$stmt->execute([$bookId, $bookId]);

echo json_encode(['data' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
