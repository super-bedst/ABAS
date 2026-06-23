# ABA Service (ABAS)

Webapplikation til at sætte automatiske brandalarmeringsanlæg (ABA) i service via TrekantBrand API.

## Funktioner

- Rollebaseret adgang: admin, vagtcentral, montør, anlægsejer
- Dashboard med anlægssøgning og service start/stop
- Alarmlog (sidste 20, 24 timer, brugerdefineret periode)
- Vagtcentral: hurtig service på vegne af montør, anlægsbruger-administration
- Montør-selvregistrering via godkendte installatør-domæner
- Admin: brugere, installatører, sync-prefixes, API-tokens, indstillinger
- Intern REST API (`/api/v1`) med Bearer-auth
- SMS-kommandoer via API-webhook (`secret miscno2 START|STOP`)
- Cron-jobs til udløbspåmindelser og anlægssynk

## Krav

- PHP 8.2+
- MySQL 8+
- Apache med `mod_rewrite` (eller tilsvarende routing til `public/`)
- cURL extension
- TrekantBrand API-adgang

## Opsætning

1. Klon repoet og peg webserverens document root på `public/`:

```bash
git clone https://github.com/<org>/ABAS.git
cd ABAS
cp .env.example .env
```

2. Rediger `.env` med database og TrekantBrand credentials:

```
DB_HOST=127.0.0.1
DB_NAME=aba_service
DB_USER=root
DB_PASS=secret
TREKANT_API_USER=NKI
TREKANT_API_PASS=***
APP_URL=http://localhost
```

3. Opret database og importer skema:

```bash
mysql -u root -p -e "CREATE DATABASE aba_service CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p aba_service < Database/schema.sql
```

### Apache (undermappe, fx `/Sandbox/ABAS/public/`)

Document root peger typisk på `public/`. Applikationen finder selv base-sti ud fra URL'en.

Valgfrit kan du åbne `https://host/Sandbox/ABAS/` — roden `index.php` videresender til `public/`.

Sæt i `.env`:

```
APP_URL=https://tkb.teamscreen.dk/Sandbox/ABAS/public
```

### Apache (direkte domæne)

Document root = `public/`. `APP_URL=https://abas.example.dk`

4. Start PHP built-in server (udvikling):

```bash
php -S localhost:8080 -t public
```

5. Log ind med seed-admin:
   - **Bruger:** `admin`
   - **E-mail:** `admin@trekantbrand.dk`
   - **Adgangskode:** `admin123` (skift i produktion!)

## Cron

```cron
*/5 * * * * php /path/to/ABAS/cron/sms_expiry.php
0 2 * * * php /path/to/ABAS/cron/sync_installations.php
*/15 * * * * php /path/to/ABAS/cron/expire_sessions.php
```

## API

Se [docs/openapi.yaml](docs/openapi.yaml). Eksempel:

```bash
curl -H "Authorization: Bearer <token>" \
  "http://localhost:8080/api/v1/installations/search?q=fab"
```

Opret tokens under **Admin → API-tokens**.

## SMS-format

Inbound SMS (via `/api/v1/sms/inbound`):

```
<hemmelighed> <miscno2> START <timer>
<hemmelighed> <miscno2> STOP
<hemmelighed> <miscno2> STATUS
```

Brugerens `sms_secret_hash` sættes i databasen (bcrypt af hemmelighed).

## Test mod TrekantBrand

```powershell
$env:TREKANT_API_USER='NKI'
$env:TREKANT_API_PASS='***'
.\scripts\trekant_test.ps1 start
```

## Dokumentation

- [docs/PLAN.md](docs/PLAN.md) — arkitektur og krav
- [docs/ABA-Service-Brandvaesen.pdf](docs/ABA-Service-Brandvaesen.pdf) — stakeholder-beskrivelse

## Licens

Proprietær — TrekantBrand.
