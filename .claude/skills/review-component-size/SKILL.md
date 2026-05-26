---
name: review-component-size
description: "Analizza file Vue frontend per identificare componenti troppo grandi e proporre come suddividerli in sotto-componenti, composables e utility. Invoca questa skill quando l'utente chiede di controllare se un file Vue va spezzato, se un componente e' troppo grande, se serve estrarre sotto-componenti, o quando menziona 'file troppo lungo', 'mega file', 'split component', 'componente enorme'. Attiva anche quando l'utente passa un path a un file .vue e chiede un'analisi sulla dimensione o complessita'."
allowed-tools: Read, Grep, Glob, Bash
argument-hint: "<path-file-vue> [path-file-vue...]"
---

# Review Component Size

Analizza file Vue per capire se sono troppo grandi e come suddividerli in componenti piu' piccoli e manutenibili.

## Scope

1. `$ARGUMENTS` deve contenere uno o piu' path a file `.vue` — leggili tutti
2. Se `$ARGUMENTS` e' vuoto, cerca i file Vue piu' grandi nel progetto con `wc -l` e analizza quelli sopra soglia
3. Per ogni file nello scope, leggi il contenuto completo

## Soglie

| Zona | Righe totali file | Significato |
|------|-------------------|-------------|
| OK | < 300 | Nessun intervento necessario |
| Attenzione | 300–500 | Valutare se ci sono sezioni estraibili |
| Critico | > 500 | Split fortemente consigliato |

Queste soglie sono indicative: un file di 400 righe con responsabilita' chiara e coesa puo' essere accettabile, mentre uno di 250 righe con 3 responsabilita' miste merita attenzione.

## Analisi

Per ogni file, valuta queste dimensioni di complessita'. L'obiettivo non e' applicare regole meccaniche ma capire se il componente ha troppe responsabilita' e dove si trovano i confini naturali di separazione.

### 1. Sezioni template isolabili

Cerca nel `<template>` blocchi che formano unita' visive e logiche autonome:
- Sezioni con `v-if`/`v-else` che gestiscono stati diversi della pagina (empty state, loading, error, content)
- Gruppi di elementi ripetuti con pattern simile (card, righe tabella custom, item lista)
- Modali, dialog, drawer, sidebar — qualunque overlay che ha il suo stato e il suo markup
- Form distinti o sezioni di form con validazione propria
- Header/footer/toolbar con logica propria

Per ogni blocco candidato, indica le righe di inizio-fine nel template e la responsabilita' che copre.

### 2. Logica script estraibile in composables

Nello `<script setup>`, cerca cluster di stato e funzioni correlati tra loro ma indipendenti dal resto:
- Gruppi di `ref` + `computed` + funzioni che operano sullo stesso dominio (es. filtri, paginazione, form validation, gestione modale)
- `watch`/`watchEffect` con logica complessa (>5 righe) che potrebbe vivere in un composable dedicato
- Fetch di dati + trasformazione + stato di loading che formano un'unita'
- Logica duplicata che esiste anche in altri componenti del progetto

Conta: numero di `ref`, `computed`, `watch`, funzioni nel `<script setup>`. Se il totale supera 15-20 elementi, c'e' quasi certamente logica estraibile.

### 3. Responsabilita' multiple

Il segnale piu' importante: il componente fa piu' di una cosa? Esempi comuni:
- Page che contiene form di creazione + tabella di lista + filtri + modale di dettaglio
- Componente che gestisce sia la visualizzazione che l'editing con flag `isEditing`
- Mix di layout/struttura pagina e logica di business specifica

### 4. Componenti fratelli e riuso

Prima di suggerire l'estrazione, controlla:
- Esistono gia' componenti simili nella directory `components/` che potrebbero essere riusati?
- Esistono composables in `composables/` che coprono parte della logica?
- Ci sono pattern ripetuti tra questo file e altri file nella stessa feature?

Usa `Glob` e `Grep` per verificare. Suggerire l'estrazione di qualcosa che esiste gia' e' un errore da evitare.

## Output

Produci un report strutturato per ogni file analizzato:

```
## Review: Component Size — {data odierna}

### Scope
- File analizzati: {N}
- {lista file con conteggio righe}

---

### {NomeFile.vue} ({N} righe)

**Metriche**
- Righe totale: {N} | Template: {N} | Script: {N} | Style: {N}
- Ref: {N} | Computed: {N} | Watch: {N} | Funzioni: {N}
- Zona: {OK | Attenzione | Critico}

**Componenti estraibili**
1. **`NomeSuggerito.vue`** (righe {da}–{a}, ~{N} righe)
   - Responsabilita': {cosa fa questo blocco}
   - Props necessarie: `{prop1}`, `{prop2}`
   - Emits: `{event1}` (se applicabile)

2. **`AltroComponente.vue`** (righe {da}–{a}, ~{N} righe)
   - ...

**Composables estraibili**
1. **`useNome.ts`**
   - Stato coinvolto: `{ref1}`, `{ref2}`, `{computed1}`
   - Funzioni: `{fn1}()`, `{fn2}()`
   - Motivazione: {perche' ha senso estrarre questa logica}

**Riuso esistente**
- {componente/composable gia' esistente che potrebbe essere usato al posto di codice inline}

**Note**
- {osservazioni aggiuntive, tradeoff, o motivi per NON splittare alcune parti}

---

### Summary
- File analizzati: {N}
- Estrazioni suggerite: {N} componenti + {N} composables
- Priorita': {quale estrazione avrebbe il maggiore impatto sulla leggibilita'}
```

### Classificazione estrazioni

Ordina le estrazioni suggerite per impatto:

- **Alta priorita'**: il blocco ha >100 righe, responsabilita' autonoma, e renderebbe il file genitore significativamente piu' leggibile
- **Media priorita'**: il blocco ha 50-100 righe o la logica e' moderatamente complessa
- **Bassa priorita'**: il blocco e' piccolo (<50 righe) ma concettualmente separato — utile ma non urgente

### Cosa NON suggerire

- Non suggerire split di componenti che hanno una sola responsabilita' coesa, anche se sono lunghi
- Non suggerire composables per 2-3 ref con una computed — il overhead non vale
- Non suggerire estrazione di logica usata una sola volta e strettamente legata al contesto del componente
- Non suggerire nomi generici (`useUtils`, `HelperComponent`) — ogni nome deve comunicare chiaramente la responsabilita'
