# apps — MAMIAS

Laravel 13 + Filament 5 admin app for Mediterranean Non-Indigenous Species
catalogue. PostgreSQL/PostGIS runtime, FrankenPHP, Redis, queue worker.
Primary surface is Filament panel at `/mamias`; `routes/web.php` only
serves welcome/about views plus two Livewire pages. No REST API.
Users: scientists, taxonomists, panel admins (Spatie roles).

## Auth shape

- **Panel access gate is two-layered.** `User::canAccessPanel()` returns
  `true` for *any* authenticated user where panel id is `mamias` — it
  does NOT check roles. Hard gating lives in
  `App\Http\Middleware\RedirectIfNotPanelUser` (in
  `MamiasPanelProvider::middleware()`), which allows only
  `super_admin` or `scientist` roles past the panel. Any panel route or
  page that bypasses this middleware = full privilege escalation.
- **Post-login redirect:** `App\Support\FilamentAuthRedirect::for($user)`.
- **Roles** (Spatie): `super_admin`, `panel_user`, `scientist`, `user`.
  Registration auto-assigns `user` (custom `Register` page).
  `FilamentShieldPlugin` manages permissions UI.
- **Email verification** is required (`MustVerifyEmail` on `User`).
  `Login::mount` redirects already-authed users.
- **Dev-only auto-login:** `FilamentDeveloperLoginsPlugin` is gated by
  `app()->environment('local')` and exposes hardcoded admin/scientist
  emails. Any non-`local` reachability = critical.

## Threat model

Attackers want (1) write access to scientific records (tamper /
unauthorized publish), (2) admin account takeover via role escalation,
(3) RCE via backup/command-runner surfaces, (4) PII/email harvest from
user list. External HTTP fan-out (WoRMS, Crossref, GreenAPI WhatsApp,
EASIN) on user-supplied identifiers introduces SSRF and unbounded-cost
surface.

## Project-specific patterns to flag

- **`/mamias/decompose` route** (`routes/web.php`) bound to
  `Lubusin\Decomposer\Controllers\DecomposerController` with **no auth
  middleware** — Decomposer exposes env, config, packages. Must require
  `super_admin`.
- **`CommandRunnerPlugin`** (BinaryBuilds) and **`BackupManager`** page
  must each enforce `super_admin` via `->authorize(...)` /
  `canAccess()`; missing check = arbitrary shell / disk access.
  Only `FilamentSpatieLaravelHealthPlugin` currently has
  `->authorize(fn () => auth()->user()->hasRole('super_admin'))`.
- **Filament resource pages skipping role check.** Pattern is to inherit
  panel-level middleware; per-resource `canCreate()` / `canDelete()` /
  `canEdit()` overrides that return `true` unconditionally are
  escalation vectors (panel reachable by `scientist`).
- **WoRMS / Crossref / DOI / EASIN / GreenAPI calls** in `Services/*`
  build URLs from user input (AphiaID, DOI, phone, taxon name). Flag
  any `Http::get(...)` where host is user-controlled or where URL is
  concatenated without an allowlist.
- **Livewire public pages** `MyReferences` and `PublicProfile`
  (`routes/web.php` `/references`, `/profile`) — IDOR risk if record
  lookup uses request input instead of `auth()->id()`.
- **Literature file upload** (`file_path` column on `literatures`):
  default Filament storage visibility is private; any
  `->visibility('public')` on user uploads = attachment leak.
- **Excel/CSV import** flows: flag formula-prefix output without
  sanitization (`=`, `+`, `-`, `@`) and unbounded row iteration.

## Known false-positives

- `routes/web.php` `/`, `/about`, `/login`, `/email-verification/prompt`
  are intentionally public (redirects or marketing views).
- `FilamentDeveloperLoginsPlugin` hardcoded emails are dev-only,
  gated by `app()->environment('local')`.
- SQLite in-memory DB in `phpunit.xml` is the test fixture; PostGIS-only
  SQL in app code is intentional (don't flag dialect mismatch).
- `backup/` directory at repo root is PostGIS container bootstrap
  input, not user upload storage.
- `User::canAccessPanel()` returning `true` is **not** a finding on
  its own — `RedirectIfNotPanelUser` is the real gate. Flag only if a
  route bypasses panel middleware.
- `php artisan filament:cache-components` in `entrypoint.sh` and
  `make dev-cache` are operational, not auth-relevant.
