CREATE TABLE IF NOT EXISTS videos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id INT UNSIGNED NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    video_path VARCHAR(255) NOT NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_videos_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE SET NULL
) ENGINE=InnoDB;

UPDATE users
SET name = 'Administrador DarkNovels',
    email = 'martinez1139mar@gmail.com',
    password = '$2y$10$vU4s5rGjn20Ik8sk17tR..7dOv57Y.x9/Q3CvE5wOuAB6XCQcFgM.',
    role = 'admin',
    status = 'active'
WHERE id = 1 OR role = 'admin';

INSERT INTO users (name, email, password, role, status)
SELECT 'Administrador DarkNovels', 'martinez1139mar@gmail.com', '$2y$10$vU4s5rGjn20Ik8sk17tR..7dOv57Y.x9/Q3CvE5wOuAB6XCQcFgM.', 'admin', 'active'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'martinez1139mar@gmail.com');
