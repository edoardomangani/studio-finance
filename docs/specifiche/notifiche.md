# Notifiche push per le scadenze

## Contesto

Le scadenze fiscali (F24, Inarcassa, bolli, adempimenti) oggi si vedono solo aprendo l'app. L'utente usa StudioFinance soprattutto come PWA installata su iPhone e vuole ricevere promemoria push prima di ogni scadenza, e un sollecito quotidiano per quelle scadute e non ancora chiuse.

Infrastruttura esistente rilevante:

- PWA già configurata con `vite-plugin-pwa` in modalità `generateSW` ([vite.config.ts](../../vite.config.ts)); il SW è servito da Laravel su `/build/serviceworker.js` con `Service-Worker-Allowed: /` ([routes/web.php](../../routes/web.php)) e registrato solo in produzione ([resources/js/app.ts](../../resources/js/app.ts)).
- Produzione su Render free (nessuno scheduler, nessun queue worker, spin-down notturno). L'app è tenuta sveglia dalle 07:00 alle 21:00 dal warm-up Cloudflare + ping `*/5` di cron-job.org.
- Le scadenze sono `deadlines` (istanze) con `status` `open` / `completed` / `not_due`, `kind` `payment` / `fulfillment`, `due_at` (date), soft delete, tenancy via `BelongsToUser`.
- Web Push su iOS funziona solo per PWA aggiunte alla schermata Home (iOS 16.4+) e il permesso va chiesto da un gesto esplicito dell'utente.

## Flusso utente

### Attivazione

1. L'utente apre **Impostazioni → sezione "Notifiche"** (nuova `FormSection` nello stack di [Profile.vue](../../resources/js/pages/settings/Profile.vue), `id="notifications"`).
2. La sezione mostra lo stato del **dispositivo corrente**, uno tra:
   - **Non supportato** — il browser non ha `PushManager`/`Notification`. Testo: spiegazione breve, nessuna azione.
   - **iOS non installata** — iOS/iPadOS in Safari, non in modalità standalone. Testo con istruzioni: "Per ricevere le notifiche su iPhone aggiungi StudioFinance alla schermata Home: Condividi → Aggiungi alla schermata Home, poi apri l'app da lì."
   - **Bloccate** — `Notification.permission === 'denied'`. Testo: "Le notifiche sono bloccate per questa app. Riattivale dalle impostazioni del dispositivo." Nessun pulsante.
   - **Non attive** — supportate, nessuna subscription su questo dispositivo. Pulsante **"Attiva notifiche"**.
   - **Attive** — subscription presente. Pulsanti **"Invia notifica di prova"** e **"Disattiva"**.
3. Tap su "Attiva notifiche" → `Notification.requestPermission()` (nel gesto) → se `granted`, `pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: VAPID_PUBLIC_KEY })` → POST della subscription al server → stato "Attive", toast "Notifiche attivate su questo dispositivo".
4. Se il permesso viene negato → stato "Bloccate", nessun toast di errore.
5. "Disattiva" → `subscription.unsubscribe()` + DELETE al server → stato "Non attive", toast "Notifiche disattivate su questo dispositivo".
6. "Invia notifica di prova" → POST al server, che invia una push a **tutte** le subscription dell'utente → toast "Notifica di prova inviata".

### Banner in Dashboard

7. In Dashboard compare un banner chiudibile "Ricevi un promemoria prima di ogni scadenza" con pulsante **"Attiva"** (stesso flusso del punto 3) e **X** per chiuderlo, **solo se tutte vere**:
   - push supportate e app in modalità standalone (PWA installata, qualunque OS);
   - `Notification.permission === 'default'`;
   - nessuna subscription attiva su questo dispositivo;
   - il banner non è stato chiuso su questo dispositivo.
8. La chiusura (X) o l'attivazione riuscita nascondono il banner per sempre su quel dispositivo (`localStorage`, chiave `sf:push-banner-dismissed`, lettura/scrittura in try/catch).

### Ricezione

9. Ogni giorno alle 08:00 (Europe/Rome) cron-job.org chiama l'endpoint di invio; il server invia una notifica **per ogni scadenza** che quel giorno rientra in una regola di promemoria (vedi regole), a tutte le subscription del proprietario.
10. Tap sulla notifica → si apre (o si mette a fuoco, se già aperta) l'app su `/deadlines?deadline={id}`, che apre il `DeadlineSheet` di quella scadenza.

## Regole di business

- **RN1 — Scadenze idonee:** solo `deadlines` con `status = open`, non soft-deleted, di entrambi i `kind` (`payment` e `fulfillment`).
- **RN2 — Promemoria prima della scadenza:** una notifica quando i giorni mancanti a `due_at` (rispetto alla data odierna Europe/Rome) sono esattamente **14, 7, 3, 1 o 0**.
- **RN3 — Sollecito scadute:** se `due_at` è passata e la scadenza è ancora `open`, una notifica **ogni giorno** finché non diventa `completed` o `not_due` (o viene archiviata).
- **RN4 — Una per scadenza:** ogni scadenza genera la propria notifica; nessun raggruppamento. Per costruzione una scadenza riceve al massimo una notifica al giorno.
- **RN5 — Idempotenza:** se l'endpoint viene chiamato più volte nello stesso giorno, ogni scadenza viene notificata una sola volta per data (log con unicità `deadline_id + sent_on`).
- **RN6 — Nessun recupero:** se l'invio di un giorno non parte (cron fallito, app giù), i promemoria di quel giorno vanno persi; il giorno dopo si applicano solo le regole del giorno dopo. Le scadute vengono comunque ri-sollecitate da RN3.
- **RN7 — Contenuto:**
  - Titolo: `name` della scadenza.
  - Corpo: `<quando> (<gg/mm>)` + ` · € <importo>` se presente.
    - `<quando>`: `Tra N giorni` (14/7/3), `Domani` (1), `Oggi` (0), `Scaduta ieri` (−1), `Scaduta da N giorni` (≤ −2).
    - Per lo scaduto la data tra parentesi è sempre `due_at`.
  - Importo: solo per `kind = payment`; è `manual_expected_amount` se valorizzato, altrimenti l'importo previsto da `DeadlineExpectation` (lo stesso mostrato in UI). Se entrambi null → nessun importo. Formato italiano (`€ 2.340,00`).
  - `tag`: `deadline-{id}` — una nuova notifica della stessa scadenza sostituisce la precedente nel centro notifiche (niente pila di solleciti).
  - `url`: `/deadlines?deadline={id}`.
- **RN8 — Destinatari:** tutte le subscription push dell'utente proprietario della scadenza. Utenti senza subscription vengono saltati (nessun log scritto).
- **RN9 — Notifica di prova:** titolo "Notifica di prova", corpo "Le notifiche funzionano su questo dispositivo.", `url` `/dashboard`. Non scrive log.
- **RN10 — Subscription scadute:** se il push service risponde 404/410, la subscription viene eliminata (gestito dal pacchetto).
- **RN11 — Fuso orario:** tutti i calcoli "oggi / giorni mancanti" usano `Europe/Rome`, indipendentemente dal timezone dell'app.

## Dati e relazioni

### Dipendenza nuova (approvata)

- `laravel-notification-channels/webpush` (usa `minishlink/web-push`). Pubblica la migration `push_subscriptions` e il config `webpush.php`.

### Tabelle

- **`push_subscriptions`** (dal pacchetto, morph `subscribable` → `User`): `endpoint` (unique), `public_key`, `auth_token`, `content_encoding`, timestamps.
- **`deadline_notifications`** (nuova) — log degli invii per l'idempotenza:
  - `id`
  - `user_id` FK → users, cascade on delete
  - `deadline_id` FK → deadlines, cascade on delete
  - `days_until_due` int (14/7/3/1/0 o negativo per lo scaduto) — informativo
  - `sent_on` date (data Europe/Rome dell'invio)
  - timestamps
  - unique `(deadline_id, sent_on)`
- Nessuna modifica a `deadlines` o `users` (colonne).

### Modelli e codice

- `User`: trait `HasPushSubscriptions` del pacchetto.
- Nuovo modello `DeadlineNotification` (con `BelongsToUser`, relazione `deadline()`).
- Nuova notification `App\Notifications\DeadlineReminder` (canale `WebPushChannel`, **non** `ShouldQueue`: su Render non c'è un worker, l'invio è sincrono nella richiesta). Idem `App\Notifications\TestPush`.
- Nuovo `App\Services\DeadlineReminderService` (convenzione service layer):
  - `dueToday(CarbonImmutable $today): Collection` — scadenze idonee per RN1–RN3 non ancora loggate per `$today`, **senza** tenancy scope (contesto senza auth: query con `withoutGlobalScopes()` limitata ai soft delete, raggruppate per `user_id`), con eager load di quanto serve al calcolo dell'importo.
  - `send(CarbonImmutable $today): int` — per ogni scadenza invia `DeadlineReminder` al proprietario e scrive il log nella stessa unità; ritorna il numero di scadenze notificate.
  - `body(Deadline, CarbonImmutable $today, ?float $amount): string` — costruzione del testo RN7, testabile in isolamento.
- Nuovo `App\Services\PushSubscriptionService`: `subscribe(User, array $data)` (upsert per `endpoint`), `unsubscribe(User, string $endpoint)`, `sendTest(User)`.
- Importo previsto: riusare `DeadlineContextBuilder::build()` + `DeadlineExpectation::for()` per utente. **Da verificare in implementazione** che il builder non dipenda da `Auth::id()`/global scope; se sì, va eseguito con l'utente impostato (`Auth::setUser($user)` per gruppo) o adattato a ricevere lo user esplicitamente.
- Comando Artisan `deadlines:send-reminders` che invoca il service (utile in locale e nei test); l'endpoint chiama lo stesso service.

### Config / env

- `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY` (generate con `php artisan webpush:vapid`), `VAPID_SUBJECT=mailto:<email>`.
- `NOTIFICATIONS_DISPATCH_TOKEN` — segreto per l'endpoint del cron, letto da `config('services.notifications.dispatch_token')`.
- `VAPID_PUBLIC_KEY` esposta al frontend come shared prop Inertia (`webPush.publicKey`) in `HandleInertiaRequests`.
- Su Render: le 4 env vanno impostate (via MCP `update_environment_variables`); la migration va lanciata a mano dopo il deploy (no migrate-on-boot).

### Route

| Metodo | URI | Nome | Middleware |
| --- | --- | --- | --- |
| POST | `settings/notifications/subscriptions` | `settings.notifications.subscribe` | auth, verified, throttle:30,1 |
| DELETE | `settings/notifications/subscriptions` | `settings.notifications.unsubscribe` | auth, verified, throttle:30,1 |
| POST | `settings/notifications/test` | `settings.notifications.test` | auth, verified, throttle:6,1 |
| POST | `internal/notifications/dispatch` | `notifications.dispatch` | throttle:10,1, verifica Bearer token, esclusa da CSRF |

- Subscribe/unsubscribe sono chiamate con il client HTTP di Inertia (`useHttp`) e rispondono JSON (204), non redirect: non devono ricaricare la pagina.
- Il body di subscribe è il JSON di `PushSubscription.toJSON()`: `endpoint` (url https, required), `keys.p256dh`, `keys.auth` (required string), `contentEncoding` opzionale (default `aes128gcm`). Validazione in `StorePushSubscriptionRequest`.

### Service worker

- Passare `workbox.importScripts: ['/sw-push.js']` in `VitePWA` (resta `generateSW`, nessun cambio di strategia).
- Nuovo file statico `public/sw-push.js` con:
  - `push` → `self.registration.showNotification(title, { body, tag, icon: '/pwa-192x192.png', badge, data: { url } })`.
  - `notificationclick` → chiude la notifica; se esiste un client della stessa origin lo mette a fuoco e lo naviga su `data.url`, altrimenti `clients.openWindow(data.url)`.

### Deep link scadenza

- `DeadlineController@index` accetta `?deadline={id}`: se la scadenza esiste ed è dell'utente, la passa come prop `focusedDeadline` (stessa shape delle righe tabella) indipendentemente dai filtri anno/stato; [Index.vue](../../resources/js/pages/deadlines/Index.vue) apre il `DeadlineSheet` al mount con quella scadenza. Id inesistente/di altri/archiviato → prop null, pagina normale, nessun errore.

## Permessi e ruoli

- Ogni utente gestisce solo le proprie subscription (upsert/delete filtrati per l'utente autenticato; un `endpoint` già registrato da un altro utente viene riassegnato all'utente corrente, perché il dispositivo ora appartiene a lui).
- Le notifiche di una scadenza vanno solo alle subscription del suo `user_id`.
- L'endpoint `internal/notifications/dispatch` non usa sessione: accetta solo `Authorization: Bearer <NOTIFICATIONS_DISPATCH_TOKEN>` (confronto con `hash_equals`). Token mancante/errato → 401. Se il token non è configurato in env → 404 (endpoint disattivato).
- Il deep link `?deadline=` rispetta la tenancy (`BelongsToUser`).

## Corner case e gestione errori

- Se l'endpoint di invio viene chiamato due volte nello stesso giorno → la seconda chiamata non invia nulla (RN5), risponde 200 con conteggio 0.
- Se una scadenza viene completata/marcata non dovuta dopo l'invio delle 08:00 → nessun effetto sulla notifica già consegnata; dal giorno dopo non viene più sollecitata.
- Se la `due_at` di una scadenza ad-hoc viene modificata → le regole si ricalcolano sulla nuova data dal giorno dopo; il log con `sent_on` passati non blocca nulla.
- Se l'invio verso una subscription fallisce con 404/410 → subscription eliminata (RN10); il log della scadenza viene scritto comunque se almeno un tentativo di invio è stato fatto (non si ritenta in giornata).
- Se l'invio fallisce per altri errori (5xx push service, timeout) → errore loggato (`Log::warning`), si prosegue con le scadenze successive; il log della scadenza viene scritto comunque (nessun retry, coerente con RN6).
- Se l'utente non ha subscription → scadenze saltate, nessun log (se attiva le notifiche in giornata dopo le 08:00, riceverà dal giorno dopo).
- Se l'app su Render è giù alle 08:00 → cron-job.org riceve errore/timeout; promemoria del giorno persi (RN6). Nessun meccanismo di retry lato app.
- Se lo stesso dispositivo si iscrive di nuovo (es. dopo reinstallazione PWA) → upsert per `endpoint`, nessun duplicato.
- Se il permesso viene revocato dalle impostazioni di sistema → al prossimo accesso alla sezione lo stato diventa "Bloccate"; la subscription lato server resta finché il push service non risponde 410.
- Se la subscription locale esiste ma il server non la conosce (es. DB ripulito) → all'apertura della sezione Notifiche il client re-invia la subscription corrente (POST idempotente), così lo stato è sempre allineato.
- Se il SW non è registrato (dev, o registrazione fallita) → la sezione mostra "Non supportato"; in dev le push non sono testabili da browser, solo via test Pest.
- Utente archiviato/eliminato → cascade sulle tabelle `push_subscriptions` (morph: pulizia esplicita in `ProfileController@destroy` o observer) e `deadline_notifications`.

## UX e feedback

- Sezione Notifiche: pulsanti con stato di loading (`:disabled` + spinner) durante permesso/subscribe/HTTP; un solo pulsante attivo alla volta.
- Toast (Italiano):
  - attivazione: "Notifiche attivate su questo dispositivo"
  - disattivazione: "Notifiche disattivate su questo dispositivo"
  - prova: "Notifica di prova inviata"
  - errore di rete / server su subscribe, unsubscribe o prova: "Non è stato possibile completare l'operazione. Riprova."
- Permesso negato al prompt → nessun toast, solo cambio di stato a "Bloccate".
- Banner Dashboard: sopra il contenuto, chiudibile, non bloccante; durante l'attivazione il pulsante "Attiva" va in loading; a successo il banner sparisce + toast di attivazione.
- Nessun redirect: tutte le azioni restano sulla pagina corrente.
- Copy della sezione (sottotitolo): "Ricevi un promemoria 14, 7, 3 e 1 giorno prima di ogni scadenza, il giorno stesso e ogni giorno finché resta aperta dopo la data."

## Fasi di implementazione

### Fase 1: Notifiche push scadenze

- **Scope**: tutto quanto sopra — dipendenza + VAPID, tabelle, service/notification/comando, endpoint cron, route subscription/test, shared prop, SW push handler, sezione Notifiche in Impostazioni, banner Dashboard, deep link `?deadline=`, test Pest (regole RN1–RN7 con data fissata, idempotenza, auth dell'endpoint, subscribe/unsubscribe/test, deep link), setup produzione (env Render, migration manuale, job cron-job.org `POST https://studio-finance.onrender.com/internal/notifications/dispatch` alle 08:00 Europe/Rome con header `Authorization`).
- **File coinvolti**:
  - `composer.json`, `config/webpush.php`, `config/services.php`, `.env.example`
  - `database/migrations/*_create_push_subscriptions_table.php` (pacchetto), `database/migrations/*_create_deadline_notifications_table.php`
  - `app/Models/User.php`, `app/Models/DeadlineNotification.php`, `database/factories/DeadlineNotificationFactory.php`
  - `app/Notifications/DeadlineReminder.php`, `app/Notifications/TestPush.php`
  - `app/Services/DeadlineReminderService.php`, `app/Services/PushSubscriptionService.php`
  - `app/Console/Commands/SendDeadlineReminders.php`
  - `app/Http/Controllers/Settings/PushSubscriptionController.php`, `app/Http/Controllers/NotificationDispatchController.php`, `app/Http/Requests/Settings/StorePushSubscriptionRequest.php`
  - `app/Http/Controllers/DeadlineController.php` (deep link), `app/Http/Middleware/HandleInertiaRequests.php`, `bootstrap/app.php` (esclusione CSRF)
  - `routes/settings.php`, `routes/web.php`
  - `vite.config.ts`, `public/sw-push.js`
  - `resources/js/composables/usePushSubscription.ts` (stato dispositivo + subscribe/unsubscribe/test)
  - `resources/js/pages/settings/NotificationsSection.vue` (estratta, inclusa in `Profile.vue`), `resources/js/pages/settings/Profile.vue`
  - `resources/js/pages/dashboard/PushBanner.vue`, `resources/js/pages/Dashboard.vue`
  - `resources/js/pages/deadlines/Index.vue`
  - `tests/Feature/DeadlineReminderTest.php`, `tests/Feature/PushSubscriptionTest.php`, `tests/Feature/NotificationDispatchTest.php`, test deep link in quello esistente delle scadenze
- **Dipende da**: —
