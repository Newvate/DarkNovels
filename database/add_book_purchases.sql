CREATE TABLE IF NOT EXISTS book_purchases (
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
) ENGINE=InnoDB;
