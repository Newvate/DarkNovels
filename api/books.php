<?php
require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

$books = $conexion->query(
    "SELECT b.id, b.title, b.synopsis, b.cover_image, b.banner_image, c.name category,
        (SELECT COUNT(*) FROM chapters ch WHERE ch.book_id = b.id AND ch.status = 'published') chapters_count
     FROM books b
     LEFT JOIN categories c ON c.id = b.category_id
     WHERE b.status = 'published'
     ORDER BY b.created_at DESC"
)->fetchAll();

echo json_encode(['data' => $books], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
