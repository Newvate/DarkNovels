UPDATE users
SET name = 'Administrador DarkNovels',
    email = 'martinez1139mar@gmail.com',
    password = '$2y$10$vU4s5rGjn20Ik8sk17tR..7dOv57Y.x9/Q3CvE5wOuAB6XCQcFgM.',
    role = 'admin',
    status = 'active'
WHERE role = 'admin' OR email IN ('admin@darknovels.local', 'martinez1139mar@gmail', 'martinez1139mar@gmail.com');

INSERT INTO users (name, email, password, role, status)
SELECT 'Administrador DarkNovels',
       'martinez1139mar@gmail.com',
       '$2y$10$vU4s5rGjn20Ik8sk17tR..7dOv57Y.x9/Q3CvE5wOuAB6XCQcFgM.',
       'admin',
       'active'
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'martinez1139mar@gmail.com'
);
