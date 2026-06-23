-- Ret admin-adgangskode til admin123 (hvis seed-hash var forkert)
UPDATE users
SET password_hash = '$2y$10$9FGUS7MEwUvmHpY91XlkaewV.H09u0J.uJkXT88xNZ67CJAJSizHS',
    password_set_at = NOW()
WHERE username = 'admin' OR email = 'admin@trekantbrand.dk';
