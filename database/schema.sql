CREATE DATABASE IF NOT EXISTS darknovels CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE darknovels;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'reader', 'premium_author') NOT NULL DEFAULT 'reader',
    avatar VARCHAR(255) NULL,
    status ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS books (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NULL,
    title VARCHAR(180) NOT NULL,
    synopsis TEXT NULL,
    cover_image VARCHAR(255) NULL,
    banner_image VARCHAR(255) NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_books_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS chapters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id INT UNSIGNED NULL,
    chapter_number INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    content MEDIUMTEXT NOT NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_book_chapter (book_id, chapter_number),
    CONSTRAINT fk_chapters_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS videos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    video_path VARCHAR(255) NOT NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_videos_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS favorites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    book_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_favorite (user_id, book_id),
    CONSTRAINT fk_favorites_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_favorites_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ratings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    book_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_rating (user_id, book_id),
    CONSTRAINT fk_ratings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ratings_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    chapter_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_chapter FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reading_progress (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    book_id INT UNSIGNED NOT NULL,
    chapter_id INT UNSIGNED NOT NULL,
    progress_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_progress (user_id, book_id),
    CONSTRAINT fk_progress_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_progress_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    CONSTRAINT fk_progress_chapter FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO users (id, name, email, password, role, status) VALUES
(1, 'Administrador DarkNovels', 'martinez1139mar@gmail.com', '$2y$10$vU4s5rGjn20Ik8sk17tR..7dOv57Y.x9/Q3CvE5wOuAB6XCQcFgM.', 'admin', 'active')
ON DUPLICATE KEY UPDATE
name = VALUES(name),
email = VALUES(email),
password = VALUES(password),
role = VALUES(role),
status = VALUES(status);

INSERT IGNORE INTO categories (id, name, slug) VALUES
(1, 'Suspenso', 'suspenso'),
(2, 'Accion', 'accion'),
(3, 'Misterio', 'misterio'),
(4, 'Fantasia oscura', 'fantasia-oscura');

INSERT IGNORE INTO books (id, category_id, title, synopsis, cover_image, banner_image, status) VALUES
(1, 1, 'La Ciudad Bajo la Luna Roja', 'Un investigador descubre que cada eclipse despierta una red de crimenes antiguos en una ciudad cubierta por niebla roja.', 'https://images.unsplash.com/photo-1519682337058-a94d519337bc?auto=format&fit=crop&w=800&q=80', 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1600&q=80', 'published'),
(2, 2, 'Codigo Carmesi', 'Una exagente perseguida por su propio equipo debe descifrar un archivo que podria derribar a toda una organizacion.', 'https://images.unsplash.com/photo-1532012197267-da84d127e765?auto=format&fit=crop&w=800&q=80', 'https://images.unsplash.com/photo-1519608487953-e999c86e7455?auto=format&fit=crop&w=1600&q=80', 'published');

INSERT IGNORE INTO chapters (id, book_id, chapter_number, title, content, status) VALUES
(1, 1, 1, 'La primera sombra', '<p>La luna aparecio sobre la ciudad como una herida abierta. Nadie hablaba en las calles, pero todas las ventanas tenian ojos.</p><p>Daniel sostuvo la carpeta contra el pecho y entendio que el expediente no era una investigacion. Era una invitacion.</p>', 'published'),
(2, 1, 2, 'Niebla en el puente', '<p>El puente viejo respiraba niebla. Cada paso hacia crujir el metal como si algo bajo el rio intentara responder.</p><p>Al fondo, una silueta levanto la mano y dejo caer una llave roja.</p>', 'published'),
(3, 2, 1, 'Protocolo cero', '<p>Cuando Mara desperto, el mensaje parpadeaba en su pantalla: corre antes de que tus aliados recuerden tu nombre.</p><p>No hubo tiempo para miedo. Solo para cargar el arma, quemar los documentos y saltar por la ventana.</p>', 'published');
