# TrekantBrand testscript

Interaktivt testscript til at verificere API-kald mod TrekantBrand for ABA Service.

## Opsætning

1. Kopiér `.env.example` til `.env` i projektroden
2. Udfyld `TREKANT_API_USER` og `TREKANT_API_PASS`

```powershell
cd c:\Private\aba-service
copy .env.example .env
# Rediger .env med credentials
```

## Kørsel

**Interaktiv menu:**

```powershell
.\scripts\trekant_test.ps1
```

**Direkte kommandoer:**

```powershell
# Sæt FAB0100 i test i 3 timer
.\scripts\trekant_test.ps1 start

# Tjek status
.\scripts\trekant_test.ps1 status

# Stop test (med kommentar)
.\scripts\trekant_test.ps1 stop

# Hent sidste 20 loglinjer
.\scripts\trekant_test.ps1 log

# Hent flere loglinjer
.\scripts\trekant_test.ps1 log -Lines 50
```

**Andet anlæg / varighed:**

```powershell
.\scripts\trekant_test.ps1 start -MiscNo2 fab0100 -Hours 2
```

**Uden .env (midlertidigt):**

```powershell
$env:TREKANT_API_USER = 'NKI'
$env:TREKANT_API_PASS = '***'
.\scripts\trekant_test.ps1 start
```

## Standardkommentarer

| Handling | Kommentar |
|----------|-----------|
| Start | `TEST START ABAS Bruger Peter Friis 20654196 Trekantbrand` |
| Stop | `TEST STOP ABAS Bruger Peter Friis 20654196 Trekantbrand` |

Kan overrides med `-StartComment` / `-StopComment` eller `TREKANT_TEST_USER_LABEL` i `.env`.

## Anlægsopslag

Scriptet finder anlæg via **`miscno2`** (fx `fab0100`), ikke `ins_no`.

Senest fundne anlæg gemmes i `scripts/.trekant_test_state.json` (s_ins, deal_id, s_inc til stop).

## API-endpoints

| Handling | Endpoint |
|----------|----------|
| Login | `POST /login/login` |
| Find anlæg | `g_search_installations` |
| Start test | `c_ma_testqueue` |
| Status | `g_ma_testqueue` |
| Stop test | `d_ma_testqueue` |
| Log | `g_ma_alarmlog` |
