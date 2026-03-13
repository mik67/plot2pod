USE plot2pod_com;

-- Generate a real hash first:
-- php -r "echo password_hash('yourpassword', PASSWORD_DEFAULT);"
-- Then replace the hash below.

INSERT INTO users (name, email, password_hash, is_admin)
VALUES (
    'Milos',
    'milos.mikulasek@plot2pod.com',
    '$2y$12$REPLACE_THIS_WITH_REAL_HASH',
    1
);
