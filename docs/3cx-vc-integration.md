# 3CX CFD — vejledning (ABA Service)

Opsæt **Call Flow Designer** så ABAS får besked når en **agent besvarer** et opkald — **ikke** mens det står i kø.

Reference: [3CX CFD Workspace](https://www.3cx.com/docs/manual/cfd-workspace/)

---

## Hvad I skal sende

**To HTTP POST-kald** pr. besvaret opkald — samme `session.id` i begge:

| Tidspunkt | `event` | Hvor i CFD |
|-----------|---------|------------|
| Agent besvarer | `connected` | Main Flow |
| Opkald lægges på | `ended` | Disconnect Handler Flow |

**Ingen HTTP** når opkaldet kun står i kø.

---

## Modtager (ABAS)

| | |
|---|---|
| **URL** | `https://teknikweb2.trekantbrand.dk/api/v1/3cx/call` |
| **Method** | `POST` |
| **Content-Type** | `application/json` |
| **Authorization** | `Bearer <API-token fra ABAS admin>` *(anbefalet)* |

Token oprettes i ABAS under **Admin → API-tokens** (rolle: Vagtcentral).

**Hvis CFD ikke kan sende custom headers** (fx kun simpel HTTP Request), sæt token i URL i stedet:

```
https://teknikweb2.trekantbrand.dk/api/v1/3cx/call?key=INDSÆT_API_TOKEN_HER
```

Brug samme token som til Bearer. *Bemærk:* `?key=` kan ende i webserver-log — brug Bearer hvis 3CX understøtter det.

**Wait for response:** Nej (eller kør HTTP i **Parallel Execution**).

---

## JSON-body — agent besvarer

Byg body med **CONCATENATE** i Expression Editor (fx-knappen).  
**Ikke** `{@session.ani}` direkte i en JSON-streng — det virker ikke.

**Færdigt JSON-eksempel:**

```json
{
  "event": "connected",
  "call_id": "<session.id>",
  "caller_number": "<session.ani>",
  "caller_name": "<session.callerName>",
  "queue": "Vagtcentral",
  "did": "<session.dnis>"
}
```

**CONCATENATE til Content-feltet:**

```
CONCATENATE(
  "{",
  "\"event\":\"connected\",",
  "\"call_id\":\"", session.id, "\",",
  "\"caller_number\":\"", session.ani, "\",",
  "\"caller_name\":\"", session.callerName, "\",",
  "\"queue\":\"Vagtcentral\",",
  "\"did\":\"", session.dnis, "\"",
  "}"
)
```

**Authorization header** (Expression):

```
CONCATENATE("Bearer ", "INDSÆT_API_TOKEN_HER")
```

---

## JSON-body — opkald afsluttet

Placeres i **Disconnect Handler Flow** (kører når opkaldet afsluttes).

```json
{
  "event": "ended",
  "call_id": "<session.id>",
  "caller_number": "<session.ani>"
}
```

**CONCATENATE:**

```
CONCATENATE(
  "{",
  "\"event\":\"ended\",",
  "\"call_id\":\"", session.id, "\",",
  "\"caller_number\":\"", session.ani, "\"",
  "}"
)
```

Samme URL, method og Content-Type som ved `connected` (inkl. `?key=` eller Bearer).

---

## Session-variabler

| 3CX-variabel | JSON-felt | Påkrævet |
|--------------|-----------|----------|
| `session.id` | `call_id` | Ja — skal være **identisk** i `connected` og `ended` |
| `session.ani` | `caller_number` | Ja |
| `session.callerName` | `caller_name` | Nej |
| `session.dnis` | `did` | Nej |
| *(fast tekst)* | `queue` | Nej — fx `"Vagtcentral"` |

---

## Flow-placering

```
[Opkald i kø]
      │  ← ingen HTTP her
      ▼
[Agent besvarer]
      │
      ▼
  HTTP POST  event=connected
      │
      ▼
[Samtale …]
      │
      ▼  Disconnect Handler Flow
  HTTP POST  event=ended
```

1. Hæng callflow på **vagtcentral-køen**.
2. **Main Flow:** HTTP Requests **efter** agent har taget opkaldet (ikke ved kø-indgang).
3. **Disconnect Handler Flow:** HTTP Requests med `ended`.

---

## HTTP Requests — indstillinger (begge kald)

| Felt | Værdi |
|------|--------|
| Request type | POST |
| URI | `https://teknikweb2.trekantbrand.dk/api/v1/3cx/call` — eller `…/3cx/call?key=TOKEN` hvis Bearer-header ikke virker |
| Content type | `application/json` |
| Content | CONCATENATE (se ovenfor) |
| Custom header | `Authorization` = `CONCATENATE("Bearer ", "TOKEN")` — udelad hvis token er i URL |
| Wait for response | Nej |

Alternativt: komponenten **Web Service REST** med Authentication = Bearer og samme body. Uden header-auth: sæt `?key=TOKEN` på URI.

---

## Tips

- **Parallel Execution:** læg HTTP-komponenten i parallel gren så opkaldet ikke venter på ABAS.
- **Logger:** log `session.id` og `session.ani` før HTTP — tjek `3CXCallFlow.log` ved fejl.
- **`ringing` sendes ikke** — ABAS ignorerer kø-opkald med vilje.

---

## Tjekliste

- [ ] API-token indsat i Authorization
- [ ] HTTP ved **agent besvarer** med `event=connected`
- [ ] HTTP i **Disconnect Handler** med `event=ended`
- [ ] Ingen HTTP ved kø-indgang
- [ ] `session.id` bruges som `call_id` i begge kald
- [ ] JSON bygget med **CONCATENATE**, ikke plain `{@variabel}`
