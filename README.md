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

## Frontend (Tailwind CSS)

Styling bygges lokalt fra `resources/css/app.css` til `public/assets/css/app.css`.

**Udvikling (watch):**

```bash
npm install
npm run watch
```

**Produktion / efter deploy:**

```bash
npm install
npm run build
```

### Opdater server (git pull + Tailwind)

På WAMP-serveren:

```powershell
cd C:\wamp64\www\TrekantBrand\Sandbox\ABAS
.\scripts\deploy.ps1
```

Hvis `git pull` fejler (fx uafsluttet merge eller lokale ændringer), brug **force** — kasserer lokale ændringer og matcher `origin/master`:

```powershell
.\scripts\deploy.ps1 -Force
```

Hvis `npm install` fejler (fx exit `-4048` pga. fil-lås/antivirus), brug **SkipNpm** — PHP deployes og CSS hentes fra git:

```powershell
.\scripts\deploy.ps1 -SkipNpm
# eller kombineret:
.\scripts\deploy.ps1 -Force -SkipNpm
```

Scriptet kører `git pull`, `npm install` og `npm run build`. Kræver [Node.js](https://nodejs.org) på serveren.

## Cron

```cron
*/5 * * * * php /path/to/ABAS/cron/sms_expiry.php
*/5 * * * * php /path/to/ABAS/cron/sms_outbound.php
0 2 * * * php /path/to/ABAS/cron/sync_installations.php
*/15 * * * * php /path/to/ABAS/cron/expire_sessions.php
```

**Anlægssynk via HTTP (Node-RED):** Sæt `SYNC_CRON_SECRET` i `env.local`, kald derefter:

```
GET https://tkb.teamscreen.dk/Sandbox/ABAS/public/api/v1/cron/sync-installations?key=<secret>
```

Alternativt `Authorization: Bearer <secret>`. Svar er JSON med `total_upserted` og `duration_ms`. Sæt lang timeout i Node-RED (typisk 1–2 min for 100 batch-kald pr. prefix).

**Service-reconcile (ekstern testkø):** Samme nøgle (`SYNC_CRON_SECRET`):

```
GET https://tkb.teamscreen.dk/Sandbox/ABAS/public/api/v1/cron/reconcile-service?key=<secret>
```

Legacy-URL'er (`/cron/sync_installations.php` og `/cron/reconcile_service.php`) kræver nu også `?key=` ved HTTP-kald. CLI-cron uden nøgle virker som før.

## API

Se [docs/openapi.yaml](docs/openapi.yaml). Eksempel:

```bash
curl -H "Authorization: Bearer <token>" \
  "http://localhost:8080/api/v1/installations/search?q=fab"
```

Opret tokens under **Admin → API-tokens**.

## SMS-format

Outbound SMS sendes via **BAS** `Api/V2/Sms/sendSms.php` (samme Inmobile-integration som PMS/ISM).
Sæt `BAS_SMS_API_URL` og `BAS_SMS_API_TOKEN` i `env.local` — brug PMS-token til test (`BAS_SMS_SYSTEM=PMS`).

Inbound webhook (uden API Bearer-token):

```
POST /api/v1/sms/inbound
{"from":"+4520123456","body":"secret123 fab0100 START 2"}
```

Fuld URL (BAS): `https://tkb.teamscreen.dk/Sandbox/ABAS/public/api/v1/sms/inbound`

Kræver `mod_rewrite` og `AllowOverride` for `.htaccess` i `public/`.

Valgfrit: `SMS_INBOUND_SECRET` — gateway sender `?key=` eller `Authorization: Bearer`.

Eksempler på SMS-kommandoer:

```
<SMS-kode> <miscno2> START <timer>
<SMS-kode> <miscno2> STOP
<SMS-kode> <miscno2> STATUS
```

SMS-koden (min. 6 tegn) sættes ved oprettelse af montør/anlægsejer. Afsenderens telefonnummer skal matche brugerens registrerede nummer.

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
