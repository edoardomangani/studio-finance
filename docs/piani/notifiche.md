# Piano: Notifiche push per le scadenze

**Specifiche**: [../specifiche/notifiche.md](../specifiche/notifiche.md)

## Stato del codebase

Da riusare:

- [DeadlineExpectation::for()](../../app/Services/DeadlineExpectation.php#L33): importo previsto, già gestisce `manual_expected_amount` per le ad-hoc. È la sola fonte dell'importo.
- [DeadlineContextBuilder::build()](../../app/Services/DeadlineContextBuilder.php#L38): non dipende da `Auth`, filtra per `user_id` preso dalla **prima** scadenza della collection. Funziona in contesto senza auth, ma va chiamato **per gruppo utente**.
- [DeadlineService::toListItem()](../../app/Services/DeadlineService.php#L251): shape riga scadenza (privato) → base per la prop `focusedDeadline`.
- [DeadlinesTable.vue](../../resources/js/pages/deadlines/DeadlinesTable.vue#L93-L99): possiede `sheetOpen`/`selectedDeadline` ed espone già `openCreate` via `defineExpose` ([L143](../../resources/js/pages/deadlines/DeadlinesTable.vue#L143)); [Index.vue](../../resources/js/pages/deadlines/Index.vue#L110) lo chiama con `table?.openCreate()`.
- [useInstallPrompt.ts](../../resources/js/composables/useInstallPrompt.ts): rilevamento standalone/iOS + dismiss su `localStorage`. [InstallPrompt.vue](../../resources/js/components/InstallPrompt.vue) è il banner di riferimento per stile e struttura (montato in [AppSidebarLayout.vue:57](../../resources/js/layouts/app/AppSidebarLayout.vue#L57)).
- [useTwoFactorAuth.ts](../../resources/js/composables/useTwoFactorAuth.ts): unico uso esistente di `useHttp`, è il pattern per subscribe/unsubscribe/test.
- `vue-sonner` (`import { toast } from 'vue-sonner'`) per i toast client-side; [FlashesToast](../../app/Concerns/FlashesToast.php) solo per i redirect (qui non serve).
- [HandleInertiaRequests::share()](../../app/Http/Middleware/HandleInertiaRequests.php#L38) + tipi in [global.d.ts](../../resources/js/types/global.d.ts#L22) per la shared prop.
- Throttle già in uso su settings (`throttle:6,1` / `throttle:30,1`) in [routes/settings.php](../../routes/settings.php#L45).
- Test vicini da imitare: [DeadlineControllerTest](../../tests/Feature/DeadlineControllerTest.php), [DeadlineExpectationTest](../../tests/Feature/DeadlineExpectationTest.php), [SharedNavPropsTest](../../tests/Feature/SharedNavPropsTest.php), [TenancyCrossUserTest](../../tests/Feature/TenancyCrossUserTest.php).

Da creare da zero: tutto il resto (pacchetto, tabelle, service, notification, comando, endpoint, SW handler, sezione, banner). `app/Console/Commands/` è vuota e `routes/console.php` ha solo `inspire`.

Convenzioni: service layer obbligatorio (controller sottili), form request per la validazione, commenti brevi in italiano, PHPDoc corti, `make:` artisan con `--no-interaction`.

## Diff rispetto alle specifiche

- **Importo.** Le specifiche dicono "`manual_expected_amount` se valorizzato, altrimenti `DeadlineExpectation`". In realtà `DeadlineExpectation` usa già il manuale per le ad-hoc e lo ignora per le standard (dove l'importo è calcolato). Quindi si chiama solo `DeadlineExpectation::for()`, senza un ramo manuale separato: è lo stesso valore della UI, che è la regola che conta.
- **Dipendenza da `Auth` del builder.** Le specifiche la segnalano come rischio da verificare. In realtà non c'è dipendenza da `Auth`: le query filtrano per `user_id` esplicito ([YearAmountsLoader](../../app/Services/YearAmountsLoader.php#L35)). Però una collection con scadenze di più utenti produrrebbe dati sbagliati, perché lo `user_id` viene preso dalla prima. Nel piano: `groupBy('user_id')` e una `build()` per gruppo, niente `Auth::setUser`.
- **Dove sta lo sheet.** Le specifiche dicono che `Index.vue` apre il `DeadlineSheet`. In realtà lo sheet vive in `DeadlinesTable.vue`. Nel piano: aggiungere `openDeadline(item)` a `defineExpose` e chiamarlo da `Index.vue` in `onMounted` quando `focusedDeadline` non è null.
- **`focusedDeadline`.** `toListItem` è privato e richiede un `DeadlineContext`. Nel piano: nuovo metodo pubblico `DeadlineService::forFocus(int $id): ?array` (find con tenancy + build del context sulla singola scadenza + `toListItem`).
- **Banner Dashboard.** Le specifiche lo trattano come banner nuovo. In realtà c'è già `InstallPrompt` nel layout, solo su mobile e non in standalone. Le condizioni sono mutuamente esclusive (il push banner esiste solo in standalone), quindi non si sovrappongono. Nel piano: estrarre `isStandalone()`/`isIos()` da `useInstallPrompt` in un helper condiviso, invece di duplicare l'euristica.
- **Timezone.** Le specifiche dicono "Europe/Rome". In realtà `app.timezone` è `UTC` e `DeadlineService` usa `Carbon::today()` UTC. Nel piano: `CarbonImmutable::today('Europe/Rome')` iniettabile nel service e confronto per **stringa data** (`whereDate`/`whereIn` su `due_at`), mai `diffInDays` tra istanti in timezone diverse.
- **Pulizia alla cancellazione utente.** Le specifiche propongono "`ProfileController@destroy` o observer". In realtà la morph `push_subscriptions` non ha FK. Nel piano: `deleting` sul modello `User`, che copre ogni punto di cancellazione, non solo [ProfileController@destroy](../../app/Http/Controllers/Settings/ProfileController.php#L73).

## Step ordinati

### Gruppo 1 — Dipendenza, schema, modelli

1. `composer require laravel-notification-channels/webpush`, pubblicare migration e config, `php artisan webpush:vapid` in locale; aggiungere le chiavi VAPID + `NOTIFICATIONS_DISPATCH_TOKEN` a `.env.example` e `config/services.php`.
   Gate: `composer show laravel-notification-channels/webpush` risolve su Laravel 13; `php artisan config:show webpush` mostra le chiavi.
2. Creare la migration `deadline_notifications` (`make:model DeadlineNotification -mf`); aggiungere `HasPushSubscriptions` e l'hook `deleting` su `User`.
   Gate: `php artisan migrate` ok; `database-schema` mostra l'unique `(deadline_id, sent_on)`.

### Gruppo 2 — Dominio invio (service, notification, comando)

3. `DeadlineReminderService`: query idonee (RN1–RN3) lato SQL su date Europe/Rome, esclusione delle già loggate (`whereDoesntHave` sul log del giorno), `with(['annualExpense.year', 'user.pushSubscriptions'])`, raggruppamento per utente → context → importo → `body()`.
   Gate: test Pest con `travelTo` e dataset su 14/7/3/1/0/−1/−5/2/8 giorni e su stati `completed`/`not_due`/archiviata.
4. `DeadlineReminder` + `TestPush` (`WebPushMessage` con title/body/tag/data.url); invio sincrono con try/catch per singola scadenza → `Log::warning`, poi scrittura del log.
   Gate: `Notification::fake()` + `assertSentTo` con payload controllato; test di idempotenza (seconda `send()` = 0) e "utente senza subscription → nessun log".
5. Comando `deadlines:send-reminders` (sottile, chiama il service, stampa il conteggio).
   Gate: `php artisan deadlines:send-reminders` in locale; test con `artisan()->expectsOutputToContain`.

### Gruppo 3 — HTTP: endpoint cron, subscription, deep link, shared prop

6. `NotificationDispatchController` (Bearer + `hash_equals`, 404 se il token non è configurato) e route con esclusione CSRF (`validateCsrfTokens(except:)` in [bootstrap/app.php](../../bootstrap/app.php), verificare l'API con `search-docs`).
   Gate: test 404/401/200 con conteggio JSON.
7. `PushSubscriptionService` + `PushSubscriptionController` + `StorePushSubscriptionRequest`, route su settings.
   Gate: test di upsert per endpoint, riassegnazione cross-user, delete solo dei propri, test con `TestPush` inviata, throttle; `php artisan route:list --name=notifications`.
8. `DeadlineService::forFocus()` + query `deadline` in `DeadlineController@index`.
   Gate: test in `DeadlineControllerTest` (propria → prop valorizzata anche con filtro che la esclude; di un altro utente, inesistente o archiviata → null).
9. Shared prop `webPush.publicKey` + tipo TS.
   Gate: estendere `SharedNavPropsTest`; `npm run types` (o `vue-tsc`) verde.

### Gruppo 4 — Frontend

10. `public/sw-push.js` + `workbox.importScripts` in [vite.config.ts](../../vite.config.ts).
    Gate: `npm run build` → `public/build/sw.js` contiene `importScripts("/sw-push.js")`.
11. Helper standalone/iOS estratto da `useInstallPrompt` + `usePushSubscription` (stato dispositivo, re-sync della subscription al mount, subscribe/unsubscribe/test via `useHttp`). `InstallPrompt` deve continuare a funzionare.
    Gate: `npm run lint` + type check verdi.
12. `NotificationsSection.vue` in `Profile.vue`; `PushBanner.vue` in `Dashboard.vue`; `openDeadline` esposto da `DeadlinesTable` e chiamato da `Index.vue`.
    Gate: check visivo in Herd con `npm run build` (il SW esiste solo in build), sia desktop Chrome sia viewport mobile: stati "Non attive" → "Attive", prova ricevuta, tap → sheet aperto.

### Gruppo 5 — Produzione

13. Impostare le 4 env su Render (MCP `update_environment_variables`), push, migration manuale su Neon, job cron-job.org alle 08:00 Europe/Rome con header `Authorization`.
    Gate: `curl -X POST -H "Authorization: Bearer …"` sull'URL di prod → 200; notifica di prova ricevuta su iPhone con la PWA installata; il giorno dopo, log cron-job.org 200.

## Rischi e punti di attenzione

- **Compatibilità del pacchetto con Laravel 13 / PHP 8.4.** Se `composer require` non risolve, il fallback è `minishlink/web-push` diretto con tabella propria: il resto del piano non cambia, cambiano solo modello e canale. Va verificato prima di tutto (step 1).
- **Tenancy in contesto senza auth.** Nella richiesta del cron `Auth::check()` è false, quindi i global scope sono spenti. Ogni query del service deve filtrare per `user_id` in modo esplicito o per relazione. Serve un test con due utenti che verifichi che nessuno riceve scadenze o importi altrui.
- **N+1.** L'invio per scadenza e la lettura delle subscription per utente vanno precaricati (`user.pushSubscriptions`). Il builder fa una query siblings per gruppo: accettabile perché i gruppi sono pochi.
- **Indice.** La query giornaliera filtra `status` + `due_at`: verificare se esiste un indice su `deadlines (user_id, status, due_at)`. Con i volumi attuali non blocca, va solo annotato.
- **Timeout della richiesta.** L'invio è sincrono, ogni push è una chiamata HTTPS: con decine di scadenze si resta nei secondi, ma il timeout di cron-job.org è 30s. Accettabile oggi. Se cresce, il primo passo è il flush batch del pacchetto.
- **Il SW si aggiorna solo in build.** In dev non c'è SW, quindi il frontend push si prova solo con `npm run build` in Herd (https ok). `sw-push.js` non ha hash: il browser ricontrolla gli `importScripts`, ma dopo un deploy l'iPhone può tenere il vecchio handler fino al riavvio della PWA.
- **iOS.** Il permesso va chiesto dentro il gesto, senza `await` prima di `requestPermission()`. Una sola chiamata, altrimenti Safari la ignora.
- **CSRF / session sul dispatch.** La route sta nel gruppo `web` (sessione inutile ma innocua). Assicurarsi che non finisca dentro `auth`/`onboarded`.
- **Deploy.** Migration manuale **prima** di abilitare il job cron, altrimenti 500 sulla tabella log mancante. Le env VAPID vanno impostate prima del deploy del frontend, altrimenti la public key è vuota e il subscribe fallisce.
- **File grandi.** [Profile.vue](../../resources/js/pages/settings/Profile.vue) (406 righe) e [Dashboard.vue](../../resources/js/pages/Dashboard.vue) (362) ricevono solo un import + un tag. La logica sta nei componenti estratti.

## Quality gates

- **Gruppo 1–2 (schema, dominio invio)** → `review-backend`
- **Gruppo 3 (HTTP: endpoint cron, subscription, deep link)** → `review-backend`, `review-security` (Bearer token, CSRF exempt, cross-user, throttle)
- **Gruppo 4 (frontend)** → `review-frontend`, `review-component-size` (Profile, Dashboard, DeadlinesTable)
- **Feature completa (gate finale)** → `review-feature`

## Domande aperte

Nessuna.

## Ordine di commit suggerito

1. `feat(notifications): pacchetto webpush, tabella log e modelli`: gruppo 1.
2. `feat(notifications): service e comando promemoria scadenze`: step 3–5 con test.
3. `feat(notifications): endpoint dispatch per il cron`: step 6 con test.
4. `feat(notifications): subscription push e notifica di prova`: step 7 e 9 con test.
5. `feat(deadlines): deep link ?deadline= apre lo sheet`: step 8 + `openDeadline` frontend.
6. `feat(notifications): service worker, sezione Impostazioni e banner Dashboard`: step 10–12.
