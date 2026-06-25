-- Fulde navn fra registreringsansøgning (brugernavn genereres fra e-mail)

ALTER TABLE users
    ADD COLUMN registration_display_name VARCHAR(255) NULL AFTER registration_type;
