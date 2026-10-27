UPDATE users
SET email = 'martinez1139mar@gmail.com'
WHERE role = 'admin'
   OR email IN ('admin@darknovels.local', 'martinez1139mar@gmail');
