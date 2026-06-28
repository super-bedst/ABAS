-- ABAS import fra trekantbrand_portal
-- Genereret: 2026-06-28T16:08:38+00:00
-- K├╕r mod tom/ny ABAS-database eller efter backup. Eksisterende r├ªkker med samme e-mail/dom├ªne springes over.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Godkendte installat├╕rer (virksomheder)
INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (1, 'Bravida Danmark A/S', 1, '2026-05-28 21:00:07', '2026-05-28 21:00:07')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 1, 'bravida.dk', '2026-05-28 21:00:07' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'bravida.dk');

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (2, 'Caverion Danmark A/S', 0, '2026-05-28 21:00:07', '2026-05-28 21:00:07')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 2, 'caverion.dk', '2026-05-28 21:00:07' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'caverion.dk');

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (3, 'Kemp & Lauritzen A/S', 1, '2026-05-28 21:00:07', '2026-05-28 21:00:07')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 3, 'kemp-lauritzen.dk', '2026-05-28 21:00:07' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'kemp-lauritzen.dk');

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (4, 'test', 1, '2026-05-28 22:08:22', '2026-05-28 22:08:22')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 4, 'gmail.com', '2026-05-28 22:08:22' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'gmail.com');

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (5, 'Falck Danmark A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 5, 'import-16271241.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-16271241.trekantbrand-import.local');
-- OBS: virksomhed 5 (Falck Danmark A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-16271241.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (6, 'Securitas Technology ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 6, 'import-15706708.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-15706708.trekantbrand-import.local');
-- OBS: virksomhed 6 (Securitas Technology ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-15706708.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (7, 'ABA Teknik ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 7, 'import-29778078.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-29778078.trekantbrand-import.local');
-- OBS: virksomhed 7 (ABA Teknik ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-29778078.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (8, 'Hoffmann Teknik', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 8, 'import-63030228.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-63030228.trekantbrand-import.local');
-- OBS: virksomhed 8 (Hoffmann Teknik) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-63030228.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (9, 'AG EL & Brandteknik ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 9, 'import-45183521.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-45183521.trekantbrand-import.local');
-- OBS: virksomhed 9 (AG EL & Brandteknik ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-45183521.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (10, 'Bravida Danmark A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 10, 'import-14769005.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-14769005.trekantbrand-import.local');
-- OBS: virksomhed 10 (Bravida Danmark A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-14769005.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (11, 'Andersen & Heegaard A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 11, 'import-18530902.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-18530902.trekantbrand-import.local');
-- OBS: virksomhed 11 (Andersen & Heegaard A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-18530902.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (12, 'JAMO Sikring A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 12, 'import-29540209.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-29540209.trekantbrand-import.local');
-- OBS: virksomhed 12 (JAMO Sikring A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-29540209.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (13, 'Projekt A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 13, 'import-36923679.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-36923679.trekantbrand-import.local');
-- OBS: virksomhed 13 (Projekt A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-36923679.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (14, 'G4S Security Services A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 14, 'import-26891280.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-26891280.trekantbrand-import.local');
-- OBS: virksomhed 14 (G4S Security Services A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-26891280.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (15, 'Wicotec Kirkebjerg A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 15, 'import-73585511.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-73585511.trekantbrand-import.local');
-- OBS: virksomhed 15 (Wicotec Kirkebjerg A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-73585511.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (16, 'Din El-Kontakt A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 16, 'import-33377789.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-33377789.trekantbrand-import.local');
-- OBS: virksomhed 16 (Din El-Kontakt A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-33377789.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (17, 'Micali El - Alarmgruppen A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 17, 'import-21815438.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-21815438.trekantbrand-import.local');
-- OBS: virksomhed 17 (Micali El - Alarmgruppen A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-21815438.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (18, 'Autronica Fire and Security A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 18, 'autronicagroup.com', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'autronicagroup.com');

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (19, 'Elsec - KIBO Sikring', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 19, 'import-67382110.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-67382110.trekantbrand-import.local');
-- OBS: virksomhed 19 (Elsec - KIBO Sikring) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-67382110.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (20, 'Kontakt ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 20, 'import-25362365.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-25362365.trekantbrand-import.local');
-- OBS: virksomhed 20 (Kontakt ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-25362365.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (21, 'WATS A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 21, 'wats.as', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'wats.as');

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (22, 'SIF Gruppen A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 22, 'import-46379918.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-46379918.trekantbrand-import.local');
-- OBS: virksomhed 22 (SIF Gruppen A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-46379918.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (23, '2M-El Installation A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 23, 'import-27017746.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-27017746.trekantbrand-import.local');
-- OBS: virksomhed 23 (2M-El Installation A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-27017746.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (24, 'Siemens A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 24, 'siemens.com', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'siemens.com');

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (25, 'Schneider Electric Buildings Denmark A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 25, 'import-15201894.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-15201894.trekantbrand-import.local');
-- OBS: virksomhed 25 (Schneider Electric Buildings Denmark A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-15201894.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (26, 'ECA Elektric ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 26, 'import-28103379.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-28103379.trekantbrand-import.local');
-- OBS: virksomhed 26 (ECA Elektric ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-28103379.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (27, 'El-Service ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 27, 'import-35388087.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-35388087.trekantbrand-import.local');
-- OBS: virksomhed 27 (El-Service ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-35388087.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (28, 'Str├╕m og Gudmundsson ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 28, 'import-36934964.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-36934964.trekantbrand-import.local');
-- OBS: virksomhed 28 (Str├╕m og Gudmundsson ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-36934964.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (29, 'Makenet A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 29, 'import-10047560.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-10047560.trekantbrand-import.local');
-- OBS: virksomhed 29 (Makenet A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-10047560.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (30, 'NCC Danmark A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 30, 'import-69894011.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-69894011.trekantbrand-import.local');
-- OBS: virksomhed 30 (NCC Danmark A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-69894011.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (31, 'E. Kalles├╕e A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 31, 'import-20013508.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-20013508.trekantbrand-import.local');
-- OBS: virksomhed 31 (E. Kalles├╕e A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-20013508.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (32, 'Detecta ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 32, 'import-45362027.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-45362027.trekantbrand-import.local');
-- OBS: virksomhed 32 (Detecta ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-45362027.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (33, 'Karsten Mortensen A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 33, 'import-20205474.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-20205474.trekantbrand-import.local');
-- OBS: virksomhed 33 (Karsten Mortensen A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-20205474.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (34, 'Leif Nielsen A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 34, 'import-34598517.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-34598517.trekantbrand-import.local');
-- OBS: virksomhed 34 (Leif Nielsen A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-34598517.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (35, 'Poul Sejr Nielsen El ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 35, 'import-33948476.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-33948476.trekantbrand-import.local');
-- OBS: virksomhed 35 (Poul Sejr Nielsen El ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-33948476.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (36, 'Hareskov Elektric A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 36, 'import-37244716.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-37244716.trekantbrand-import.local');
-- OBS: virksomhed 36 (Hareskov Elektric A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-37244716.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (37, 'Lelectric ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 37, 'el-tech.dk', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'el-tech.dk');

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (38, 'TECO Brand & Sikring', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 38, 'import-33050763.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-33050763.trekantbrand-import.local');
-- OBS: virksomhed 38 (TECO Brand & Sikring) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-33050763.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (39, 'EL-ABC A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 39, 'import-18491702.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-18491702.trekantbrand-import.local');
-- OBS: virksomhed 39 (EL-ABC A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-18491702.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (40, 'Alpha Electric A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 40, 'import-26904072.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-26904072.trekantbrand-import.local');
-- OBS: virksomhed 40 (Alpha Electric A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-26904072.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (41, 'Just Jensen A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 41, 'import-14844392.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-14844392.trekantbrand-import.local');
-- OBS: virksomhed 41 (Just Jensen A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-14844392.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (42, 'R├╕rskou El ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 42, 'import-39798476.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-39798476.trekantbrand-import.local');
-- OBS: virksomhed 42 (R├╕rskou El ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-39798476.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (43, 'Knudsker El', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 43, 'import-86395517.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-86395517.trekantbrand-import.local');
-- OBS: virksomhed 43 (Knudsker El) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-86395517.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (44, 'Axel S├╕rensen El A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 44, 'import-15473509.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-15473509.trekantbrand-import.local');
-- OBS: virksomhed 44 (Axel S├╕rensen El A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-15473509.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (45, 'CR Electric A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 45, 'import-37131415.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-37131415.trekantbrand-import.local');
-- OBS: virksomhed 45 (CR Electric A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-37131415.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (46, 'AJ Sikring og Teknik ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 46, 'import-40302832.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-40302832.trekantbrand-import.local');
-- OBS: virksomhed 46 (AJ Sikring og Teknik ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-40302832.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (47, 'Brandteknisk Service A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 47, 'import-41954396.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-41954396.trekantbrand-import.local');
-- OBS: virksomhed 47 (Brandteknisk Service A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-41954396.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (48, 'Consilium Safety Denmark A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 48, 'import-26903092.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-26903092.trekantbrand-import.local');
-- OBS: virksomhed 48 (Consilium Safety Denmark A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-26903092.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (49, 'Holmstrup & Mortensen A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 49, 'import-37197173.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-37197173.trekantbrand-import.local');
-- OBS: virksomhed 49 (Holmstrup & Mortensen A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-37197173.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (50, 'AA EL & Bygningsservice ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 50, 'import-43616455.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-43616455.trekantbrand-import.local');
-- OBS: virksomhed 50 (AA EL & Bygningsservice ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-43616455.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (51, 'Gelcom ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 51, 'import-35027467.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-35027467.trekantbrand-import.local');
-- OBS: virksomhed 51 (Gelcom ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-35027467.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (52, 'SABA Sikring ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 52, 'import-36018127.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-36018127.trekantbrand-import.local');
-- OBS: virksomhed 52 (SABA Sikring ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-36018127.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (53, 'Bagger L├Ñse & Alarm A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 53, 'import-27920004.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-27920004.trekantbrand-import.local');
-- OBS: virksomhed 53 (Bagger L├Ñse & Alarm A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-27920004.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (54, 'Eiland Sikkerhedssystemer', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 54, 'import-34249210.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-34249210.trekantbrand-import.local');
-- OBS: virksomhed 54 (Eiland Sikkerhedssystemer) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-34249210.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (55, 'Multi-Tech A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 55, 'import-28898894.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-28898894.trekantbrand-import.local');
-- OBS: virksomhed 55 (Multi-Tech A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-28898894.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (56, 'Dahl Service ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 56, 'import-39557266.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-39557266.trekantbrand-import.local');
-- OBS: virksomhed 56 (Dahl Service ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-39557266.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (57, 'DI-Teknik A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 57, 'import-27581781.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-27581781.trekantbrand-import.local');
-- OBS: virksomhed 57 (DI-Teknik A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-27581781.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (58, 'Actas A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 58, 'actas.dk', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'actas.dk');

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (59, 'Scheibel El & Sikring', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 59, 'import-38634399.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-38634399.trekantbrand-import.local');
-- OBS: virksomhed 59 (Scheibel El & Sikring) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-38634399.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (60, 'Rask El ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 60, 'import-20747994.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-20747994.trekantbrand-import.local');
-- OBS: virksomhed 60 (Rask El ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-20747994.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (61, 'ENELCO ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 61, 'import-19550036.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-19550036.trekantbrand-import.local');
-- OBS: virksomhed 61 (ENELCO ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-19550036.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (62, 'Aktiv El A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 62, 'import-21376906.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-21376906.trekantbrand-import.local');
-- OBS: virksomhed 62 (Aktiv El A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-21376906.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (63, '├ÿstergades El ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 63, 'import-33969031.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-33969031.trekantbrand-import.local');
-- OBS: virksomhed 63 (├ÿstergades El ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-33969031.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (64, 'Omega El og Sikring ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 64, 'import-44702576.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-44702576.trekantbrand-import.local');
-- OBS: virksomhed 64 (Omega El og Sikring ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-44702576.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (65, 'Secura ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 65, 'import-30807642.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-30807642.trekantbrand-import.local');
-- OBS: virksomhed 65 (Secura ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-30807642.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (66, 'El-Team Fyn A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 66, 'import-26570492.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-26570492.trekantbrand-import.local');
-- OBS: virksomhed 66 (El-Team Fyn A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-26570492.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (67, 'Dansk Sprinkler Teknik A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 67, 'dansksprinklerteknik.dk', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'dansksprinklerteknik.dk');

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (68, 'L-Brand & Teknik ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 68, 'import-35468676.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-35468676.trekantbrand-import.local');
-- OBS: virksomhed 68 (L-Brand & Teknik ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-35468676.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (69, 'Elpunkt Fyn A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 69, 'import-31090032.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-31090032.trekantbrand-import.local');
-- OBS: virksomhed 69 (Elpunkt Fyn A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-31090032.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (70, 'JK+ Alarmteknik A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 70, 'import-32144365.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-32144365.trekantbrand-import.local');
-- OBS: virksomhed 70 (JK+ Alarmteknik A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-32144365.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (71, 'Str├╕h A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 71, 'stp.dk', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'stp.dk');

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (72, 'Detekt ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 72, 'import-44313979.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-44313979.trekantbrand-import.local');
-- OBS: virksomhed 72 (Detekt ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-44313979.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (73, 'A.G. Electric A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 73, 'ag-electric.dk', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'ag-electric.dk');

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (74, 'Tekum ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 74, 'tekum.dk', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'tekum.dk');

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (75, 'Lotek A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 75, 'import-14291687.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-14291687.trekantbrand-import.local');
-- OBS: virksomhed 75 (Lotek A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-14291687.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (76, 'O&J Comfort A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 76, 'import-14182233.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-14182233.trekantbrand-import.local');
-- OBS: virksomhed 76 (O&J Comfort A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-14182233.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (77, 'Telesikring A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 77, 'telesikring.dk', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'telesikring.dk');

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (78, 'Caverion A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 78, 'caverion.com', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'caverion.com');

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (79, 'ZIPP systems ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 79, 'zippsystems.dk', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'zippsystems.dk');

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (80, 'Jansson Alarm A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 80, 'import-74783619.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-74783619.trekantbrand-import.local');
-- OBS: virksomhed 80 (Jansson Alarm A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-74783619.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (81, 'Str├╕m Hansen A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 81, 'import-10775345.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-10775345.trekantbrand-import.local');
-- OBS: virksomhed 81 (Str├╕m Hansen A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-10775345.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (82, 'Lund & Erichsen A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 82, 'import-16647586.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-16647586.trekantbrand-import.local');
-- OBS: virksomhed 82 (Lund & Erichsen A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-16647586.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (83, 'Knud Knudsen A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 83, 'import-44959712.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-44959712.trekantbrand-import.local');
-- OBS: virksomhed 83 (Knud Knudsen A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-44959712.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (84, 'Idom El-forretning ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 84, 'idomel.dk', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'idomel.dk');

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (85, 'Jysk Elteknik A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 85, 'import-56410910.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-56410910.trekantbrand-import.local');
-- OBS: virksomhed 85 (Jysk Elteknik A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-56410910.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (86, 'Vietz El-installation A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 86, 'import-12860609.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-12860609.trekantbrand-import.local');
-- OBS: virksomhed 86 (Vietz El-installation A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-12860609.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (87, 'ELCON A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 87, 'import-10074185.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-10074185.trekantbrand-import.local');
-- OBS: virksomhed 87 (ELCON A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-10074185.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (88, 'Securpro ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 88, 'import-30921666.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-30921666.trekantbrand-import.local');
-- OBS: virksomhed 88 (Securpro ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-30921666.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (89, 'Anker & Nygaard ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 89, 'import-28670931.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-28670931.trekantbrand-import.local');
-- OBS: virksomhed 89 (Anker & Nygaard ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-28670931.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (90, 'RE-L SIKRING IVS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 90, 'import-36968508.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-36968508.trekantbrand-import.local');
-- OBS: virksomhed 90 (RE-L SIKRING IVS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-36968508.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (91, 'ABS Alarm & Sikkerhed A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 91, 'import-18851881.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-18851881.trekantbrand-import.local');
-- OBS: virksomhed 91 (ABS Alarm & Sikkerhed A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-18851881.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (92, 'El-Team Vest A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 92, 'elteamvest.dk', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'elteamvest.dk');

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (93, 'T&E Teknik og El ApS', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 93, 'import-27017851.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-27017851.trekantbrand-import.local');
-- OBS: virksomhed 93 (T&E Teknik og El ApS) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-27017851.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (94, 'BE Installationer A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 94, 'import-10555787.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-10555787.trekantbrand-import.local');
-- OBS: virksomhed 94 (BE Installationer A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-10555787.trekantbrand-import.local

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (95, 'Mariendal El-Teknik A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 95, 'mariendal.dk', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'mariendal.dk');

INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)
VALUES (96, 'CBRE Teknisk Servicepartner A/S', 1, '2026-05-30 20:14:25', '2026-05-30 20:14:25')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active);
INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)
SELECT 96, 'import-31165563.trekantbrand-import.local', '2026-05-30 20:14:25' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE email_domain = 'import-31165563.trekantbrand-import.local');
-- OBS: virksomhed 96 (CBRE Teknisk Servicepartner A/S) havde tomt e-maildom├ªne ΓåÆ syntetisk dom├ªne import-31165563.trekantbrand-import.local

-- Virksomhedsadministratorer (portal users.rolle=firma_admin)
INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_display_name, password_set_at, access_confirmed_at, access_confirm_due_at, last_login_at, created_at)
VALUES ('nickyiversen@gmail.com', 'nickyiversen', NULL, 'virksomhedsadmin', '90909090', 4, 1, 'approved', 'nicky iversen', NULL, NULL, NULL, NULL, '2026-05-29 19:06:26')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), active = VALUES(active), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes (password_hash NULL): nickyiversen@gmail.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_display_name, password_set_at, access_confirmed_at, access_confirm_due_at, last_login_at, created_at)
VALUES ('ks@zippsystems.dk', 'ks', NULL, 'virksomhedsadmin', '92150155', 79, 1, 'approved', 'Karsten S├╕by', NULL, NULL, NULL, NULL, '2026-06-15 09:59:10')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), active = VALUES(active), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes (password_hash NULL): ks@zippsystems.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_display_name, password_set_at, access_confirmed_at, access_confirm_due_at, last_login_at, created_at)
VALUES ('jl@ag-electric.dk', 'jl', NULL, 'virksomhedsadmin', '24851191', 73, 1, 'approved', 'Jakob Langhoff', NULL, NULL, NULL, NULL, '2026-06-15 10:24:39')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), active = VALUES(active), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes (password_hash NULL): jl@ag-electric.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_display_name, password_set_at, access_confirmed_at, access_confirm_due_at, last_login_at, created_at)
VALUES ('jts@ag-electric.dk', 'jts', NULL, 'virksomhedsadmin', '51141317', 73, 1, 'approved', 'Jesper Toft Simonsen', NULL, NULL, NULL, NULL, '2026-06-15 10:25:12')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), active = VALUES(active), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes (password_hash NULL): jts@ag-electric.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_display_name, password_set_at, access_confirmed_at, access_confirm_due_at, last_login_at, created_at)
VALUES ('klaus.mikkelsen1@autronicagroup.com', 'klaus.mikkelsen1', NULL, 'virksomhedsadmin', '20804386', 18, 1, 'approved', 'Klaus Schmidt Mikkelsen', NULL, NULL, NULL, NULL, '2026-06-15 10:28:12')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), active = VALUES(active), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes (password_hash NULL): klaus.mikkelsen1@autronicagroup.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_display_name, password_set_at, access_confirmed_at, access_confirm_due_at, last_login_at, created_at)
VALUES ('em@stp.dk', 'em', NULL, 'virksomhedsadmin', '61620641', 71, 1, 'approved', 'Evan Mathiesen', NULL, NULL, NULL, NULL, '2026-06-15 10:32:49')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), active = VALUES(active), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes (password_hash NULL): em@stp.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_display_name, password_set_at, access_confirmed_at, access_confirm_due_at, last_login_at, created_at)
VALUES ('jeppe-ulff-moeller.nielsen@caverion.com', 'jeppe-ulff-moeller.nielsen', NULL, 'virksomhedsadmin', '21610202', 78, 1, 'approved', 'Jeppe Ulff-M├╕ller Nielsen', NULL, NULL, NULL, NULL, '2026-06-15 10:36:23')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), active = VALUES(active), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes (password_hash NULL): jeppe-ulff-moeller.nielsen@caverion.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_display_name, password_set_at, access_confirmed_at, access_confirm_due_at, last_login_at, created_at)
VALUES ('tks@mariendal.dk', 'tks', NULL, 'virksomhedsadmin', '25427774', 95, 1, 'approved', 'Torben Kroman Secher', NULL, NULL, NULL, NULL, '2026-06-15 10:38:53')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), active = VALUES(active), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes (password_hash NULL): tks@mariendal.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_display_name, password_set_at, access_confirmed_at, access_confirm_due_at, last_login_at, created_at)
VALUES ('kan@telesikring.dk', 'kan', NULL, 'virksomhedsadmin', '24885009', 77, 1, 'approved', 'Kasper Vandtved Andersen', NULL, NULL, NULL, NULL, '2026-06-15 10:42:37')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), active = VALUES(active), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes (password_hash NULL): kan@telesikring.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_display_name, password_set_at, access_confirmed_at, access_confirm_due_at, last_login_at, created_at)
VALUES ('ronni.sole@siemens.com', 'ronni.sole', NULL, 'virksomhedsadmin', '51502768', 24, 1, 'approved', 'Ronni Sole', NULL, NULL, NULL, NULL, '2026-06-15 10:47:39')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), active = VALUES(active), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes (password_hash NULL): ronni.sole@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_display_name, password_set_at, access_confirmed_at, access_confirm_due_at, last_login_at, created_at)
VALUES ('mm@idomel.dk', 'mm', NULL, 'virksomhedsadmin', '30907291', 84, 1, 'approved', 'Martin Kvistgaard Majlandt', NULL, NULL, NULL, NULL, '2026-06-15 10:56:17')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), active = VALUES(active), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes (password_hash NULL): mm@idomel.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_display_name, password_set_at, access_confirmed_at, access_confirm_due_at, last_login_at, created_at)
VALUES ('emt@wats.as', 'emt', NULL, 'virksomhedsadmin', '66443953', 21, 1, 'approved', 'Emil Tj├╕rnild', NULL, NULL, NULL, NULL, '2026-06-15 10:59:08')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), active = VALUES(active), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes (password_hash NULL): emt@wats.as

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_display_name, password_set_at, access_confirmed_at, access_confirm_due_at, last_login_at, created_at)
VALUES ('casper.billund@bravida.dk', 'casper.billund', NULL, 'virksomhedsadmin', '25253380', 1, 1, 'approved', 'Casper Billund', NULL, NULL, NULL, NULL, '2026-06-15 11:15:44')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), active = VALUES(active), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes (password_hash NULL): casper.billund@bravida.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_display_name, password_set_at, access_confirmed_at, access_confirm_due_at, last_login_at, created_at)
VALUES ('pfm@elteamvest.dk', 'pfm', NULL, 'virksomhedsadmin', '29220697', 92, 1, 'approved', 'Peter M├╕lgaard', NULL, NULL, NULL, NULL, '2026-06-15 11:29:44')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), active = VALUES(active), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes (password_hash NULL): pfm@elteamvest.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_display_name, password_set_at, access_confirmed_at, access_confirm_due_at, last_login_at, created_at)
VALUES ('th@el-tech.dk', 'th', NULL, 'virksomhedsadmin', '22163444', 37, 1, 'approved', 'Thomas H├╕jegaard', NULL, NULL, NULL, NULL, '2026-06-15 11:33:07')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), active = VALUES(active), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes (password_hash NULL): th@el-tech.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_display_name, password_set_at, access_confirmed_at, access_confirm_due_at, last_login_at, created_at)
VALUES ('hans-henrik.madsen@dansksprinklerteknik.dk', 'hans-henrik.madsen', NULL, 'virksomhedsadmin', '51959143', 67, 1, 'approved', 'Hans-Henrik Madsen', NULL, NULL, NULL, NULL, '2026-06-15 11:53:04')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), active = VALUES(active), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes (password_hash NULL): hans-henrik.madsen@dansksprinklerteknik.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_display_name, password_set_at, access_confirmed_at, access_confirm_due_at, last_login_at, created_at)
VALUES ('mkv@tekum.dk', 'mkv', NULL, 'virksomhedsadmin', '91340934', 74, 1, 'approved', 'Michael Vissing', NULL, NULL, NULL, NULL, '2026-06-15 12:00:37')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), active = VALUES(active), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes (password_hash NULL): mkv@tekum.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_display_name, password_set_at, access_confirmed_at, access_confirm_due_at, last_login_at, created_at)
VALUES ('jrj@actas.dk', 'jrj', NULL, 'virksomhedsadmin', '23333555', 58, 1, 'approved', 'Jacob Rose Rodriguez J├╕rgensen', NULL, NULL, NULL, NULL, '2026-06-15 12:03:08')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), active = VALUES(active), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes (password_hash NULL): jrj@actas.dk

-- TrekantBrand medarbejdere (portal users.rolle=medarbejder ΓåÆ vagtcentral)
-- Godkendte mont├╕rer (montor_anmodninger.status=oprettet)
INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('nickyiversen@gmail.com', 'test2', NULL, 'montor', '123456778', 4, 1, 'approved', 'montor', 'Nicky Iversen', '2026-05-30 19:49:38', NULL, NULL, NULL, '2026-05-30 19:49:38')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: nickyiversen@gmail.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('ronni.sole@siemens.com', 'MON05', NULL, 'montor', '51502768', 24, 1, 'approved', 'montor', 'Ronni Sole', '2026-06-17 10:57:52', NULL, NULL, NULL, '2026-06-17 10:57:52')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: ronni.sole@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('rene.soerensen@siemens.com', 'MON06', NULL, 'montor', '20212075', 24, 1, 'approved', 'montor', 'Ren├⌐ S├╕rensen', '2026-06-17 11:00:14', NULL, NULL, NULL, '2026-06-17 11:00:14')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: rene.soerensen@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('allan.lindrup@siemens.com', 'MON07', NULL, 'montor', '30942012', 24, 1, 'approved', 'montor', 'Allan Lindrup', '2026-06-17 11:02:37', NULL, NULL, NULL, '2026-06-17 11:02:37')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: allan.lindrup@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('bo.christensen@siemens.com', 'MON08', NULL, 'montor', '21690744', 24, 1, 'approved', 'montor', 'Bo Christensen', '2026-06-17 11:04:31', NULL, NULL, NULL, '2026-06-17 11:04:31')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: bo.christensen@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('louise.wiggers@siemens.com', 'MON09', NULL, 'montor', '23656242', 24, 1, 'approved', 'montor', 'Louise Friborg Wiggers', '2026-06-17 11:06:33', NULL, NULL, NULL, '2026-06-17 11:06:33')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: louise.wiggers@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('troels.jansen@siemens.com', 'MON10', NULL, 'montor', '40185181', 24, 1, 'approved', 'montor', 'Troels Jansen', '2026-06-17 11:09:14', NULL, NULL, NULL, '2026-06-17 11:09:14')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: troels.jansen@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('torben.krogh@siemens.com', 'MON11', NULL, 'montor', '23726798', 24, 1, 'approved', 'montor', 'Torben Skaaning Krogh', '2026-06-17 11:11:23', NULL, NULL, NULL, '2026-06-17 11:11:23')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: torben.krogh@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('martin.t.nielsen@siemens.com', 'MON12', NULL, 'montor', '21834974', 24, 1, 'approved', 'montor', 'Martin Thorbj├╕rn Fischer', '2026-06-17 11:14:29', NULL, NULL, NULL, '2026-06-17 11:14:29')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: martin.t.nielsen@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('henrik.christiansen@siemens.com', 'MON13', NULL, 'montor', '24626978', 24, 1, 'approved', 'montor', 'Henrik Christiansen', '2026-06-17 11:16:50', NULL, NULL, NULL, '2026-06-17 11:16:50')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: henrik.christiansen@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('peter.noerregaard@siemens.com', 'MON14', NULL, 'montor', '23239077', 24, 1, 'approved', 'montor', 'Peter N├╕rregaard', '2026-06-17 11:21:21', NULL, NULL, NULL, '2026-06-17 11:21:21')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: peter.noerregaard@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('tim.n.poulsen@siemens.com', 'MON15', NULL, 'montor', '21190861', 24, 1, 'approved', 'montor', 'Tim Nymark Poulsen', '2026-06-17 11:24:03', NULL, NULL, NULL, '2026-06-17 11:24:03')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: tim.n.poulsen@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('johnny.larsen@siemens.com', 'MON16', NULL, 'montor', '23239075', 24, 1, 'approved', 'montor', 'Johnny Larsen', '2026-06-17 11:26:02', NULL, NULL, NULL, '2026-06-17 11:26:02')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: johnny.larsen@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('johannes.nielsen@siemens.com', 'MON17', NULL, 'montor', '23655831', 24, 1, 'approved', 'montor', 'Johannes M├╕ller Nielsen', '2026-06-17 11:28:14', NULL, NULL, NULL, '2026-06-17 11:28:14')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: johannes.nielsen@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('torben.enemark@siemens.com', 'MON18', NULL, 'montor', '23349129', 24, 1, 'approved', 'montor', 'Torben Scott Enemark', '2026-06-17 11:30:13', NULL, NULL, NULL, '2026-06-17 11:30:13')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: torben.enemark@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('kristian.olsen@siemens.com', 'MON04', NULL, 'montor', '51148727', 24, 1, 'approved', 'montor', 'Kristian Emil Olsen', '2026-06-17 10:53:58', NULL, NULL, NULL, '2026-06-17 10:53:58')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: kristian.olsen@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('flemming.flebbe@siemens.com', 'MON03', NULL, 'montor', '21299374', 24, 1, 'approved', 'montor', 'Flemming H├╕jgaard Flebbe', '2026-06-17 10:51:17', NULL, NULL, NULL, '2026-06-17 10:51:17')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: flemming.flebbe@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('sulejman.topic@siemens.com', 'MON02', NULL, 'montor', '23297762', 24, 1, 'approved', 'montor', 'Sulejman Topic', '2026-06-17 10:46:18', NULL, NULL, NULL, '2026-06-17 10:46:18')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: sulejman.topic@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('puica.gabriel@siemens.com', 'MON01', NULL, 'montor', '23461741', 24, 1, 'approved', 'montor', 'Costel Gabriel Puica', '2026-06-17 10:43:24', NULL, NULL, NULL, '2026-06-17 10:43:24')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: puica.gabriel@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('ahmed.chaabi@siemens.com', 'MON19', NULL, 'montor', '23994443', 24, 1, 'approved', 'montor', 'Ahmed Chaabi', '2026-06-17 11:36:12', NULL, NULL, NULL, '2026-06-17 11:36:12')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: ahmed.chaabi@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('morten.iversen@siemens.com', 'MON20', NULL, 'montor', '61167895', 24, 1, 'approved', 'montor', 'Morten Taanum Iversen', '2026-06-17 11:38:15', NULL, NULL, NULL, '2026-06-17 11:38:15')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: morten.iversen@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('tommy.andersen@siemens.com', 'MON21', NULL, 'montor', '21686319', 24, 1, 'approved', 'montor', 'Tommy Andersen', '2026-06-17 11:40:15', NULL, NULL, NULL, '2026-06-17 11:40:15')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: tommy.andersen@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('kimtruelsen@siemens.com', 'MON22', NULL, 'montor', '23239074', 24, 1, 'approved', 'montor', 'Kim Truelsen', '2026-06-17 11:42:09', NULL, NULL, NULL, '2026-06-17 11:42:09')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: kimtruelsen@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('fleming.olsen@siemens.com', 'MON23', NULL, 'montor', '29359118', 24, 1, 'approved', 'montor', 'Flemming Rine Olsen', '2026-06-17 11:44:10', NULL, NULL, NULL, '2026-06-17 11:44:10')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: fleming.olsen@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('kurt.mikkelsen@siemens.com', 'MON24', NULL, 'montor', '21644963', 24, 1, 'approved', 'montor', 'Kurt Mikkelsen', '2026-06-17 11:46:07', NULL, NULL, NULL, '2026-06-17 11:46:07')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: kurt.mikkelsen@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('carsten.hojgaard@siemens.com', 'MON25', NULL, 'montor', '24650220', 24, 1, 'approved', 'montor', 'Carsten H├╕jgaard', '2026-06-17 11:48:03', NULL, NULL, NULL, '2026-06-17 11:48:03')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: carsten.hojgaard@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('frederik.risgaard@siemens.com', 'MON26', NULL, 'montor', '21631910', 24, 1, 'approved', 'montor', 'Frederik Risgaard', '2026-06-17 11:49:59', NULL, NULL, NULL, '2026-06-17 11:49:59')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: frederik.risgaard@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('karsten.tavs@siemens.com', 'MON27', NULL, 'montor', '23816008', 24, 1, 'approved', 'montor', 'Kartsen Tavs', '2026-06-17 11:51:40', NULL, NULL, NULL, '2026-06-17 11:51:40')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: karsten.tavs@siemens.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('em@stp.dk', 'MON29', NULL, 'montor', '61620641', 71, 1, 'approved', 'montor', 'Evan Mathiesen', '2026-06-17 16:48:31', NULL, NULL, NULL, '2026-06-17 16:48:31')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: em@stp.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('cje@stp.dk', 'MON30', NULL, 'montor', '25278676', 71, 1, 'approved', 'montor', 'Claus Eskildsen', '2026-06-17 16:48:43', NULL, NULL, NULL, '2026-06-17 16:48:43')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: cje@stp.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('mjj@stp.dk', 'MON31', NULL, 'montor', '25278668', 71, 1, 'approved', 'montor', 'Mikael J J├╕rgensen', '2026-06-17 16:49:02', NULL, NULL, NULL, '2026-06-17 16:49:02')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: mjj@stp.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('cht@stp.dk', 'MON32', NULL, 'montor', '61620640', 71, 1, 'approved', 'montor', 'Christian Tagesen', '2026-06-17 16:49:19', NULL, NULL, NULL, '2026-06-17 16:49:19')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: cht@stp.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('jje@actas.dk', 'MON33', NULL, 'montor', '+45 23 33 35 96', 58, 1, 'approved', 'montor', 'J├╕rgen Jensen', '2026-06-17 15:03:20', NULL, NULL, NULL, '2026-06-17 15:03:20')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: jje@actas.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('jrj@actas.dk', 'MON34', NULL, 'montor', '+45 23 33 35 55', 58, 1, 'approved', 'montor', 'Jacob J├╕rgensen', '2026-06-17 15:05:19', NULL, NULL, NULL, '2026-06-17 15:05:19')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: jrj@actas.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('ola@actas.dk', 'MON35', NULL, 'montor', '+45 40 80 83 81', 58, 1, 'approved', 'montor', 'Oliver Lagoni', '2026-06-17 15:07:51', NULL, NULL, NULL, '2026-06-17 15:07:51')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: ola@actas.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('joern.v.kristensen@caverion.com', 'MON36', NULL, 'montor', '+45 61 89 58 88', 78, 1, 'approved', 'montor', 'J├╕rn Vestergaard Kristensen', '2026-06-17 15:09:48', NULL, NULL, NULL, '2026-06-17 15:09:48')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: joern.v.kristensen@caverion.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('dennis.jensen@caverion.com', 'MON37', NULL, 'montor', '+45 20 18 40 42', 78, 1, 'approved', 'montor', 'Dennis Jensen', '2026-06-17 15:11:53', NULL, NULL, NULL, '2026-06-17 15:11:53')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: dennis.jensen@caverion.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('thorbjoern.falk@caverion.com', 'MON38', NULL, 'montor', '+45 61 89 59 11', 78, 1, 'approved', 'montor', 'Thorbj├╕rn Falk', '2026-06-17 15:16:06', NULL, NULL, NULL, '2026-06-17 15:16:06')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: thorbjoern.falk@caverion.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('nikolaj.jensen@caverion.com', 'MON39', NULL, 'montor', '+45 40 80 41 53', 78, 1, 'approved', 'montor', 'Nikolaj Nyborg Beck Jensen', '2026-06-17 15:18:16', NULL, NULL, NULL, '2026-06-17 15:18:16')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: nikolaj.jensen@caverion.com

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('hbl@zippsystems.dk', 'MON40', NULL, 'montor', '92150153', 79, 1, 'approved', 'montor', 'Henrik Bisgaard Laursen', '2026-06-17 15:21:17', NULL, NULL, NULL, '2026-06-17 15:21:17')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: hbl@zippsystems.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('kad@zippsystems.dk', 'MON41', NULL, 'montor', '92150152', 79, 1, 'approved', 'montor', 'Kaspar Adamsen', '2026-06-17 15:23:21', NULL, NULL, NULL, '2026-06-17 15:23:21')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: kad@zippsystems.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('hba@zippsystems.dk', 'MON42', NULL, 'montor', '92150157', 79, 1, 'approved', 'montor', 'Henrik Bach', '2026-06-17 15:25:05', NULL, NULL, NULL, '2026-06-17 15:25:05')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: hba@zippsystems.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('jl@ag-electric.dk', 'MON43', NULL, 'montor', '24851191', 73, 1, 'approved', 'montor', 'Jakob Langhoff', '2026-06-17 15:35:18', NULL, NULL, NULL, '2026-06-17 15:35:18')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: jl@ag-electric.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('sep@ag-electric.dk', 'MON46', NULL, 'montor', '21223707', 73, 1, 'approved', 'montor', 'Sebastian Brodersen', '2026-06-17 15:44:25', NULL, NULL, NULL, '2026-06-17 15:44:25')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: sep@ag-electric.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('jcc@ag-electric.dk', 'MON47', NULL, 'montor', '21221252', 73, 1, 'approved', 'montor', 'Jens Christian Ravnholt Cramer', '2026-06-17 15:46:23', NULL, NULL, NULL, '2026-06-17 15:46:23')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: jcc@ag-electric.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('fys@ag-electric.dk', 'MON48', NULL, 'montor', '23211033', 73, 1, 'approved', 'montor', 'Fylkir S├ªvarsson', '2026-06-17 15:50:49', NULL, NULL, NULL, '2026-06-17 15:50:49')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: fys@ag-electric.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('peder.skelskov@bravida.dk', 'MON28', NULL, 'montor', '+4525254119', 1, 1, 'approved', 'montor', 'Peder skelskov', '2026-06-17 11:53:17', NULL, NULL, NULL, '2026-06-17 11:53:17')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: peder.skelskov@bravida.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('cja@elteamvest.dk', 'MON49', NULL, 'montor', '29220698', 92, 1, 'approved', 'montor', 'Carsten Juhl Andersen', '2026-06-17 15:54:13', NULL, NULL, NULL, '2026-06-17 15:54:13')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: cja@elteamvest.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('cgl@elteamvest.dk', 'MON50', NULL, 'montor', '20908926', 92, 1, 'approved', 'montor', 'Claus Grav Lange', '2026-06-17 15:57:10', NULL, NULL, NULL, '2026-06-17 15:57:10')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: cgl@elteamvest.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('cpl@elteamvest.dk', 'MON51', NULL, 'montor', '29906782', 92, 1, 'approved', 'montor', 'Claus Poulin', '2026-06-17 15:58:55', NULL, NULL, NULL, '2026-06-17 15:58:55')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: cpl@elteamvest.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('elt@elteamvest.dk', 'MON52', NULL, 'montor', '22230417', 92, 1, 'approved', 'montor', 'Emil Lykke Thulstrup', '2026-06-17 16:02:38', NULL, NULL, NULL, '2026-06-17 16:02:38')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: elt@elteamvest.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('jtr@elteamvest.dk', 'MON53', NULL, 'montor', '29220692', 92, 1, 'approved', 'montor', 'Jesper Traberg', '2026-06-17 16:04:11', NULL, NULL, NULL, '2026-06-17 16:04:11')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: jtr@elteamvest.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('knr@elteamvest.dk', 'MON54', NULL, 'montor', '29908542', 92, 1, 'approved', 'montor', 'Kristian Nervik R├╕dby', '2026-06-17 16:05:50', NULL, NULL, NULL, '2026-06-17 16:05:50')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: knr@elteamvest.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('mjo@elteamvest.dk', 'MON55', NULL, 'montor', '29220696', 92, 1, 'approved', 'montor', 'Michael Johansen', '2026-06-17 16:07:27', NULL, NULL, NULL, '2026-06-17 16:07:27')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: mjo@elteamvest.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('mim@elteamvest.dk', 'MON56', NULL, 'montor', '20636772', 92, 1, 'approved', 'montor', 'Michal Majewski', '2026-06-17 16:08:57', NULL, NULL, NULL, '2026-06-17 16:08:57')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: mim@elteamvest.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('trl@elteamvest.dk', 'MON57', NULL, 'montor', '29220686', 92, 1, 'approved', 'montor', 'Thomas Rosenbech Lund', '2026-06-17 16:10:30', NULL, NULL, NULL, '2026-06-17 16:10:30')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: trl@elteamvest.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('mbr@telesikring.dk', 'MON64', NULL, 'montor', '20417594', 77, 1, 'approved', 'montor', 'Michael Br├╝gmann', '2026-06-23 08:05:08', NULL, NULL, NULL, '2026-06-23 08:05:08')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: mbr@telesikring.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('dfr@telesikring.dk', 'MON63', NULL, 'montor', '24885004', 77, 1, 'approved', 'montor', 'Dan Frederiksen', '2026-06-23 08:02:25', NULL, NULL, NULL, '2026-06-23 08:02:25')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: dfr@telesikring.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('acr@telesikring.dk', 'MON62', NULL, 'montor', '24882904', 77, 1, 'approved', 'montor', 'Astrid Ross', '2026-06-23 07:59:49', NULL, NULL, NULL, '2026-06-23 07:59:49')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: acr@telesikring.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('soren.steenberg@bravida.dk', 'MON61', NULL, 'montor', '+4525253300', 1, 1, 'approved', 'montor', 'S├╕ren S Pedersen', '2026-06-23 07:48:19', NULL, NULL, NULL, '2026-06-23 07:48:19')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: soren.steenberg@bravida.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('h.knudsen@bravida.dk', 'MON60', NULL, 'montor', '+4525253019', 1, 1, 'approved', 'montor', 'Henrik Knudsen', '2026-06-23 07:42:31', NULL, NULL, NULL, '2026-06-23 07:42:31')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: h.knudsen@bravida.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('paw.n.vestergaard@bravida.dk', 'MON59', NULL, 'montor', '+4525253000', 1, 1, 'approved', 'montor', 'Paw Nors Vestergaard', '2026-06-23 07:40:11', NULL, NULL, NULL, '2026-06-23 07:40:11')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: paw.n.vestergaard@bravida.dk

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)
VALUES ('birthe.petersen@bravida.dk', 'MON58', NULL, 'montor', '25253480', 1, 1, 'approved', 'montor', 'birthe.petersen@bravida.dk', '2026-06-23 07:35:35', NULL, NULL, NULL, '2026-06-23 07:35:35')
ON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);
-- Velkomst-mail skal sendes: birthe.petersen@bravida.dk

-- Afventende mont├╕r-ans├╕gninger (montor_anmodninger.status=afventer)
INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_requested_at, created_at)
VALUES ('ole@tekum.dk', 'ole', NULL, 'montor', '+4591340936', 74, 0, 'pending', 'montor', 'Ole Eskildsen', '2026-06-18 07:51:25', '2026-06-18 07:51:25')
ON DUPLICATE KEY UPDATE registration_status = 'pending', installer_id = VALUES(installer_id);

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_requested_at, created_at)
VALUES ('mkv@tekum.dk', 'mkv1', NULL, 'montor', '+4591340934', 74, 0, 'pending', 'montor', 'Michael Vissing', '2026-06-18 07:57:32', '2026-06-18 07:57:32')
ON DUPLICATE KEY UPDATE registration_status = 'pending', installer_id = VALUES(installer_id);

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_requested_at, created_at)
VALUES ('michael.milo@bravida.dk', 'michael.milo', NULL, 'montor', '+45 25 25 12 40', 1, 0, 'pending', 'montor', 'Michael Lund Milo', '2026-06-24 10:47:17', '2026-06-24 10:47:17')
ON DUPLICATE KEY UPDATE registration_status = 'pending', installer_id = VALUES(installer_id);

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_requested_at, created_at)
VALUES ('henrik.thingholm@bravida.dk', 'henrik.thingholm', NULL, 'montor', '25253881', 1, 0, 'pending', 'montor', 'Henrik Thingholm', '2026-06-24 13:11:44', '2026-06-24 13:11:44')
ON DUPLICATE KEY UPDATE registration_status = 'pending', installer_id = VALUES(installer_id);

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_requested_at, created_at)
VALUES ('casper.billund@bravida.dk', 'casper.billund1', NULL, 'montor', '+4525253380', 1, 0, 'pending', 'montor', 'Casper Billund', '2026-06-24 15:21:33', '2026-06-24 15:21:33')
ON DUPLICATE KEY UPDATE registration_status = 'pending', installer_id = VALUES(installer_id);

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_requested_at, created_at)
VALUES ('casper.gregorius@bravida.dk', 'casper.gregorius', NULL, 'montor', '+4525253072', 1, 0, 'pending', 'montor', 'Casper Gregorius', '2026-06-25 08:39:24', '2026-06-25 08:39:24')
ON DUPLICATE KEY UPDATE registration_status = 'pending', installer_id = VALUES(installer_id);

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_requested_at, created_at)
VALUES ('jes.asmussen@bravida.dk', 'jes.asmussen', NULL, 'montor', '+452522211', 1, 0, 'pending', 'montor', 'Jes Asmussen', '2026-06-25 13:33:44', '2026-06-25 13:33:44')
ON DUPLICATE KEY UPDATE registration_status = 'pending', installer_id = VALUES(installer_id);

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_requested_at, created_at)
VALUES ('mamo.khalil@bravida.dk', 'mamo.khalil', NULL, 'montor', '25254440', 1, 0, 'pending', 'montor', 'Mamo Khalil', '2026-06-25 13:37:21', '2026-06-25 13:37:21')
ON DUPLICATE KEY UPDATE registration_status = 'pending', installer_id = VALUES(installer_id);

INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_requested_at, created_at)
VALUES ('andy.vilhelmsen@bravida.dk', 'andy.vilhelmsen', NULL, 'montor', '+4525259933', 1, 0, 'pending', 'montor', 'Andy Vilhelmsen', '2026-06-26 13:18:46', '2026-06-26 13:18:46')
ON DUPLICATE KEY UPDATE registration_status = 'pending', installer_id = VALUES(installer_id);

-- Portal audit_logs ΓåÆ activity_events (kr├ªver migration 008_activity_events.sql)
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '10.181.140.254', 'portal_import', '2026-05-28 19:55:11');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'logout', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '10.181.140.254', 'portal_import', '2026-05-28 20:12:40');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '10.181.140.254', 'portal_import', '2026-05-28 20:13:11');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'logout', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '10.181.140.254', 'portal_import', '2026-05-28 20:56:21');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Test Admin', 'auth', 'login', 'App\\Models\\User', '2', 'Test Admin', NULL, '10.181.140.254', 'portal_import', '2026-05-28 20:58:16');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Test Admin', 'auth', 'login', 'App\\Models\\User', '2', 'Test Admin', NULL, '10.181.140.254', 'portal_import', '2026-05-28 21:03:58');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Test Admin', 'auth', 'login', 'App\\Models\\User', '2', 'Test Admin', NULL, '10.181.140.254', 'portal_import', '2026-05-28 21:06:07');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Test Admin', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '1', 'Test Testesen (Bravida Danmark A/S)', '{"email": "test@bravida.dk"}', '10.181.140.254', 'portal_import', '2026-05-28 21:07:49');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Test Admin', 'auth', 'logout', 'App\\Models\\User', '2', 'Test Admin', NULL, '10.181.140.254', 'portal_import', '2026-05-28 21:11:27');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '10.181.140.254', 'portal_import', '2026-05-28 21:12:14');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'logout', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '10.181.140.254', 'portal_import', '2026-05-28 21:39:45');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '2', 'test testesen2 (Bravida Danmark A/S)', '{"email": "test2@bravida.dk"}', '10.181.140.254', 'portal_import', '2026-05-28 21:40:24');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '10.181.140.254', 'portal_import', '2026-05-28 21:40:56');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'system', 'virksomhed_oprettet', 'App\\Models\\Virksomhed', '4', 'test', NULL, '10.181.140.254', 'portal_import', '2026-05-28 22:08:22');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '3', 'Nicky Testesen (test)', '{"email": "nickyiversen@gmail.com"}', '10.181.140.254', 'portal_import', '2026-05-28 22:08:59');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'registration', 'rejected', 'App\\Models\\MontorAnmodning', '3', 'Nicky Testesen (test)', '{"begrundelse": "Forkert firma"}', '10.181.140.254', 'portal_import', '2026-05-28 22:27:38');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '4', 'Nicky Iversen (test)', '{"email": "nickyiversen@gmail.com"}', '10.181.140.254', 'portal_import', '2026-05-28 22:28:26');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'registration', 'rejected', 'App\\Models\\MontorAnmodning', '4', 'Nicky Iversen (test)', '{"begrundelse": "forkert firma"}', '10.181.140.254', 'portal_import', '2026-05-28 22:31:35');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '5', 'Nicky Iversen (test)', '{"email": "nickyiversen@gmail.com"}', '10.181.140.254', 'portal_import', '2026-05-28 22:32:18');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '5', 'Nicky Iversen (test)', NULL, '10.181.140.254', 'portal_import', '2026-05-28 22:44:07');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'deleted', NULL, NULL, 'Nicky Iversen (test)', NULL, '10.181.140.254', 'portal_import', '2026-05-28 22:56:30');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'deleted', NULL, NULL, 'Nicky Testesen (test)', NULL, '10.181.140.254', 'portal_import', '2026-05-28 22:56:38');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'deleted', NULL, NULL, 'test testesen2 (Bravida Danmark A/S)', NULL, '10.181.140.254', 'portal_import', '2026-05-28 22:56:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'deleted', NULL, NULL, 'Test Testesen (Bravida Danmark A/S)', NULL, '10.181.140.254', 'portal_import', '2026-05-28 22:56:48');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Test Admin', 'auth', 'login', 'App\\Models\\User', '2', 'Test Admin', NULL, '10.181.140.254', 'portal_import', '2026-05-29 11:46:13');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Test Admin', 'auth', 'logout', 'App\\Models\\User', '2', 'Test Admin', NULL, '10.181.140.254', 'portal_import', '2026-05-29 11:57:30');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '10.181.140.254', 'portal_import', '2026-05-29 12:20:18');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\User', '4', 'Nicky Iversen (test)', NULL, '10.181.140.254', 'portal_import', '2026-05-29 12:22:45');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'logout', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '10.181.140.254', 'portal_import', '2026-05-29 12:25:54');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '6', 'Nicky Iversen (test)', '{"email": "nickyiversen@gmail.com"}', '10.181.140.254', 'portal_import', '2026-05-29 12:26:15');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-29 13:41:54');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '6', 'Nicky Iversen (test)', NULL, '188.228.103.132', 'portal_import', '2026-05-29 13:42:26');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'logout', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-29 13:46:37');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '7', 'nicky iversen (test)', '{"email": "nickyiversen@gmail.com"}', '188.228.103.132', 'portal_import', '2026-05-29 13:47:12');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-29 13:48:01');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '7', 'nicky iversen (test)', NULL, '188.228.103.132', 'portal_import', '2026-05-29 13:48:19');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'logout', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-29 13:48:58');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '8', 'nicky iversen (test)', '{"email": "nickyiversen@gmail.com"}', '188.228.103.132', 'portal_import', '2026-05-29 13:49:19');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-29 13:49:35');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '8', 'nicky iversen (test)', NULL, '188.228.103.132', 'portal_import', '2026-05-29 13:49:56');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'logout', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-29 13:51:38');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '9', 'Nicky Iversen (test)', '{"email": "nickyiversen@gmail.com"}', '188.228.103.132', 'portal_import', '2026-05-29 13:51:57');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-29 13:52:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '9', 'Nicky Iversen (test)', NULL, '188.228.103.132', 'portal_import', '2026-05-29 13:53:05');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'logout', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-29 13:54:33');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '10', 'Nicky iversen 2 (test)', '{"email": "nickyiversen@gmail.com"}', '188.228.103.132', 'portal_import', '2026-05-29 13:54:50');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-29 13:55:30');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '10', 'Nicky iversen 2 (test)', NULL, '188.228.103.132', 'portal_import', '2026-05-29 13:55:53');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'logout', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-29 13:59:04');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '11', 'Nicky iversen 22 (test)', '{"email": "nickyiversen@gmail.com"}', '188.228.103.132', 'portal_import', '2026-05-29 13:59:19');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-29 13:59:51');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '11', 'Nicky iversen 22 (test)', NULL, '188.228.103.132', 'portal_import', '2026-05-29 14:00:13');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'logout', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-29 14:04:32');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '12', 'nicky ivversen 55 (test)', '{"email": "nickyiversen@gmail.com"}', '188.228.103.132', 'portal_import', '2026-05-29 14:04:51');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-29 14:05:20');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '12', 'nicky ivversen 55 (test)', NULL, '188.228.103.132', 'portal_import', '2026-05-29 14:05:38');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'deleted', NULL, NULL, 'nicky ivversen 55 (test)', NULL, '188.228.103.132', 'portal_import', '2026-05-29 14:07:30');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-29 16:13:20');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'logout', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-29 17:06:42');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '13', 'nicky iversen (test)', '{"email": "nickyiversen@gmail.com"}', '188.228.103.132', 'portal_import', '2026-05-29 17:15:01');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-29 17:15:55');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '13', 'nicky iversen (test)', NULL, '188.228.103.132', 'portal_import', '2026-05-29 17:18:12');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'updated', 'App\\Models\\User', '4', 'Nicky Iversen (test)', NULL, '188.228.103.132', 'portal_import', '2026-05-29 17:19:05');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'system', 'pdf_vejledning_opdateret', NULL, NULL, NULL, NULL, '188.228.103.132', 'portal_import', '2026-05-29 17:21:17');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'updated', 'App\\Models\\User', '4', 'Nicky Iversen (test)', NULL, '188.228.103.132', 'portal_import', '2026-05-29 19:02:41');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'deleted', 'App\\Models\\User', '4', 'Nicky Iversen (test)', NULL, '188.228.103.132', 'portal_import', '2026-05-29 19:06:08');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\User', '5', 'nicky iversen (test)', NULL, '188.228.103.132', 'portal_import', '2026-05-29 19:06:26');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'nicky iversen', 'auth', 'login', 'App\\Models\\User', '5', 'nicky iversen', NULL, '188.228.103.132', 'portal_import', '2026-05-29 19:07:13');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'logout', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-29 19:07:44');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'nicky iversen', 'auth', 'login', 'App\\Models\\User', '5', 'nicky iversen', NULL, '188.228.103.132', 'portal_import', '2026-05-29 19:08:14');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'nicky iversen', 'auth', 'logout', 'App\\Models\\User', '5', 'nicky iversen', NULL, '188.228.103.132', 'portal_import', '2026-05-29 21:01:39');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-29 21:02:14');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'system', 'firma_admin_nyt_password', 'App\\Models\\User', '5', 'nicky iversen (test)', NULL, '188.228.103.132', 'portal_import', '2026-05-29 21:02:41');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'nicky iversen', 'auth', 'logout', 'App\\Models\\User', '5', 'nicky iversen', NULL, '188.228.103.132', 'portal_import', '2026-05-29 21:03:21');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'nicky iversen', 'auth', 'login', 'App\\Models\\User', '5', 'nicky iversen', NULL, '188.228.103.132', 'portal_import', '2026-05-29 21:03:36');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'deleted', NULL, NULL, 'nicky iversen (test)', NULL, '188.228.103.132', 'portal_import', '2026-05-29 21:05:39');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'deleted', NULL, NULL, 'Nicky Iversen (test)', NULL, '188.228.103.132', 'portal_import', '2026-05-29 21:12:42');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'deleted', NULL, NULL, 'Nicky Iversen (test)', NULL, '188.228.103.132', 'portal_import', '2026-05-29 21:15:16');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'logout', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-29 21:19:09');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'nicky iversen', 'auth', 'login', 'App\\Models\\User', '5', 'nicky iversen', NULL, '188.228.103.132', 'portal_import', '2026-05-29 21:19:37');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'nicky iversen', 'system', 'nedlaeggelse_anmodet', 'App\\Models\\MontorAnmodning', '11', 'Nicky iversen 22 (test)', '{"begrundelse": "fyret"}', '188.228.103.132', 'portal_import', '2026-05-29 21:23:25');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-29 21:24:08');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'system', 'montor_nedlagt', 'App\\Models\\MontorAnmodning', '11', 'Nicky iversen 22 (test)', NULL, '188.228.103.132', 'portal_import', '2026-05-29 21:24:26');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'nicky iversen', 'auth', 'login', 'App\\Models\\User', '5', 'nicky iversen', NULL, '93.165.253.15', 'portal_import', '2026-05-29 22:11:58');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'nicky iversen', 'auth', 'logout', 'App\\Models\\User', '5', 'nicky iversen', NULL, '93.165.253.15', 'portal_import', '2026-05-29 22:12:49');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '93.165.253.15', 'portal_import', '2026-05-29 22:12:58');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-30 19:27:49');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'installer', 'updated', 'App\\Models\\Virksomhed', '4', 'test', NULL, '188.228.103.132', 'portal_import', '2026-05-30 19:28:20');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'installer', 'updated', 'App\\Models\\Virksomhed', '4', 'test', NULL, '188.228.103.132', 'portal_import', '2026-05-30 19:29:05');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'installer', 'updated', 'App\\Models\\Virksomhed', '4', 'test', NULL, '188.228.103.132', 'portal_import', '2026-05-30 19:35:53');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'installer', 'updated', 'App\\Models\\Virksomhed', '4', 'test', NULL, '188.228.103.132', 'portal_import', '2026-05-30 19:36:24');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '14', 'Nicky Iversen (test)', '{"email": "nickyiversen@gmail.com"}', '188.228.103.132', 'portal_import', '2026-05-30 19:47:26');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-30 19:47:51');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '14', 'Nicky Iversen (test)', NULL, '188.228.103.132', 'portal_import', '2026-05-30 19:49:38');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'logout', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-05-30 20:15:30');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'nicky iversen', 'auth', 'login', 'App\\Models\\User', '5', 'nicky iversen', NULL, '93.165.253.15', 'portal_import', '2026-06-03 11:56:22');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'nicky iversen', 'auth', 'logout', 'App\\Models\\User', '5', 'nicky iversen', NULL, '93.165.253.15', 'portal_import', '2026-06-03 11:56:52');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '93.165.253.15', 'portal_import', '2026-06-03 11:57:03');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'logout', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '93.165.253.15', 'portal_import', '2026-06-03 11:58:55');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '10.180.140.88', 'portal_import', '2026-06-11 16:18:22');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'logout', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '10.180.140.88', 'portal_import', '2026-06-11 16:18:28');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '10.180.140.88', 'portal_import', '2026-06-11 16:18:42');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'deleted', NULL, NULL, 'Nicky iversen 22 (test)', NULL, '10.180.140.88', 'portal_import', '2026-06-11 16:19:24');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'deleted', NULL, NULL, 'Nicky iversen 2 (test)', NULL, '10.180.140.88', 'portal_import', '2026-06-11 16:19:29');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'deleted', NULL, NULL, 'Nicky Iversen (test)', NULL, '10.180.140.88', 'portal_import', '2026-06-11 16:19:34');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'deleted', NULL, NULL, 'nicky iversen (test)', NULL, '10.180.140.88', 'portal_import', '2026-06-11 16:19:39');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'deleted', NULL, NULL, 'nicky iversen (test)', NULL, '10.180.140.88', 'portal_import', '2026-06-11 16:19:44');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '91.238.206.147', 'portal_import', '2026-06-15 09:54:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'installer', 'updated', 'App\\Models\\Virksomhed', '79', 'ZIPP systems ApS', NULL, '91.238.206.147', 'portal_import', '2026-06-15 09:58:28');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\User', '6', 'Karsten S├╕by (ZIPP systems ApS)', NULL, '91.238.206.147', 'portal_import', '2026-06-15 09:59:10');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'installer', 'updated', 'App\\Models\\Virksomhed', '73', 'A.G. Electric A/S', NULL, '91.238.206.147', 'portal_import', '2026-06-15 10:24:02');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\User', '7', 'Jakob Langhoff (A.G. Electric A/S)', NULL, '91.238.206.147', 'portal_import', '2026-06-15 10:24:39');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\User', '8', 'Jesper Toft Simonsen (A.G. Electric A/S)', NULL, '91.238.206.147', 'portal_import', '2026-06-15 10:25:12');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'installer', 'updated', 'App\\Models\\Virksomhed', '18', 'Autronica Fire and Security A/S', NULL, '91.238.206.147', 'portal_import', '2026-06-15 10:27:21');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\User', '9', 'Klaus Schmidt Mikkelsen (Autronica Fire and Security A/S)', NULL, '91.238.206.147', 'portal_import', '2026-06-15 10:28:12');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'system', 'pdf_vejledning_opdateret', NULL, NULL, NULL, NULL, '91.238.206.147', 'portal_import', '2026-06-15 10:30:29');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'installer', 'updated', 'App\\Models\\Virksomhed', '71', 'Str├╕h A/S', NULL, '91.238.206.147', 'portal_import', '2026-06-15 10:32:26');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\User', '10', 'Evan Mathiesen (Str├╕h A/S)', NULL, '91.238.206.147', 'portal_import', '2026-06-15 10:32:49');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'installer', 'updated', 'App\\Models\\Virksomhed', '78', 'Caverion A/S', NULL, '91.238.206.147', 'portal_import', '2026-06-15 10:35:33');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\User', '11', 'Jeppe Ulff-M├╕ller Nielsen (Caverion A/S)', NULL, '91.238.206.147', 'portal_import', '2026-06-15 10:36:23');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'installer', 'updated', 'App\\Models\\Virksomhed', '95', 'Mariendal El-Teknik A/S', NULL, '91.238.206.147', 'portal_import', '2026-06-15 10:38:15');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\User', '12', 'Torben Kroman Secher (Mariendal El-Teknik A/S)', NULL, '91.238.206.147', 'portal_import', '2026-06-15 10:38:53');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'installer', 'updated', 'App\\Models\\Virksomhed', '77', 'Telesikring A/S', NULL, '91.238.206.147', 'portal_import', '2026-06-15 10:40:39');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\User', '13', 'Kasper Vandtved Andersen (Telesikring A/S)', NULL, '91.238.206.147', 'portal_import', '2026-06-15 10:42:37');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'installer', 'updated', 'App\\Models\\Virksomhed', '24', 'Siemens A/S', NULL, '91.238.206.147', 'portal_import', '2026-06-15 10:47:16');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\User', '14', 'Ronni Sole (Siemens A/S)', NULL, '91.238.206.147', 'portal_import', '2026-06-15 10:47:39');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'installer', 'updated', 'App\\Models\\Virksomhed', '84', 'Idom El-forretning ApS', NULL, '91.238.206.147', 'portal_import', '2026-06-15 10:55:04');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\User', '15', 'Martin Kvistgaard Majlandt (Idom El-forretning ApS)', NULL, '91.238.206.147', 'portal_import', '2026-06-15 10:56:17');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'installer', 'updated', 'App\\Models\\Virksomhed', '21', 'WATS A/S', NULL, '91.238.206.147', 'portal_import', '2026-06-15 10:58:46');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\User', '16', 'Emil Tj├╕rnild (WATS A/S)', NULL, '91.238.206.147', 'portal_import', '2026-06-15 10:59:08');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'installer', 'updated', 'App\\Models\\Virksomhed', '1', 'Bravida Danmark A/S', NULL, '91.238.206.147', 'portal_import', '2026-06-15 11:15:04');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'deleted', 'App\\Models\\User', '2', 'Test Admin (Bravida Danmark A/S)', NULL, '91.238.206.147', 'portal_import', '2026-06-15 11:15:10');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\User', '17', 'Casper Claes Billund (Bravida Danmark A/S)', NULL, '91.238.206.147', 'portal_import', '2026-06-15 11:15:44');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'installer', 'updated', 'App\\Models\\Virksomhed', '92', 'El-Team Vest A/S', NULL, '91.238.206.147', 'portal_import', '2026-06-15 11:19:56');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\User', '18', 'Per M├╕lgaard (El-Team Vest A/S)', NULL, '91.238.206.147', 'portal_import', '2026-06-15 11:29:44');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'installer', 'updated', 'App\\Models\\Virksomhed', '37', 'Lelectric ApS', NULL, '91.238.206.147', 'portal_import', '2026-06-15 11:32:47');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\User', '19', 'Thomas H├╕jegaard (Lelectric ApS)', NULL, '91.238.206.147', 'portal_import', '2026-06-15 11:33:07');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'installer', 'updated', 'App\\Models\\Virksomhed', '2', 'Caverion Danmark A/S', NULL, '91.238.206.147', 'portal_import', '2026-06-15 11:33:55');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '15', 'Ronni Sole (Siemens A/S)', '{"email": "ronni.sole@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '16', 'Ren├⌐ S├╕rensen (Siemens A/S)', '{"email": "rene.soerensen@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '17', 'Allan Lindrup (Siemens A/S)', '{"email": "allan.lindrup@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '18', 'Bo Christensen (Siemens A/S)', '{"email": "bo.christensen@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '19', 'Louise Friborg Wiggers (Siemens A/S)', '{"email": "louise.wiggers@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '20', 'Troels Jansen (Siemens A/S)', '{"email": "troels.jansen@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '21', 'Torben Skaaning Krogh (Siemens A/S)', '{"email": "torben.krogh@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '22', 'Martin Thorbj├╕rn Fischer (Siemens A/S)', '{"email": "martin.t.nielsen@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '23', 'Henrik Christiansen (Siemens A/S)', '{"email": "henrik.christiansen@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '24', 'Peter N├╕rregaard (Siemens A/S)', '{"email": "peter.noerregaard@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '25', 'Tim Nymark Poulsen (Siemens A/S)', '{"email": "tim.n.poulsen@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '26', 'Johnny Larsen (Siemens A/S)', '{"email": "johnny.larsen@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '27', 'Johannes M├╕ller Nielsen (Siemens A/S)', '{"email": "johannes.nielsen@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '28', 'Torben Scott Enemark (Siemens A/S)', '{"email": "torben.enemark@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '29', 'Kristian Emil Olsen (Siemens A/S)', '{"email": "kristian.olsen@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '30', 'Flemming H├╕jgaard Flebbe (Siemens A/S)', '{"email": "flemming.flebbe@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '31', 'Sulejman Topic (Siemens A/S)', '{"email": "sulejman.topic@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '32', 'Costel Gabriel Puica (Siemens A/S)', '{"email": "puica.gabriel@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '33', 'Ahmed Chaabi (Siemens A/S)', '{"email": "ahmed.chaabi@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '34', 'Morten Taanum Iversen (Siemens A/S)', '{"email": "morten.iversen@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '35', 'Tommy Andersen (Siemens A/S)', '{"email": "tommy.andersen@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '36', 'Kim Truelsen (Siemens A/S)', '{"email": "kimtruelsen@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '37', 'Flemming Rine Olsen (Siemens A/S)', '{"email": "fleming.olsen@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '38', 'Kurt Mikkelsen (Siemens A/S)', '{"email": "kurt.mikkelsen@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '39', 'Carsten H├╕jgaard (Siemens A/S)', '{"email": "carsten.hojgaard@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '40', 'Frederik Risgaard (Siemens A/S)', '{"email": "frederik.risgaard@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '41', 'Kartsen Tavs (Siemens A/S)', '{"email": "karsten.tavs@siemens.com"}', '127.0.0.1', 'portal_import', '2026-06-15 11:44:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'installer', 'updated', 'App\\Models\\Virksomhed', '67', 'Dansk Sprinkler Teknik A/S', NULL, '91.238.206.147', 'portal_import', '2026-06-15 11:52:32');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\User', '20', 'Hans-Henrik Madsen (Dansk Sprinkler Teknik A/S)', NULL, '91.238.206.147', 'portal_import', '2026-06-15 11:53:04');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'installer', 'updated', 'App\\Models\\Virksomhed', '74', 'Tekum ApS', NULL, '91.238.206.147', 'portal_import', '2026-06-15 12:00:10');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\User', '21', 'Michael Vissing (Tekum ApS)', NULL, '91.238.206.147', 'portal_import', '2026-06-15 12:00:37');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'installer', 'updated', 'App\\Models\\Virksomhed', '58', 'Actas A/S', NULL, '91.238.206.147', 'portal_import', '2026-06-15 12:02:22');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\User', '22', 'Jacob Rose Rodriguez J├╕rgensen (Actas A/S)', NULL, '91.238.206.147', 'portal_import', '2026-06-15 12:03:08');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jacob Rose Rodriguez J├╕rgensen', 'auth', 'login', 'App\\Models\\User', '22', 'Jacob Rose Rodriguez J├╕rgensen', NULL, '94.189.40.133', 'portal_import', '2026-06-15 12:06:55');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '42', 'Evan Mathiesen (Str├╕h A/S)', '{"email": "em@stp.dk"}', '127.0.0.1', 'portal_import', '2026-06-15 12:06:59');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '43', 'Claus Eskildsen (Str├╕h A/S)', '{"email": "cje@stp.dk"}', '127.0.0.1', 'portal_import', '2026-06-15 12:06:59');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '44', 'Mikael J J├╕rgensen (Str├╕h A/S)', '{"email": "mjj@stp.dk"}', '127.0.0.1', 'portal_import', '2026-06-15 12:06:59');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '45', 'Christian Tagesen (Str├╕h A/S)', '{"email": "cht@stp.dk"}', '127.0.0.1', 'portal_import', '2026-06-15 12:06:59');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jacob Rose Rodriguez J├╕rgensen', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '46', 'J├╕rgen Jensen (Actas A/S)', '{"email": "jje@actas.dk"}', '94.189.40.133', 'portal_import', '2026-06-15 12:21:29');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jacob Rose Rodriguez J├╕rgensen', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '47', 'Jacob J├╕rgensen (Actas A/S)', '{"email": "jrj@actas.dk"}', '94.189.40.133', 'portal_import', '2026-06-15 12:21:53');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jacob Rose Rodriguez J├╕rgensen', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '48', 'Oliver Lagoni (Actas A/S)', '{"email": "ola@actas.dk"}', '94.189.40.133', 'portal_import', '2026-06-15 12:22:38');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jacob Rose Rodriguez J├╕rgensen', 'auth', 'logout', 'App\\Models\\User', '22', 'Jacob Rose Rodriguez J├╕rgensen', NULL, '94.189.40.133', 'portal_import', '2026-06-15 12:41:54');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jacob Rose Rodriguez J├╕rgensen', 'auth', 'login', 'App\\Models\\User', '22', 'Jacob Rose Rodriguez J├╕rgensen', NULL, '94.189.40.133', 'portal_import', '2026-06-15 12:42:45');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '10.180.140.88', 'portal_import', '2026-06-15 16:53:47');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'installer', 'updated', 'App\\Models\\Virksomhed', '67', 'Dansk Sprinkler Teknik A/S', NULL, '10.180.140.88', 'portal_import', '2026-06-15 16:54:57');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jeppe Ulff-M├╕ller Nielsen', 'auth', 'login', 'App\\Models\\User', '11', 'Jeppe Ulff-M├╕ller Nielsen', NULL, '85.184.160.211', 'portal_import', '2026-06-15 18:31:38');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jeppe Ulff-M├╕ller Nielsen', 'auth', 'logout', 'App\\Models\\User', '11', 'Jeppe Ulff-M├╕ller Nielsen', NULL, '85.184.160.211', 'portal_import', '2026-06-15 18:32:30');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jeppe Ulff-M├╕ller Nielsen', 'auth', 'login', 'App\\Models\\User', '11', 'Jeppe Ulff-M├╕ller Nielsen', NULL, '85.184.160.211', 'portal_import', '2026-06-15 18:32:53');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jeppe Ulff-M├╕ller Nielsen', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '49', 'J├╕rn Vestergaard Kristensen (Caverion A/S)', '{"email": "joern.v.kristensen@caverion.com"}', '85.184.160.211', 'portal_import', '2026-06-15 18:36:14');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jeppe Ulff-M├╕ller Nielsen', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '50', 'Dennis Jensen (Caverion A/S)', '{"email": "dennis.jensen@caverion.com"}', '85.184.160.211', 'portal_import', '2026-06-15 18:37:10');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jeppe Ulff-M├╕ller Nielsen', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '51', 'Thorbj├╕rn Falk (Caverion A/S)', '{"email": "thorbjoern.falk@caverion.com"}', '85.184.160.211', 'portal_import', '2026-06-15 18:38:20');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jeppe Ulff-M├╕ller Nielsen', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '52', 'Nikolaj Nyborg Beck Jensen (Caverion A/S)', '{"email": "nikolaj.jensen@caverion.com"}', '85.184.160.211', 'portal_import', '2026-06-15 18:39:20');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jacob Rose Rodriguez J├╕rgensen', 'auth', 'login', 'App\\Models\\User', '22', 'Jacob Rose Rodriguez J├╕rgensen', NULL, '87.49.147.84', 'portal_import', '2026-06-16 07:58:40');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '91.238.206.148', 'portal_import', '2026-06-16 10:16:11');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Karsten S├╕by', 'auth', 'login', 'App\\Models\\User', '6', 'Karsten S├╕by', NULL, '217.74.208.153', 'portal_import', '2026-06-16 13:06:53');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Karsten S├╕by', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '53', 'Henrik Bisgaard Laursen (ZIPP systems ApS)', '{"email": "hbl@zippsystems.dk"}', '217.74.208.153', 'portal_import', '2026-06-16 13:10:03');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Karsten S├╕by', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '54', 'Kaspar Adamsen (ZIPP systems ApS)', '{"email": "kad@zippsystems.dk"}', '217.74.208.153', 'portal_import', '2026-06-16 13:10:33');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Karsten S├╕by', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '55', 'Henrik Bach (ZIPP systems ApS)', '{"email": "hba@zippsystems.dk"}', '217.74.208.153', 'portal_import', '2026-06-16 13:10:58');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Karsten S├╕by', 'auth', 'logout', 'App\\Models\\User', '6', 'Karsten S├╕by', NULL, '217.74.208.153', 'portal_import', '2026-06-16 13:11:15');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Karsten S├╕by', 'auth', 'login', 'App\\Models\\User', '6', 'Karsten S├╕by', NULL, '217.74.208.153', 'portal_import', '2026-06-16 13:11:40');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jesper Toft Simonsen', 'auth', 'login', 'App\\Models\\User', '8', 'Jesper Toft Simonsen', NULL, '212.112.158.244', 'portal_import', '2026-06-17 07:55:49');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jesper Toft Simonsen', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '56', 'Jakob Langhoff (A.G. Electric A/S)', '{"email": "jl@ag-electric.dk"}', '212.112.158.244', 'portal_import', '2026-06-17 07:56:55');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jesper Toft Simonsen', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '57', 'Sebastian Brodersen (A.G. Electric A/S)', '{"email": "sep@ag-electric.dk"}', '212.112.158.244', 'portal_import', '2026-06-17 07:57:24');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jesper Toft Simonsen', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '58', 'Jens Christian Ravnholt Cramer (A.G. Electric A/S)', '{"email": "jcc@ag-electric.dk"}', '212.112.158.244', 'portal_import', '2026-06-17 07:57:56');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jesper Toft Simonsen', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '59', 'Fylkir S├ªvarsson (A.G. Electric A/S)', '{"email": "fys@ag-electric.dk"}', '212.112.158.244', 'portal_import', '2026-06-17 07:58:40');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '60', 'Peder skelskov (Bravida Danmark A/S)', '{"email": "peder.skelskov@bravida.dk"}', '37.96.79.229', 'portal_import', '2026-06-17 08:20:40');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '93.165.253.15', 'portal_import', '2026-06-17 10:26:30');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '10.180.140.91', 'portal_import', '2026-06-17 10:27:10');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '32', 'Costel Gabriel Puica (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 10:43:24');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '31', 'Sulejman Topic (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 10:46:18');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '30', 'Flemming H├╕jgaard Flebbe (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 10:51:17');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '29', 'Kristian Emil Olsen (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 10:53:58');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '15', 'Ronni Sole (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 10:57:52');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '16', 'Ren├⌐ S├╕rensen (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:00:14');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '17', 'Allan Lindrup (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:02:30');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '17', 'Allan Lindrup (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:02:39');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '18', 'Bo Christensen (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:04:31');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '19', 'Louise Friborg Wiggers (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:06:33');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '20', 'Troels Jansen (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:09:14');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '21', 'Torben Skaaning Krogh (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:11:23');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '22', 'Martin Thorbj├╕rn Fischer (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:14:29');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '23', 'Henrik Christiansen (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:16:50');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '24', 'Peter N├╕rregaard (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:21:21');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '25', 'Tim Nymark Poulsen (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:24:03');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '26', 'Johnny Larsen (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:26:02');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '27', 'Johannes M├╕ller Nielsen (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:28:14');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '28', 'Torben Scott Enemark (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:30:13');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '33', 'Ahmed Chaabi (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:36:12');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '34', 'Morten Taanum Iversen (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:38:15');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '35', 'Tommy Andersen (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:40:15');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '36', 'Kim Truelsen (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:42:09');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '37', 'Flemming Rine Olsen (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:44:10');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '38', 'Kurt Mikkelsen (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:46:07');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '39', 'Carsten H├╕jgaard (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:48:03');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '40', 'Frederik Risgaard (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:49:59');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '41', 'Kartsen Tavs (Siemens A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:51:40');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '60', 'Peder skelskov (Bravida Danmark A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 11:53:17');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'system', 'pdf_vejledning_opdateret', NULL, NULL, NULL, NULL, '100.65.99.166', 'portal_import', '2026-06-17 13:44:53');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Per M├╕lgaard', 'auth', 'login', 'App\\Models\\User', '18', 'Per M├╕lgaard', NULL, '86.52.118.241', 'portal_import', '2026-06-17 14:41:49');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Peter M├╕lgaard', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '61', 'Carsten Juhl Andersen (El-Team Vest A/S)', '{"email": "cja@elteamvest.dk"}', '86.52.118.241', 'portal_import', '2026-06-17 14:47:14');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Peter M├╕lgaard', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '62', 'Claus Grav Lange (El-Team Vest A/S)', '{"email": "cgl@elteamvest.dk"}', '86.52.118.241', 'portal_import', '2026-06-17 14:47:54');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Peter M├╕lgaard', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '63', 'Claus Poulin (El-Team Vest A/S)', '{"email": "cpl@elteamvest.dk"}', '86.52.118.241', 'portal_import', '2026-06-17 14:48:35');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Peter M├╕lgaard', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '64', 'Emil Lykke Thulstrup (El-Team Vest A/S)', '{"email": "elt@elteamvest.dk"}', '86.52.118.241', 'portal_import', '2026-06-17 14:49:14');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Peter M├╕lgaard', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '65', 'Jesper Traberg (El-Team Vest A/S)', '{"email": "jtr@elteamvest.dk"}', '86.52.118.241', 'portal_import', '2026-06-17 14:49:48');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Peter M├╕lgaard', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '66', 'Kristian Nervik R├╕dby (El-Team Vest A/S)', '{"email": "knr@elteamvest.dk"}', '86.52.118.241', 'portal_import', '2026-06-17 14:50:28');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Peter M├╕lgaard', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '67', 'Michael Johansen (El-Team Vest A/S)', '{"email": "mjo@elteamvest.dk"}', '86.52.118.241', 'portal_import', '2026-06-17 14:51:08');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Peter M├╕lgaard', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '68', 'Michal Majewski (El-Team Vest A/S)', '{"email": "mim@elteamvest.dk"}', '86.52.118.241', 'portal_import', '2026-06-17 14:51:54');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Peter M├╕lgaard', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '69', 'Thomas Rosenbech Lund (El-Team Vest A/S)', '{"email": "trl@elteamvest.dk"}', '86.52.118.241', 'portal_import', '2026-06-17 14:52:37');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '46', 'J├╕rgen Jensen (Actas A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 15:03:20');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '47', 'Jacob J├╕rgensen (Actas A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 15:05:19');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '48', 'Oliver Lagoni (Actas A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 15:07:51');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '49', 'J├╕rn Vestergaard Kristensen (Caverion A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 15:09:48');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '50', 'Dennis Jensen (Caverion A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 15:11:53');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '51', 'Thorbj├╕rn Falk (Caverion A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 15:16:06');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '52', 'Nikolaj Nyborg Beck Jensen (Caverion A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 15:18:16');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '53', 'Henrik Bisgaard Laursen (ZIPP systems ApS)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 15:21:17');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '54', 'Kaspar Adamsen (ZIPP systems ApS)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 15:23:21');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '55', 'Henrik Bach (ZIPP systems ApS)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 15:25:05');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '56', 'Jakob Langhoff (A.G. Electric A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 15:35:18');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '57', 'Sebastian Brodersen (A.G. Electric A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 15:44:25');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '58', 'Jens Christian Ravnholt Cramer (A.G. Electric A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 15:46:23');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '59', 'Fylkir S├ªvarsson (A.G. Electric A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 15:50:49');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '61', 'Carsten Juhl Andersen (El-Team Vest A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 15:54:13');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '62', 'Claus Grav Lange (El-Team Vest A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 15:57:10');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '63', 'Claus Poulin (El-Team Vest A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 15:58:55');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '64', 'Emil Lykke Thulstrup (El-Team Vest A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 16:02:38');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '65', 'Jesper Traberg (El-Team Vest A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 16:04:11');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '66', 'Kristian Nervik R├╕dby (El-Team Vest A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 16:05:50');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '67', 'Michael Johansen (El-Team Vest A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 16:07:27');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '68', 'Michal Majewski (El-Team Vest A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 16:08:57');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '69', 'Thomas Rosenbech Lund (El-Team Vest A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 16:10:30');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '42', 'Evan Mathiesen (Str├╕h A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 16:48:31');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '43', 'Claus Eskildsen (Str├╕h A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 16:48:43');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '44', 'Mikael J J├╕rgensen (Str├╕h A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 16:49:02');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '45', 'Christian Tagesen (Str├╕h A/S)', NULL, '100.65.99.166', 'portal_import', '2026-06-17 16:49:19');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'logout', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '100.65.99.166', 'portal_import', '2026-06-17 16:51:07');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '128.76.147.63', 'portal_import', '2026-06-17 17:20:22');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jacob Rose Rodriguez J├╕rgensen', 'auth', 'logout', 'App\\Models\\User', '22', 'Jacob Rose Rodriguez J├╕rgensen', NULL, '212.10.79.14', 'portal_import', '2026-06-17 19:52:20');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jacob Rose Rodriguez J├╕rgensen', 'auth', 'login', 'App\\Models\\User', '22', 'Jacob Rose Rodriguez J├╕rgensen', NULL, '212.10.79.14', 'portal_import', '2026-06-17 19:53:32');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jacob Rose Rodriguez J├╕rgensen', 'auth', 'logout', 'App\\Models\\User', '22', 'Jacob Rose Rodriguez J├╕rgensen', NULL, '212.10.79.14', 'portal_import', '2026-06-17 20:03:56');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jacob Rose Rodriguez J├╕rgensen', 'auth', 'login', 'App\\Models\\User', '22', 'Jacob Rose Rodriguez J├╕rgensen', NULL, '212.10.79.14', 'portal_import', '2026-06-17 20:05:58');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jacob Rose Rodriguez J├╕rgensen', 'auth', 'logout', 'App\\Models\\User', '22', 'Jacob Rose Rodriguez J├╕rgensen', NULL, '212.10.79.14', 'portal_import', '2026-06-17 20:06:13');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jacob Rose Rodriguez J├╕rgensen', 'auth', 'login', 'App\\Models\\User', '22', 'Jacob Rose Rodriguez J├╕rgensen', NULL, '212.10.79.14', 'portal_import', '2026-06-17 20:06:29');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jacob Rose Rodriguez J├╕rgensen', 'auth', 'login', 'App\\Models\\User', '22', 'Jacob Rose Rodriguez J├╕rgensen', NULL, '212.10.79.14', 'portal_import', '2026-06-17 20:23:59');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-06-17 22:29:01');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '93.165.253.15', 'portal_import', '2026-06-17 22:51:26');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jesper Toft Simonsen', 'auth', 'login', 'App\\Models\\User', '8', 'Jesper Toft Simonsen', NULL, '212.112.158.244', 'portal_import', '2026-06-18 07:37:08');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Michael Vissing', 'auth', 'login', 'App\\Models\\User', '21', 'Michael Vissing', NULL, '37.96.7.207', 'portal_import', '2026-06-18 07:48:55');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Michael Vissing', 'auth', 'logout', 'App\\Models\\User', '21', 'Michael Vissing', NULL, '37.96.7.207', 'portal_import', '2026-06-18 07:49:49');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Michael Vissing', 'auth', 'login', 'App\\Models\\User', '21', 'Michael Vissing', NULL, '37.96.7.207', 'portal_import', '2026-06-18 07:50:12');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Michael Vissing', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '70', 'Ole Eskildsen (Tekum ApS)', '{"email": "ole@tekum.dk"}', '37.96.7.207', 'portal_import', '2026-06-18 07:51:25');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Michael Vissing', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '71', 'Michael Vissing (Tekum ApS)', '{"email": "mkv@tekum.dk"}', '37.96.7.207', 'portal_import', '2026-06-18 07:57:32');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '10.180.140.88', 'portal_import', '2026-06-18 09:15:16');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Kasper Vandtved Andersen', 'auth', 'login', 'App\\Models\\User', '13', 'Kasper Vandtved Andersen', NULL, '87.62.97.77', 'portal_import', '2026-06-18 09:15:55');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Kasper Vandtved Andersen', 'auth', 'logout', 'App\\Models\\User', '13', 'Kasper Vandtved Andersen', NULL, '87.62.97.77', 'portal_import', '2026-06-18 09:16:35');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Kasper Vandtved Andersen', 'auth', 'login', 'App\\Models\\User', '13', 'Kasper Vandtved Andersen', NULL, '87.62.97.77', 'portal_import', '2026-06-18 09:16:53');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Kasper Vandtved Andersen', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '72', 'Michael Br├╝gmann (Telesikring A/S)', '{"email": "mbr@telesikring.dk"}', '87.62.97.77', 'portal_import', '2026-06-18 09:17:51');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Kasper Vandtved Andersen', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '73', 'Dan Frederiksen (Telesikring A/S)', '{"email": "dfr@telesikring.dk"}', '87.62.97.77', 'portal_import', '2026-06-18 09:18:13');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Kasper Vandtved Andersen', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '74', 'Astrid Ross (Telesikring A/S)', '{"email": "acr@telesikring.dk"}', '87.62.97.77', 'portal_import', '2026-06-18 09:18:47');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '10.180.140.88', 'portal_import', '2026-06-18 13:32:03');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '93.165.253.15', 'portal_import', '2026-06-18 18:22:00');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jesper Toft Simonsen', 'auth', 'login', 'App\\Models\\User', '8', 'Jesper Toft Simonsen', NULL, '212.112.158.244', 'portal_import', '2026-06-19 07:19:17');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jesper Toft Simonsen', 'auth', 'login', 'App\\Models\\User', '8', 'Jesper Toft Simonsen', NULL, '212.112.158.244', 'portal_import', '2026-06-19 09:55:58');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jesper Toft Simonsen', 'auth', 'login', 'App\\Models\\User', '8', 'Jesper Toft Simonsen', NULL, '86.52.26.218', 'portal_import', '2026-06-19 12:16:29');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '75', 'S├╕ren S Pedersen (Bravida Danmark A/S)', '{"email": "soren.steenberg@bravida.dk"}', '37.96.10.57', 'portal_import', '2026-06-22 12:09:01');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '76', 'Henrik Knudsen (Bravida Danmark A/S)', '{"email": "h.knudsen@bravida.dk"}', '213.83.189.18', 'portal_import', '2026-06-22 12:12:34');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '77', 'Paw Nors Vestergaard (Bravida Danmark A/S)', '{"email": "paw.n.vestergaard@bravida.dk"}', '213.83.189.18', 'portal_import', '2026-06-22 12:28:48');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '78', 'birthe.petersen@bravida.dk (Bravida Danmark A/S)', '{"email": "birthe.petersen@bravida.dk"}', '213.83.157.46', 'portal_import', '2026-06-22 14:13:19');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jesper Toft Simonsen', 'auth', 'login', 'App\\Models\\User', '8', 'Jesper Toft Simonsen', NULL, '212.112.158.244', 'portal_import', '2026-06-22 14:20:46');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jesper Toft Simonsen', 'auth', 'logout', 'App\\Models\\User', '8', 'Jesper Toft Simonsen', NULL, '212.112.158.244', 'portal_import', '2026-06-22 14:21:21');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jesper Toft Simonsen', 'auth', 'login', 'App\\Models\\User', '8', 'Jesper Toft Simonsen', NULL, '212.112.158.244', 'portal_import', '2026-06-22 14:21:28');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '10.180.140.88', 'portal_import', '2026-06-22 14:29:10');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'system', 'firma_admin_nyt_password', 'App\\Models\\User', '7', 'Jakob Langhoff (A.G. Electric A/S)', NULL, '10.180.140.88', 'portal_import', '2026-06-22 14:29:41');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jakob Langhoff', 'auth', 'login', 'App\\Models\\User', '7', 'Jakob Langhoff', NULL, '212.112.158.244', 'portal_import', '2026-06-22 14:51:24');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jakob Langhoff', 'auth', 'logout', 'App\\Models\\User', '7', 'Jakob Langhoff', NULL, '212.112.158.244', 'portal_import', '2026-06-22 14:54:40');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jakob Langhoff', 'auth', 'login', 'App\\Models\\User', '7', 'Jakob Langhoff', NULL, '212.112.158.244', 'portal_import', '2026-06-22 14:55:03');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '10.180.140.92', 'portal_import', '2026-06-23 07:27:32');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '78', 'birthe.petersen@bravida.dk (Bravida Danmark A/S)', NULL, '10.180.140.92', 'portal_import', '2026-06-23 07:35:35');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '77', 'Paw Nors Vestergaard (Bravida Danmark A/S)', NULL, '10.180.140.92', 'portal_import', '2026-06-23 07:40:11');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '76', 'Henrik Knudsen (Bravida Danmark A/S)', NULL, '10.180.140.92', 'portal_import', '2026-06-23 07:42:31');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '75', 'S├╕ren S Pedersen (Bravida Danmark A/S)', NULL, '10.180.140.92', 'portal_import', '2026-06-23 07:48:19');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '74', 'Astrid Ross (Telesikring A/S)', NULL, '10.180.140.92', 'portal_import', '2026-06-23 07:59:49');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '73', 'Dan Frederiksen (Telesikring A/S)', NULL, '10.180.140.92', 'portal_import', '2026-06-23 08:02:25');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'user', 'created', 'App\\Models\\MontorAnmodning', '72', 'Michael Br├╝gmann (Telesikring A/S)', NULL, '10.180.140.92', 'portal_import', '2026-06-23 08:05:08');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Casper Claes Billund', 'auth', 'login', 'App\\Models\\User', '17', 'Casper Claes Billund', NULL, '34.89.99.246', 'portal_import', '2026-06-23 10:05:20');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jakob Langhoff', 'auth', 'login', 'App\\Models\\User', '7', 'Jakob Langhoff', NULL, '87.49.44.76', 'portal_import', '2026-06-23 12:21:37');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Jesper Toft Simonsen', 'auth', 'login', 'App\\Models\\User', '8', 'Jesper Toft Simonsen', NULL, '212.112.158.244', 'portal_import', '2026-06-23 15:28:53');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '79', 'Michael Lund Milo (Bravida Danmark A/S)', '{"email": "michael.milo@bravida.dk"}', '213.83.157.46', 'portal_import', '2026-06-24 10:47:17');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '80', 'Henrik Thingholm (Bravida Danmark A/S)', '{"email": "henrik.thingholm@bravida.dk"}', '87.116.27.180', 'portal_import', '2026-06-24 13:11:44');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Casper Billund', 'auth', 'login', 'App\\Models\\User', '17', 'Casper Billund', NULL, '87.51.143.0', 'portal_import', '2026-06-24 14:31:44');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '10.180.140.88', 'portal_import', '2026-06-24 15:18:22');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Casper Billund', 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '81', 'Casper Billund (Bravida Danmark A/S)', '{"email": "casper.billund@bravida.dk"}', '87.51.143.0', 'portal_import', '2026-06-24 15:21:33');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '82', 'Casper Gregorius (Bravida Danmark A/S)', '{"email": "casper.gregorius@bravida.dk"}', '87.49.43.8', 'portal_import', '2026-06-25 08:39:24');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '83', 'Jes Asmussen (Bravida Danmark A/S)', '{"email": "jes.asmussen@bravida.dk"}', '213.83.157.58', 'portal_import', '2026-06-25 13:33:44');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '84', 'Mamo Khalil (Bravida Danmark A/S)', '{"email": "mamo.khalil@bravida.dk"}', '213.83.157.58', 'portal_import', '2026-06-25 13:37:21');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, NULL, 'registration', 'submitted', 'App\\Models\\MontorAnmodning', '85', 'Andy Vilhelmsen (Bravida Danmark A/S)', '{"email": "andy.vilhelmsen@bravida.dk"}', '185.125.221.223', 'portal_import', '2026-06-26 13:18:46');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '188.228.103.132', 'portal_import', '2026-06-26 15:33:58');
INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (NULL, 'Trekantbrand Admin', 'auth', 'login', 'App\\Models\\User', '1', 'Trekantbrand Admin', NULL, '95.154.23.240', 'portal_import', '2026-06-28 17:28:01');

SET FOREIGN_KEY_CHECKS = 1;
-- Efter import: send velkomst-mails til alle brugere med password_hash IS NULL
