# CTI / Screen-pop instellen (inkomende oproep → naam op scherm)

SalesFlow bevat een volledige CTI-koppeling: bij een inkomende oproep zoekt het
portaal het nummer op in je eigen database en toont het de naam van de beller op
het scherm van de juiste collega. Bellen zelf gaat via click-to-call in de
browser of via je softphone.

Dit werkt **zonder** dat de beller in de lokale contacten van de PC staat — de
lookup gebeurt live tegen de SalesFlow-database.

## Overzicht

```
Inkomende oproep
   → VoIP-centrale (3CX / Zadarma / Twilio / SIP-trunk)
   → stuurt het belnummer naar SalesFlow
   → SalesFlow zoekt klant/contact in de database
   → screen-pop verschijnt bij de juiste collega (sales of algemeen)
```

Er zijn **twee manieren** om te koppelen. Kies er één.

---

## Manier A — Softphone opent direct een URL (simpelst, per PC)

Werkt met **MicroSIP** (gratis, Windows), Zoiper, Linphone, 3CX-softphone.

1. Installeer de softphone en registreer hem op jullie SIP-trunk/centrale.
2. Open in SalesFlow (als collega) de portaal-URL en zorg dat je ingelogd bent
   in dezelfde browser die de pop mag tonen.
3. Zet in de softphone de "open URL bij inkomende oproep" in.

**MicroSIP:** Menu → Settings → *Contacts/Integration* →
`On incoming call` → **Open URL** →

```
https://JOUW-PORTAAL.tld/cti/popup?number=%number%
```

MicroSIP vervangt `%number%` automatisch door het beller-nummer. Er opent een
tabblad met de klantnaam + knop naar de klantkaart. Klaar.

> De reverse-lookup zelf (JSON) is ook los bruikbaar:
> `GET https://JOUW-PORTAAL.tld/api/v1/cti/lookup?number=+3231234567`

---

## Manier B — Webhook vanaf de centrale (beste voor teams)

De centrale stuurt bij elke inkomende oproep een webhook naar SalesFlow. Het
portaal bepaalt zelf welke collega de pop krijgt (sales-nummer → sales-team,
algemeen nummer → iedereen, of de eigenaar van de klant). De browser van die
collega toont de pop automatisch (poll elke 3 s, geen extensie nodig).

**Webhook-URL (in de centrale invullen):**

```
POST https://JOUW-PORTAAL.tld/api/v1/cti/incoming
Content-Type: application/x-www-form-urlencoded  (of application/json)

caller=%CALLER%      (verplicht — het beller-nummer)
called=%DIALED%      (optioneel — algemeen of sales-nummer)
agent=%EXTENSION%    (optioneel — e-mail of user-id van de opgebelde collega)
key=JOUW_GEHEIM      (aanbevolen — zie beveiliging)
```

### Voorbeeld per centrale

**3CX** — Admin console → *Settings → CRM Integration* → kies "Generic/JSON".
Zet de *URL for incoming call* op de webhook hierboven en map het veld
`Number` op `caller`.

**Zadarma** — Persoonlijk account → *Integraties → Notify/Webhooks*. Zet het
"NOTIFY_START"-event op de webhook-URL en map `caller_id` → `caller`.

**Twilio** — In de Voice-webhook van je nummer een klein TwiML/Function die
`From` en `To` doorstuurt naar `/api/v1/cti/incoming` als `caller` en `called`.

---

## Wat instellen in SalesFlow

Vul in de `settings`-tabel (of straks via Instellingen → Telefonie) in:

| Sleutel | Betekenis |
|---|---|
| `general_number` | Jullie algemene nummer (E.164, bv. `+3231234567`) |
| `sales_number` | Jullie sales-nummer — oproepen hierheen gaan naar het sales-team |
| `cti_webhook_secret` | Geheim dat de webhook/lookup moet meesturen (`key=` of header `X-CTI-Secret`) |

## Beveiliging

- Zet **altijd** een `cti_webhook_secret`. Zonder secret is het endpoint open.
- Gebruik HTTPS (staat standaard afgedwongen in `.htaccess`).
- Nummers worden genormaliseerd (`0032`, `+32`, spaties, `/`, `.`) en gematcht
  op de laatste 9 cijfers, zodat opgeslagen en inkomende formaten samenvallen.

## Uitbellen (click-to-call)

- Op **gsm/PWA**: de belknop is een `tel:`-link → één tik en de telefoon belt.
- Op **PC met MicroSIP**: gebruik een `microsip:`-link, of stel de softphone in
  als standaard-handler voor `tel:`-links.
- Voor **VoIP-bellen vanuit de browser** (naar echte nummers) is een provider
  met kosten nodig (bv. Twilio); de belknop en gesprekslogging zijn er al klaar
  voor.
