<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v3
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/vue3 (INERTIA_VUE) - v3
- tailwindcss (TAILWINDCSS) - v4
- vue (VUE) - v3
- @laravel/vite-plugin-wayfinder (WAYFINDER_VITE) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.
- Prefer shadcn-vue components over raw HTML or custom-built equivalents. If shadcn-vue ships a Button, Input, Dialog, Sheet, Table, Card, Select, etc., use it — do not rewrite `<button class="...">` or hand-roll a dialog. Extend via the project's variants and CSS tokens (see DESIGN.md), never by reimplementing the primitive.
- Some shadcn-vue components may not be installed in this project yet. Before assuming a primitive does not exist, consult the `shadcn-vue` skill or the official shadcn-vue docs to discover and install it. Do not write a custom version because "it isn't in the project."
- For all forms, use the `Field` family: `Field`, `FieldGroup`, `FieldLabel`, `FieldDescription`, `FieldError`. Do not assemble labels + inputs + helper text by hand. If you are unsure of the correct composition (slots, props, accessibility wiring), consult the `shadcn-vue` skill or docs before writing.
- If a needed primitive truly is not in shadcn-vue, check the reka-ui layer it builds on before writing from scratch.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>

# Project conventions (Studiofinance)

## Naming

- **Database tables, columns, model classes, enum classes, controllers, requests, route URL segments, Vue components and props are all in English.** UI-facing labels, copy, toasts and breadcrumb labels stay in Italian. Italian fiscal acronyms and proper nouns (IRPEF, IVA, Inarcassa, OATO, "bolli") are preserved as-is in code when they appear inside enum values or domain terms — they are technical terms without English equivalents.

## Forms convention

The project follows the shadcn-vue Field family rules (see `shadcn-vue` skill `rules/forms.md`) with these project-specific clarifications:

- **Only use the HTML `<form>` element (lowercase).** Never use Inertia's `<Form>` component. Reactive form state is handled via Inertia's `useForm()` composable + `v-model` on inputs.
- **Hierarchy: `<form>` → `<FieldGroup>` → `<Field>`/`<FormField>`.** The `<form>` element ALWAYS wraps `<FieldGroup>`, never the other way around.
- **`FormSection` already wraps its slot in `<FieldGroup>`** — never add an explicit `<FieldGroup>` inside `<FormSection>` and never put `<form>` *inside* `<FormSection>`. The `<form>` element, when present, wraps the `<FormSection>` from outside.
- **Never use `space-y-*` / `space-x-*` for form layout.** Gap is provided by `<FieldGroup>`.
- **Forms inside `Dialog`:** put `<form id="…">` inside `<DialogBody>`, the submit button outside via `<Button type="submit" form="…">` in `<DialogStandardFooter>`.
- **Action-only buttons (POST with no input fields):** no `<form>` wrapper — just `<Button @click="form.post(url)">` using `useForm({})`.
- **Pages with multiple FormSection sharing a global topbar submit:** no outer `<form>`, submit programmatic from the Teleported button.

### Pattern decision table

| Case | When | Shape |
| --- | --- | --- |
| 1 | Standalone single-block form (auth pages, onboarding) | `<form>` + `<FieldGroup>` + `<FormField>` |
| 2 | Form inside a `Dialog` | `<form id="x">` + `<FieldGroup>`; submit button outside via `form="x"` |
| 3 | Action-only POST (no fields) | Just `<Button @click="form.post(url)">` |
| 4 | Domain page with one or more `FormSection` | `<form>` wraps the `<FormSection>` (never inside) |
| 5 | Monolite page with global topbar submit | No `<form>`, programmatic submit |

## Phase quality gates

Each implementation phase listed in `~/.claude/plans/piano-studiofinance-brief.md` declares its own quality gate skills (see the "Quality gates" section of the plan). **A phase is NOT considered closed until every gate listed for it has been invoked formally via the relevant Skill (`review-backend`, `review-frontend`, `review-security`, `review-component-size`, `review-feature`) and the issues raised have been triaged.** Same applies to `impeccable` skills declared in the phase steps (`/impeccable shape`, `/impeccable polish`, `/impeccable adapt`): inline reasoning in chat does NOT substitute for invoking the skill.

If a phase is committed before its gates run, the gates must be invoked retroactively on the committed paths (passing the path argument to the skill) before starting the next phase. Skipping is never the default.

## Table action columns

Domain tables (Clients, future Invoices, ...) and settings tables (Expense items, Recurring deadlines) use the same action-cell pattern for consistency: a single kebab `<DropdownMenu>` trigger (`PhDotsThreeVertical`) in the last column with `align="end"`, containing `Modifica` (`PhPencil`) and `Archivia` (`PhArchive`, `variant="destructive"`). No `DropdownMenuSeparator` between the two — keep the menu dense. Row-click can navigate elsewhere (Show page) or duplicate the Modifica action; either is acceptable as long as the dropdown is always present and consistent.

