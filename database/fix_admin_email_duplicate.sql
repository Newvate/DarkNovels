UPDATE users
SET name = 'Administrador DarkNovels',
    role = 'admin',
    status = 'active'
WHERE email = 'martinez1139mar@gmail.com';

UPDATE users
SET email = CONCAT('old-admin-', id, '@darknovels.local'),
    status = 'inactive'
WHERE email IN ('admin@darknovels.local', 'martinez1139mar@gmail');
