---
name: review-backend
description: "Reviews Laravel backend code for architecture quality, Eloquent patterns, validation, and code smells. Invoke periodically on new features or before merging."
allowed-tools: Read, Grep, Glob, Bash
argument-hint: "[path-opzionale]"
---

# Backend Code Review

## Scope

1. Se `$ARGUMENTS` contiene un path, analizza **solo quel path** (ricorsivamente)
2. Altrimenti, esegui `git diff main --name-only --diff-filter=ACMR -- '*.php'` per trovare i file PHP modificati rispetto a `main`
3. Se non ci sono file modificati, comunicalo e termina

Per ogni file nello scope, leggi il contenuto completo e analizza secondo la checklist.

## Checklist

### Architettura & Design
- I controller sono snelli (delegano a Service/Action per logica complessa)
- Single Responsibility Principle rispettato
- Dependency Injection usata correttamente (constructor injection)
- Nessuna dipendenza circolare
- Pattern Laravel rispettati: Form Request per validazione, Resource per API, Policy per authorization

### Eloquent & Database
- N+1 query prevenute con eager loading (`with()`, `load()`)
- Uso di `Model::query()` invece di `DB::` dove possibile
- Mass assignment protetto (`$fillable` definito, no `$guarded = []`)
- Relazioni con return type hints
- Query complesse nel query builder, non raw SQL
- Migrazioni backward-compatible (no drop column senza step intermedio)
- Indici sulle colonne usate in WHERE/JOIN
- Foreign keys e cascade definiti

### Validazione
- Form Request per ogni endpoint che riceve input (no validazione inline nel controller)
- Regole coerenti col tipo di dato (email, exists, unique, in)
- Messaggi di errore custom dove utile
- Coerenza nello stile regole (array vs string) — segui la convenzione del progetto

### Pattern Laravel
- `config()` invece di `env()` fuori dai file config
- Named routes usate per URL generation
- `ShouldQueue` per operazioni lunghe (email, notifiche, job)
- Event/Listener per side effects disaccoppiati
- Middleware applicati correttamente sulle route

### Code Quality
- Metodi non più lunghi di ~30 righe
- Nessuna duplicazione di logica
- Naming descrittivo (`isRegisteredForDiscounts`, non `discount()`)
- Return type declarations esplicite su tutti i metodi
- Type hints sui parametri
- PHPDoc con array shapes dove appropriato
- Nessun commento superfluo (solo per logica complessa)
- Constructor property promotion (PHP 8+)

## Output

Produci un report strutturato:

```
## Review: Backend — {data odierna}

### Scope
- Branch: `{branch corrente}` vs `main`
- File analizzati: {N}
- {lista file}

### Critical
- **[file.php:42](path/to/file.php#L42)**: Descrizione problema + snippet codice + fix suggerito

### Warning
- **[file.php:18](path/to/file.php#L18)**: Descrizione

### Suggestion
- **[file.php:5](path/to/file.php#L5)**: Descrizione

### Summary
- Critical: N | Warning: N | Suggestion: N
- Verdict: Approvato / Da rivedere / Bloccante
```

Se non ci sono problemi, scrivi "Nessun problema trovato" con il verdict "Approvato".
