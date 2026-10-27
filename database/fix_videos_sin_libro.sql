SET @fk_name = (
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'videos'
      AND COLUMN_NAME = 'book_id'
      AND REFERENCED_TABLE_NAME IS NOT NULL
    LIMIT 1
);

SET @drop_fk_sql = IF(
    @fk_name IS NULL,
    'SELECT "No foreign key to drop" AS message',
    CONCAT('ALTER TABLE videos DROP FOREIGN KEY `', @fk_name, '`')
);

PREPARE drop_fk_stmt FROM @drop_fk_sql;
EXECUTE drop_fk_stmt;
DEALLOCATE PREPARE drop_fk_stmt;

ALTER TABLE videos MODIFY book_id INT UNSIGNED NULL DEFAULT NULL;

UPDATE videos SET book_id = NULL WHERE book_id = 0;
