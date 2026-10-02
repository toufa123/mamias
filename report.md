# Security Review: mamias

## Scope

Repository-wide, read-only static security review of the current working tree, prioritizing Laravel/Filament entry points, authentication, authorization, uploads, rendering, deployment configuration, privileged plugins, external-service boundaries, and tracked sensitive artifacts.

- Scan mode: repository
- Target kind: git_worktree
- Target ID: target_sha256_b27bdce12efd531181982166f61e6d6bb71ada0556d9af4e804d5f1dc3022207
- Revision: 48ec79ccf218afd3c6b1eb807a5946dc6535a7d7
- Snapshot digest: codex-security-snapshot/v1:sha256:c0f30016e1edaaff2695013f6eb7121403316a05f040e0b1924cb8dc7263c00c
- Inventory strategy: repository
- Included paths: .
- Excluded paths: none
- Artifacts reviewed: Laravel application source and policies, Filament and relevant plugin security-control source, Docker development and production configuration, Git-tracked database backup metadata

Limitations and exclusions:
- Coverage is partial: the repository contains 2,247 files including generated assets, installed vendor code, language packs, reports, and binary backups. Security-sensitive application and deployment surfaces were prioritized.
- No network access, application execution, database restore, or vulnerability-triggering input was used.
- Deployed role assignments, proxy configuration, log contents, and external service state were unavailable.
- Excluded apps/public/assets/\*\*: Generated third-party bundles were not fully audited; installed source owning relevant Filament controls was inspected instead.
- Excluded apps/vendor/\*\*: Vendor code was reviewed only where it implements application security boundaries or candidate controls.
- Excluded backups/\*\*/\*.dmp: Binary row contents were not extracted; only safe catalog metadata and tracked-file status were inspected.

### Scan Summary

| Field | Value |
| --- | --- |
| Scan outcome | completed |
| Reportable findings | 6 |
| Severity mix | medium: 4, low: 2 |
| Confidence mix | high: 6 |
| Coverage | partial |
| Validation mode | Independent source traces plus parent reconciliation |

Canonical artifacts: `scan-manifest.json`, `findings.json`, and `coverage.json`. This report is a deterministic projection of those files.

## Threat Model

MAMIAS is a Laravel/Filament marine-species catalogue with public CMS pages, verified-user submissions, a scientist/super-admin panel, queue workers, PostGIS, Redis, public uploads, external taxonomy/bibliographic services, and privileged operational plugins. Production serves the app behind a TLS-terminating reverse proxy; application and queue containers use the same image but do not share application storage in the production compose file.

### Assets

- User identities, password hashes, email-verification state, contact details, roles, sessions, reset tokens, and notifications.
- Scientific taxonomy, literature, occurrence, suggestion, import, review, and CMS publication integrity.
- Public upload paths, private file-manager data, application logs, database backups, and database/Redis/CAPTCHA/service credentials.
- Privileged command, backup/restore, file-management, log-management, and role-management authority.

### Trust Boundaries

- Anonymous browsers cross CAPTCHA and authentication controls before obtaining a public account (`apps/app/Filament/Pages/Auth/Register.php`, `Login.php`).
- Verified public users cross ownership boundaries into personal submissions and imports (`apps/routes/web.php`, Livewire components, `DownloadNotImportedRows.php`).
- Scientists and super administrators cross the `/mamias` panel boundary; model policies and Shield permissions are intended to separate view, update, create, and delete authority (`User.php:215`, policies).
- Panel users cross independent privileged plugin boundaries for commands, health, files, logs, database tools, and backups; navigation hiding is not an authorization control.
- Persisted taxon metadata crosses a text-to-HTML rendering boundary in Filament tables and forms.
- Repository recipients cross a source-distribution boundary that currently includes database backup artifacts.
- The application crosses fixed network boundaries to WoRMS, GBIF, EASIN, Crossref, DOI, GreenAPI, avatar, and CAPTCHA services.

### Attacker Capabilities

- Anonymous actors can access CMS/authentication endpoints and register public accounts but do not start with scientist, super-admin, filesystem, or deployment authority.
- Verified public users control profile, suggestion, occurrence, literature, upload, and import inputs within authenticated workflows.
- Scientists can enter the panel and may receive granular Shield permissions; they should not inherit super-admin operational tools or abilities denied by model policies.
- Lower-trust repository readers can inspect every Git-tracked source artifact but should not receive database rows or credential verifiers.
- Third-party API responses and uploaded files are untrusted data; deployment operators and super administrators already possess broad intended authority.

### Security Objectives

- Bind verified status to the current email address and enforce authentication, role, ownership, and per-model policy checks server-side.
- Preserve catalogue, review, and publication integrity across direct, bulk, Livewire, import, and queued workflows.
- Render persisted metadata safely and prevent stored content from acquiring browser-origin execution authority.
- Restrict operational logs, files, backups, commands, and database tooling to explicitly authorized administrators.
- Keep credentials, database rows, session/reset state, personal data, and backups outside source distribution and public storage unless deliberately published.
- Maintain bounded, fixed-destination external requests and fail-closed production CAPTCHA/proxy/session behavior.

### Assumptions

- No user-supplied threat model or deployment context was provided; conclusions are based on the current working tree.
- Production panel admission is `super_admin` or `scientist`; exact Shield permission assignments live outside the repository.
- Public upload visibility is intentional, but active browser content is not assumed intentional.
- Production proxy, Caddy MIME behavior, live logs, and database roles were not observed.
- AGENTS.md descriptions of web routes, CMS prefix, and panel roles are stale relative to current source.
- BackupManager's Docker commands have a development socket mount, while production compose does not declare Docker-socket access.
- No claim is made that ignored local `.env` files are distributed; the reported backup artifacts are confirmed Git-tracked.

## Findings

| Finding | Severity | Confidence | Detailed write-up |
| --- | --- | --- | --- |
| [Tracked database backups expose account and PostgreSQL credential material](#finding-1) | medium | high | inline below |
| [Taxon metadata can execute stored JavaScript in curator sessions](#finding-2) | medium | high | inline below |
| [View-only panel users can invoke privileged custom resource actions](#finding-3) | medium | high | inline below |
| [Scientists can read and delete application logs through a hidden page](#finding-4) | medium | high | inline below |
| [Taxon editors can delete nonduplicate records without delete permission](#finding-5) | low | high | inline below |
| [Users keep verified status after replacing their email address](#finding-6) | low | high | inline below |

### Confidence Scale

| Label | Meaning |
| --- | --- |
| high | Direct evidence supports the finding with no material unresolved blocker. |
| medium | Evidence supports a plausible issue, but material runtime or reachability proof remains. |
| low | Evidence is incomplete and the item is retained only for explicit follow-up. |

<a id="finding-1"></a>

### [1] Tracked database backups expose account and PostgreSQL credential material

| Field | Value |
| --- | --- |
| Severity | medium |
| Confidence | high |
| Confidence rationale | Git tracks both files; the custom dump catalog contains table-data entries and sensitive table schemas, and the globals file contains password-bearing role declarations. Secret bytes were deliberately not reproduced. |
| Category | sensitive-data-exposure |
| CWE | CWE-200, CWE-312 |
| Affected lines | backups/2026/June/mamias_db_mamias_db.23-June-2026-11-27.dmp:1, backups/globals.sql:16-23 |

#### Summary

The Git tree contains a PostgreSQL custom dump with table data for users, sessions, password-reset tokens, notifications, and domain records, plus a cluster globals dump containing password verifiers for login and superuser roles.

#### Root Cause

Database backup output is stored under a Git-tracked repository path without redaction or an ignore boundary.

**Git-tracked PostgreSQL dump contains sensitive table data** — `backups/2026/June/mamias_db_mamias_db.23-June-2026-11-27.dmp:1`

The binary is part of the Git tree and packages database rows that source recipients do not need to build the application.

```
PostgreSQL custom-format dump; catalog includes TABLE DATA for users, sessions, password_reset_tokens, notifications, and application domain tables. Binary row contents omitted.
```

**Cluster dump includes login-role password verifiers** — `backups/globals.sql:16-23`

The tracked cluster dump contains reusable offline password-verifier material for login-capable PostgreSQL roles, including a superuser-named role.

```sql
CREATE ROLE "CHANGE_ME_DB_USER";
ALTER ROLE "CHANGE_ME_DB_USER" WITH SUPERUSER ... LOGIN ... PASSWORD [verifier omitted];
CREATE ROLE sparac_admin_2026;
ALTER ROLE sparac_admin_2026 WITH SUPERUSER ... LOGIN ... PASSWORD [verifier omitted];
```

#### Validation

Git index inspection confirmed the files are tracked; safe catalog/string inspection confirmed sensitive table-data entries and password-bearing role declarations without exposing their values.

Validation method: parent static artifact inspection

**Git-tracked PostgreSQL dump contains sensitive table data** — `backups/2026/June/mamias_db_mamias_db.23-June-2026-11-27.dmp:1`

The binary is part of the Git tree and packages database rows that source recipients do not need to build the application.

```
PostgreSQL custom-format dump; catalog includes TABLE DATA for users, sessions, password_reset_tokens, notifications, and application domain tables. Binary row contents omitted.
```

**Cluster dump includes login-role password verifiers** — `backups/globals.sql:16-23`

The tracked cluster dump contains reusable offline password-verifier material for login-capable PostgreSQL roles, including a superuser-named role.

```sql
CREATE ROLE "CHANGE_ME_DB_USER";
ALTER ROLE "CHANGE_ME_DB_USER" WITH SUPERUSER ... LOGIN ... PASSWORD [verifier omitted];
CREATE ROLE sparac_admin_2026;
ALTER ROLE sparac_admin_2026 WITH SUPERUSER ... LOGIN ... PASSWORD [verifier omitted];
```

Limitations:
- The dump was not restored or queried, and its deployment provenance is unknown.

#### Dataflow

database backup -\> tracked repository files -\> repository reader

- **Source:** database and cluster backup output

- **Sink:** Git-distributed dump artifacts

- **Outcome:** offline disclosure and credential-guessing material

**Git-tracked PostgreSQL dump contains sensitive table data** — `backups/2026/June/mamias_db_mamias_db.23-June-2026-11-27.dmp:1`

The binary is part of the Git tree and packages database rows that source recipients do not need to build the application.

```
PostgreSQL custom-format dump; catalog includes TABLE DATA for users, sessions, password_reset_tokens, notifications, and application domain tables. Binary row contents omitted.
```

**Cluster dump includes login-role password verifiers** — `backups/globals.sql:16-23`

The tracked cluster dump contains reusable offline password-verifier material for login-capable PostgreSQL roles, including a superuser-named role.

```sql
CREATE ROLE "CHANGE_ME_DB_USER";
ALTER ROLE "CHANGE_ME_DB_USER" WITH SUPERUSER ... LOGIN ... PASSWORD [verifier omitted];
CREATE ROLE sparac_admin_2026;
ALTER ROLE sparac_admin_2026 WITH SUPERUSER ... LOGIN ... PASSWORD [verifier omitted];
```

#### Reachability

Requires read access to this Git repository or any derived source archive.

- **Attacker:** lower-trust repository reader or leaked-source recipient

- **Entry point:** tracked `backups/` files

- **Outcome:** access to sensitive database backup material

#### Severity

**Medium** — A repository reader may gain personal data, session/reset state, password hashes, and database role verifiers, but repository visibility and whether the dump reflects production are not established.

Additional runtime or deployment evidence could raise or lower this severity.

Impact assessment:
- **Level:** high
- **Why:** Potentially exposes accounts, sessions, reset tokens, personal records, and database role verifier material.

Likelihood assessment:
- **Level:** medium
- **Why:** Depends on repository distribution and whether the snapshot reflects real deployment data.

#### Remediation

Remove database and globals dumps from the tracked tree, add backup paths to ignore rules, rotate affected database role credentials, invalidate exposed session/reset state as appropriate, and purge the artifacts from repository history where distribution warrants it.

Tests:
- Fail CI when PostgreSQL dump signatures or password-bearing `ALTER ROLE` statements appear in tracked files.
- Assert backup output paths are ignored by Git.

Preventive controls:
- Store backups in access-controlled external storage and run secret/data scanning on commits.

<a id="finding-2"></a>

### [2] Taxon metadata can execute stored JavaScript in curator sessions

| Field | Value |
| --- | --- |
| Severity | medium |
| Confidence | high |
| Confidence rationale | Independent static traces reach unsanitized `HtmlString` sinks and Filament's `innerHTML` renderer; no browser reproduction was performed. |
| Category | cross-site-scripting |
| CWE | CWE-79 |
| Affected lines | apps/app/Filament/Resources/Taxons/Tables/TaxonTable.php:686-710, apps/app/Filament/Resources/Taxons/Schemas/TaxonForm.php:67-88, apps/vendor/filament/tables/src/Columns/TextColumn.php:451-456 |

#### Summary

A user who can create or update taxa can store markup in `authority` or `Easin_id`; the catalogue tooltip and edit-form helper mark those values as trusted HTML, so another curator or administrator executes the markup when viewing the record.

#### Root Cause

The application crosses the text-to-HTML boundary by wrapping database values in `HtmlString` without first encoding the text or validating the identifier grammar.

**Editable taxon metadata** — `apps/app/Filament/Resources/Taxons/Schemas/TaxonForm.php:67-88`

Both persisted fields are attacker-controlled text. `Easin_id` is immediately concatenated inside a quoted HTML attribute and marked trusted.

```php
TextInput::make('authority')->maxLength(255),
TextInput::make('Easin_id')->helperText(fn ($record) => $record?->Easin_id ? new HtmlString('<a href="https://easin.jrc.ec.europa.eu/spexplorer/species/factsheet/'.$record->Easin_id.'" target="_blank" class="text-primary-600 underline">View EASIN Factsheet</a>') : null)
```

**Authority is returned as trusted tooltip HTML** — `apps/app/Filament/Resources/Taxons/Tables/TaxonTable.php:686-710`

The tooltip concatenates persisted authority into markup without encoding and labels the result safe.

```php
$authority = $record->authority ?? '';
// ...
return new HtmlString(
    "<span class='italic'>{$formattedName}</span>".
    ($authority !== '' ? " <span class='not-italic'> {$authority}</span>" : '')
);
// ...
return new HtmlString($fullName);
```

**Htmlable tooltips enable HTML rendering** — `apps/vendor/filament/tables/src/Columns/TextColumn.php:451-456`

Filament enables HTML for the `HtmlString`; its bundled tooltip renderer assigns the content through `innerHTML`.

```php
content: {$tooltipJs},
allowHTML: Js::from($tooltip instanceof Htmlable)
```

#### Validation

Both editable fields persist without an HTML-safe invariant and reach trusted HTML sinks. Ordinary cell sanitization does not apply to the separate tooltip or helper text.

Validation method: independent static source trace

**Editable taxon metadata** — `apps/app/Filament/Resources/Taxons/Schemas/TaxonForm.php:67-88`

Both persisted fields are attacker-controlled text. `Easin_id` is immediately concatenated inside a quoted HTML attribute and marked trusted.

```php
TextInput::make('authority')->maxLength(255),
TextInput::make('Easin_id')->helperText(fn ($record) => $record?->Easin_id ? new HtmlString('<a href="https://easin.jrc.ec.europa.eu/spexplorer/species/factsheet/'.$record->Easin_id.'" target="_blank" class="text-primary-600 underline">View EASIN Factsheet</a>') : null)
```

**Authority is returned as trusted tooltip HTML** — `apps/app/Filament/Resources/Taxons/Tables/TaxonTable.php:686-710`

The tooltip concatenates persisted authority into markup without encoding and labels the result safe.

```php
$authority = $record->authority ?? '';
// ...
return new HtmlString(
    "<span class='italic'>{$formattedName}</span>".
    ($authority !== '' ? " <span class='not-italic'> {$authority}</span>" : '')
);
// ...
return new HtmlString($fullName);
```

**Htmlable tooltips enable HTML rendering** — `apps/vendor/filament/tables/src/Columns/TextColumn.php:451-456`

Filament enables HTML for the `HtmlString`; its bundled tooltip renderer assigns the content through `innerHTML`.

```php
content: {$tooltipJs},
allowHTML: Js::from($tooltip instanceof Htmlable)
```

Limitations:
- No browser reproduction was run; exact production role assignments are database state.

#### Dataflow

taxon form input -\> database -\> `HtmlString` -\> Filament HTML renderer

- **Source:** editable `authority` or `Easin_id`

- **Sink:** trusted tooltip/helper HTML

- **Outcome:** same-origin JavaScript in the victim panel session

**Editable taxon metadata** — `apps/app/Filament/Resources/Taxons/Schemas/TaxonForm.php:67-88`

Both persisted fields are attacker-controlled text. `Easin_id` is immediately concatenated inside a quoted HTML attribute and marked trusted.

```php
TextInput::make('authority')->maxLength(255),
TextInput::make('Easin_id')->helperText(fn ($record) => $record?->Easin_id ? new HtmlString('<a href="https://easin.jrc.ec.europa.eu/spexplorer/species/factsheet/'.$record->Easin_id.'" target="_blank" class="text-primary-600 underline">View EASIN Factsheet</a>') : null)
```

**Authority is returned as trusted tooltip HTML** — `apps/app/Filament/Resources/Taxons/Tables/TaxonTable.php:686-710`

The tooltip concatenates persisted authority into markup without encoding and labels the result safe.

```php
$authority = $record->authority ?? '';
// ...
return new HtmlString(
    "<span class='italic'>{$formattedName}</span>".
    ($authority !== '' ? " <span class='not-italic'> {$authority}</span>" : '')
);
// ...
return new HtmlString($fullName);
```

**Htmlable tooltips enable HTML rendering** — `apps/vendor/filament/tables/src/Columns/TextColumn.php:451-456`

Filament enables HTML for the `HtmlString`; its bundled tooltip renderer assigns the content through `innerHTML`.

```php
content: {$tooltipJs},
allowHTML: Js::from($tooltip instanceof Htmlable)
```

#### Reachability

Requires create/update access to taxa and a victim who opens or focuses the affected record.

- **Attacker:** authenticated taxon editor

- **Entry point:** taxon create/edit form

- **Outcome:** script execution as another panel user

#### Severity

**Medium** — Same-origin script execution can act with a victim curator or administrator session, but exploitation requires authenticated taxon mutation access and a victim view.

Additional runtime or deployment evidence could raise or lower this severity.

Impact assessment:
- **Level:** high
- **Why:** Script can perform panel-visible actions and read data as the victim.

Likelihood assessment:
- **Level:** medium
- **Why:** Requires an authenticated editor and a victim view.

#### Remediation

Treat taxon metadata as text: validate `Easin_id` against its identifier grammar, build links with encoded URL helpers, and escape `authority` and scientific-name fragments before adding only the intended markup.

Tests:
- Persist event-bearing markup in `authority` and assert the tooltip renders it as text.
- Persist quote-breaking text in `Easin_id` and assert the helper produces one safe fixed-origin link.

Preventive controls:
- Centralize safe taxon display formatting and prohibit raw database values inside `HtmlString`.

<a id="finding-3"></a>

### [3] View-only panel users can invoke privileged custom resource actions

| Field | Value |
| --- | --- |
| Severity | medium |
| Confidence | high |
| Confidence rationale | The action callbacks, policies, and installed Filament authorization defaults were traced directly. |
| Category | missing-authorization |
| CWE | CWE-862 |
| Affected lines | apps/app/Filament/Resources/Literatures/Tables/LiteraturesTable.php:220-270, apps/app/Filament/Resources/Literatures/Tables/LiteraturesTable.php:375-423, apps/app/Filament/Resources/NisSuggestions/Actions/NisSuggestionActions.php:31-145, apps/vendor/filament/filament/src/Resources/Pages/Page.php:309-323 |

#### Summary

Custom literature, NIS-suggestion, and taxon actions rely on record-state visibility but do not authorize the underlying update, create, merge, or delete operations. Filament supplies automatic policy checks for built-in actions only, so a panel user with `ViewAny` can cross permissions that Shield exposes separately.

#### Root Cause

The resources model granular permissions, but custom actions use UI visibility as the only gate and never call `authorize()` for their sensitive operations.

**Literature moderation checks state, not permission** — `apps/app/Filament/Resources/Literatures/Tables/LiteraturesTable.php:220-270`

A reachable generic action changes review state without authorizing `Update:Literature`.

```php
Action::make('approve')
    ->visible(fn (Literature $record): bool => $record->status !== LiteratureStatus::APPROVED)
    ->action(fn (Literature $record) => self::review($record, LiteratureStatus::APPROVED, ...));
```

**Merge moves citations and deletes the source** — `apps/app/Filament/Resources/Literatures/Tables/LiteraturesTable.php:375-423`

The generic action reaches a multi-record mutation and source soft-delete without update/delete authorization.

```php
Action::make('merge')
    // ...
    ->action(function (Literature $record, array $data, $livewire): void {
        $target = Literature::findOrFail($data['target_id']);
        $record->mergeInto($target);
```

**Generic actions have no automatic policy decision** — `apps/vendor/filament/filament/src/Resources/Pages/Page.php:309-323`

Filament maps built-in actions to policies; arbitrary `Action` and `BulkAction` instances fall through without a decision.

```php
return match (true) {
    $action instanceof CreateAction => $resource::getCreateAuthorizationResponse(),
    $action instanceof DeleteAction => $resource::getDeleteAuthorizationResponse($record),
    $action instanceof EditAction => $resource::getEditAuthorizationResponse($record),
    // ...
    default => null,
};
```

#### Validation

Resource list access requires only `ViewAny`; the generic actions default to allowed and their callbacks directly mutate protected models.

Validation method: independent static authorization trace

**Literature moderation checks state, not permission** — `apps/app/Filament/Resources/Literatures/Tables/LiteraturesTable.php:220-270`

A reachable generic action changes review state without authorizing `Update:Literature`.

```php
Action::make('approve')
    ->visible(fn (Literature $record): bool => $record->status !== LiteratureStatus::APPROVED)
    ->action(fn (Literature $record) => self::review($record, LiteratureStatus::APPROVED, ...));
```

**Merge moves citations and deletes the source** — `apps/app/Filament/Resources/Literatures/Tables/LiteraturesTable.php:375-423`

The generic action reaches a multi-record mutation and source soft-delete without update/delete authorization.

```php
Action::make('merge')
    // ...
    ->action(function (Literature $record, array $data, $livewire): void {
        $target = Literature::findOrFail($data['target_id']);
        $record->mergeInto($target);
```

**Generic actions have no automatic policy decision** — `apps/vendor/filament/filament/src/Resources/Pages/Page.php:309-323`

Filament maps built-in actions to policies; arbitrary `Action` and `BulkAction` instances fall through without a decision.

```php
return match (true) {
    $action instanceof CreateAction => $resource::getCreateAuthorizationResponse(),
    $action instanceof DeleteAction => $resource::getDeleteAuthorizationResponse($record),
    $action instanceof EditAction => $resource::getEditAuthorizationResponse($record),
    // ...
    default => null,
};
```

Limitations:
- The deployed scientist permission set is not committed; exploitation requires a user with view access but without one of the bypassed mutation permissions.

#### Dataflow

resource list access -\> generic action -\> unguarded callback -\> model/service mutation

- **Source:** authenticated panel action request

- **Sink:** review, create, merge, update, or delete operation

- **Outcome:** unauthorized catalogue and review-state changes

**Literature moderation checks state, not permission** — `apps/app/Filament/Resources/Literatures/Tables/LiteraturesTable.php:220-270`

A reachable generic action changes review state without authorizing `Update:Literature`.

```php
Action::make('approve')
    ->visible(fn (Literature $record): bool => $record->status !== LiteratureStatus::APPROVED)
    ->action(fn (Literature $record) => self::review($record, LiteratureStatus::APPROVED, ...));
```

**Merge moves citations and deletes the source** — `apps/app/Filament/Resources/Literatures/Tables/LiteraturesTable.php:375-423`

The generic action reaches a multi-record mutation and source soft-delete without update/delete authorization.

```php
Action::make('merge')
    // ...
    ->action(function (Literature $record, array $data, $livewire): void {
        $target = Literature::findOrFail($data['target_id']);
        $record->mergeInto($target);
```

**Generic actions have no automatic policy decision** — `apps/vendor/filament/filament/src/Resources/Pages/Page.php:309-323`

Filament maps built-in actions to policies; arbitrary `Action` and `BulkAction` instances fall through without a decision.

```php
return match (true) {
    $action instanceof CreateAction => $resource::getCreateAuthorizationResponse(),
    $action instanceof DeleteAction => $resource::getDeleteAuthorizationResponse($record),
    $action instanceof EditAction => $resource::getEditAuthorizationResponse($record),
    // ...
    default => null,
};
```

#### Reachability

Production panel admission is limited to scientists and super administrators; a partial Shield permission assignment is required.

- **Attacker:** authenticated panel user with view-only resource permission

- **Entry point:** custom row/header/bulk action

- **Outcome:** privileged model mutations

#### Severity

**Medium** — The actions can change moderation state, create taxa, merge citations, and delete source records, but exploitation requires authenticated panel access and a partial permission assignment not represented in the repository.

Additional runtime or deployment evidence could raise or lower this severity.

Impact assessment:
- **Level:** high
- **Why:** Actions can rewrite catalogue relationships and delete merged source records.

Likelihood assessment:
- **Level:** medium
- **Why:** Depends on the deployed role having a partial permission set.

#### Remediation

Attach explicit policy authorization to every custom row, header, and bulk action, including per-record checks; merges must authorize updates to both records and deletion of the source, and NIS approval must authorize taxon creation.

Tests:
- With only `ViewAny:Literature`, assert approve, reject, bulk moderation, and merge are unavailable and rejected server-side.
- With `ViewAny:NisSuggestion` but no update/create permissions, assert approve and reject are rejected.
- Assert bulk taxon actions authorize every selected record.

Preventive controls:
- Require custom Filament actions to declare the exact policy abilities for every affected model.

<a id="finding-4"></a>

### [4] Scientists can read and delete application logs through a hidden page

| Field | Value |
| --- | --- |
| Severity | medium |
| Confidence | high |
| Confidence rationale | Panel admission, plugin defaults, page route, read sink, and unlink sink were independently traced in installed source. |
| Category | broken-access-control |
| CWE | CWE-862, CWE-200 |
| Affected lines | apps/config/filament-logs-explorer.php:120-159, apps/app/Providers/Filament/MamiasPanelProvider.php:245-254, apps/vendor/laboiteacode/filament-logs-explorer/src/Pages/LogsExplorer.php:294-337 |

#### Summary

The panel hides the System navigation group from scientists, but the registered logs explorer has no access or deletion gate. Any production panel user can visit `/mamias/logs`, read or download Laravel logs, and unlink them.

#### Root Cause

The application treats navigation hiding as a role boundary while the plugin's server-side access and deletion controls remain at their allow-by-default settings.

**Logs explorer is registered without authorization callbacks** — `apps/app/Providers/Filament/MamiasPanelProvider.php:245-254`

The plugin is reachable in the panel and configures the sensitive log directory, but no access or deletion callback.

```php
FilamentLogsExplorerPlugin::make()
    ->navigationLabel('Application logs')
    ->navigationGroup('System')
    ->channels(['daily', 'single'])
    ->excludeChannels(['emergency'])
    ->expandStacks()
    ->filesPerChannel(20)
    ->discoverUntrackedFiles(directory: storage_path('logs')),
```

**Access and deletion gates default open** — `apps/config/filament-logs-explorer.php:120-159`

Plugin source returns true when these gates are unset, so hiding navigation does not restrict the route or actions.

```php
'authorization' => [
    'gate' => null,
],
// ...
'deletion' => [
    'enabled' => true,
    'gate' => null,
],
```

**The page unlinks selected logs** — `apps/vendor/laboiteacode/filament-logs-explorer/src/Pages/LogsExplorer.php:294-337`

Because `canDelete()` defaults true, a scientist can permanently remove a resolved application log.

```php
protected function deleteFile(string $id): bool
{
    if (! $this->canDelete()) { /* ... */ }
    $file = $this->repository()->find($id);
    // ...
    if (is_file($file->path) && ! @unlink($file->path)) { /* ... */ }
}
```

#### Validation

Scientists pass panel access, the page slug is `logs`, and plugin source allows reading/deleting when no callbacks or gates are configured.

Validation method: independent static authorization trace

**Logs explorer is registered without authorization callbacks** — `apps/app/Providers/Filament/MamiasPanelProvider.php:245-254`

The plugin is reachable in the panel and configures the sensitive log directory, but no access or deletion callback.

```php
FilamentLogsExplorerPlugin::make()
    ->navigationLabel('Application logs')
    ->navigationGroup('System')
    ->channels(['daily', 'single'])
    ->excludeChannels(['emergency'])
    ->expandStacks()
    ->filesPerChannel(20)
    ->discoverUntrackedFiles(directory: storage_path('logs')),
```

**Access and deletion gates default open** — `apps/config/filament-logs-explorer.php:120-159`

Plugin source returns true when these gates are unset, so hiding navigation does not restrict the route or actions.

```php
'authorization' => [
    'gate' => null,
],
// ...
'deletion' => [
    'enabled' => true,
    'gate' => null,
],
```

**The page unlinks selected logs** — `apps/vendor/laboiteacode/filament-logs-explorer/src/Pages/LogsExplorer.php:294-337`

Because `canDelete()` defaults true, a scientist can permanently remove a resolved application log.

```php
protected function deleteFile(string $id): bool
{
    if (! $this->canDelete()) { /* ... */ }
    $file = $this->repository()->find($id);
    // ...
    if (is_file($file->path) && ! @unlink($file->path)) { /* ... */ }
}
```

Limitations:
- Production log contents were unavailable, so specific exposed secrets or personal data were not asserted.

#### Dataflow

panel session -\> logs page -\> plugin repository -\> file read/download/unlink

- **Source:** authenticated scientist request

- **Sink:** Laravel log filesystem

- **Outcome:** operational-data disclosure or log deletion

**Logs explorer is registered without authorization callbacks** — `apps/app/Providers/Filament/MamiasPanelProvider.php:245-254`

The plugin is reachable in the panel and configures the sensitive log directory, but no access or deletion callback.

```php
FilamentLogsExplorerPlugin::make()
    ->navigationLabel('Application logs')
    ->navigationGroup('System')
    ->channels(['daily', 'single'])
    ->excludeChannels(['emergency'])
    ->expandStacks()
    ->filesPerChannel(20)
    ->discoverUntrackedFiles(directory: storage_path('logs')),
```

**Access and deletion gates default open** — `apps/config/filament-logs-explorer.php:120-159`

Plugin source returns true when these gates are unset, so hiding navigation does not restrict the route or actions.

```php
'authorization' => [
    'gate' => null,
],
// ...
'deletion' => [
    'enabled' => true,
    'gate' => null,
],
```

**The page unlinks selected logs** — `apps/vendor/laboiteacode/filament-logs-explorer/src/Pages/LogsExplorer.php:294-337`

Because `canDelete()` defaults true, a scientist can permanently remove a resolved application log.

```php
protected function deleteFile(string $id): bool
{
    if (! $this->canDelete()) { /* ... */ }
    $file = $this->repository()->find($id);
    // ...
    if (is_file($file->path) && ! @unlink($file->path)) { /* ... */ }
}
```

#### Reachability

Production panel allows the scientist role; hiding System navigation does not block direct routing.

- **Attacker:** authenticated scientist

- **Entry point:** `/mamias/logs`

- **Outcome:** read/download/delete application logs

#### Severity

**Medium** — The issue exposes operational data and permits audit-log deletion across the scientist-to-administrator boundary, but requires authenticated panel access and actual log sensitivity is deployment-dependent.

Additional runtime or deployment evidence could raise or lower this severity.

Impact assessment:
- **Level:** medium
- **Why:** Logs may contain request, exception, operational, or personal data and their deletion impairs incident review.

Likelihood assessment:
- **Level:** high
- **Why:** No additional permission is required once the scientist enters the panel.

#### Remediation

Restrict both page access and deletion to an explicit super-admin gate or callbacks; disable deletion unless operationally required, and test direct route and Livewire-action denial for scientists.

Tests:
- Assert a scientist receives 403 for `/mamias/logs`.
- Assert a scientist cannot mount view, download, or delete actions directly.
- Assert only the intended administrator role can delete a log.

Preventive controls:
- Treat navigation visibility as presentation only and configure server-side gates for every System plugin.

<a id="finding-5"></a>

### [5] Taxon editors can delete nonduplicate records without delete permission

| Field | Value |
| --- | --- |
| Severity | low |
| Confidence | high |
| Confidence rationale | A repository test directly invokes the handler, and the installed EditRecord flow checks update rather than delete permission. |
| Category | missing-authorization |
| CWE | CWE-862 |
| Affected lines | apps/app/Filament/Resources/Taxons/Pages/EditTaxon.php:93-103 |

#### Summary

The public Livewire `deleteDuplicateTaxon()` handler deletes the mounted record without authorizing `Delete:Taxon` or repeating the duplicate check that controls the normal UI path.

#### Root Cause

A destructive public component method assumes it is reachable only from the warning UI and omits server-side authorization and invariant checks.

**Duplicate condition exists only before save** — `apps/app/Filament/Resources/Taxons/Pages/EditTaxon.php:51-64`

The application computes the destructive action's intended precondition only during `beforeSave()`.

```php
if ($this->record->catalogue_status !== Catalogue_Status::not_checked) {
    return;
}
$duplicate = Taxon::findDuplicateOf(...);
```

**Callable handler deletes immediately** — `apps/app/Filament/Resources/Taxons/Pages/EditTaxon.php:93-103`

A direct Livewire call bypasses both `Delete:Taxon` and the duplicate/status precondition.

```php
#[On('deleteDuplicateTaxon')]
public function deleteDuplicateTaxon(): void
{
    $this->record->delete();
    // ...
}
```

#### Validation

EditRecord authorizes update access on hydration; `TaxonPolicy` defines delete separately, and the handler never invokes it.

Validation method: independent static authorization trace

**Duplicate condition exists only before save** — `apps/app/Filament/Resources/Taxons/Pages/EditTaxon.php:51-64`

The application computes the destructive action's intended precondition only during `beforeSave()`.

```php
if ($this->record->catalogue_status !== Catalogue_Status::not_checked) {
    return;
}
$duplicate = Taxon::findDuplicateOf(...);
```

**Callable handler deletes immediately** — `apps/app/Filament/Resources/Taxons/Pages/EditTaxon.php:93-103`

A direct Livewire call bypasses both `Delete:Taxon` and the duplicate/status precondition.

```php
#[On('deleteDuplicateTaxon')]
public function deleteDuplicateTaxon(): void
{
    $this->record->delete();
    // ...
}
```

Limitations:
- The record is Livewire-locked and deletion is soft, limiting impact.

#### Dataflow

edit-page component -\> public handler -\> Eloquent soft delete

- **Source:** direct Livewire method/event invocation

- **Sink:** `Taxon::delete()`

- **Outcome:** soft deletion without delete permission

**Callable handler deletes immediately** — `apps/app/Filament/Resources/Taxons/Pages/EditTaxon.php:93-103`

A direct Livewire call bypasses both `Delete:Taxon` and the duplicate/status precondition.

```php
#[On('deleteDuplicateTaxon')]
public function deleteDuplicateTaxon(): void
{
    $this->record->delete();
    // ...
}
```

#### Reachability

Requires `Update:Taxon` and access to the selected edit page.

- **Attacker:** taxon editor without delete permission

- **Entry point:** `deleteDuplicateTaxon` Livewire method

- **Outcome:** mounted record is soft-deleted

#### Severity

**Low** — The action bypasses a distinct delete permission, but requires existing taxon-update access, affects only the locked mounted record, and performs a recoverable soft delete.

Additional runtime or deployment evidence could raise or lower this severity.

Impact assessment:
- **Level:** medium
- **Why:** A valid catalogue record becomes unavailable until restored.

Likelihood assessment:
- **Level:** medium
- **Why:** Direct invocation is straightforward once edit access exists.

#### Remediation

Authorize deletion inside `deleteDuplicateTaxon()` and recompute the duplicate and status predicates before deleting; preferably use a policy-aware `DeleteAction` with the duplicate guard.

Tests:
- Assert a user with update but without delete permission receives a denial.
- Assert the handler refuses a record that is not currently a verified duplicate.

Preventive controls:
- Keep destructive authorization and business preconditions inside the server-side handler.

<a id="finding-6"></a>

### [6] Users keep verified status after replacing their email address

| Field | Value |
| --- | --- |
| Severity | low |
| Confidence | high |
| Confidence rationale | The form, mass assignment, model hook, and Laravel verification trait were independently traced. |
| Category | authentication |
| CWE | CWE-287 |
| Affected lines | apps/app/Livewire/PublicProfile.php:155-162, apps/app/Livewire/PublicProfile.php:228-232, apps/app/Models/User.php:93-99 |

#### Summary

The verified profile page lets a user replace `email` and saves it directly. The model never clears `email_verified_at`, so Laravel continues treating an address the user does not control as verified.

#### Root Cause

The account state transition changes the verified identifier without invalidating or re-establishing the proof attached to the old identifier.

**Profile accepts a replacement email** — `apps/app/Livewire/PublicProfile.php:155-162`

Syntax and uniqueness are checked, but mailbox ownership is not.

```php
TextInput::make('email')
    ->email()
    ->required()
    ->maxLength(255)
    ->unique(ignoreRecord: true)
```

**Form state updates the verified user directly** — `apps/app/Livewire/PublicProfile.php:228-232`

The email transition is committed without clearing the prior verification timestamp or staging the new address.

```php
$data = $this->form->getState();

auth()->user()->update($data);
```

**Model hook does not invalidate verification** — `apps/app/Models/User.php:93-99`

The only saving hook handles names; no application control clears `email_verified_at` when `email` changes.

```php
static::saving(function ($user) {
    if ($user->isDirty(['first_name', 'last_name'])) {
        $user->name = $user->getComputedFullName();
    }
});
```

#### Validation

Laravel's `MustVerifyEmail` checks only whether `email_verified_at` is non-null; this update preserves it.

Validation method: independent static state-transition trace

**Profile accepts a replacement email** — `apps/app/Livewire/PublicProfile.php:155-162`

Syntax and uniqueness are checked, but mailbox ownership is not.

```php
TextInput::make('email')
    ->email()
    ->required()
    ->maxLength(255)
    ->unique(ignoreRecord: true)
```

**Form state updates the verified user directly** — `apps/app/Livewire/PublicProfile.php:228-232`

The email transition is committed without clearing the prior verification timestamp or staging the new address.

```php
$data = $this->form->getState();

auth()->user()->update($data);
```

**Model hook does not invalidate verification** — `apps/app/Models/User.php:93-99`

The only saving hook handles names; no application control clears `email_verified_at` when `email` changes.

```php
static::saving(function ($user) {
    if ($user->isDirty(['first_name', 'last_name'])) {
        $user->name = $user->getComputedFullName();
    }
});
```

Limitations:
- No runtime test was executed; the repository does not show every downstream decision that relies on verified email.

#### Dataflow

profile email input -\> direct Eloquent update -\> stale non-null `email_verified_at` -\> verified middleware

- **Source:** replacement email

- **Sink:** account verification state

- **Outcome:** unowned address remains marked verified

**Profile accepts a replacement email** — `apps/app/Livewire/PublicProfile.php:155-162`

Syntax and uniqueness are checked, but mailbox ownership is not.

```php
TextInput::make('email')
    ->email()
    ->required()
    ->maxLength(255)
    ->unique(ignoreRecord: true)
```

**Form state updates the verified user directly** — `apps/app/Livewire/PublicProfile.php:228-232`

The email transition is committed without clearing the prior verification timestamp or staging the new address.

```php
$data = $this->form->getState();

auth()->user()->update($data);
```

**Model hook does not invalidate verification** — `apps/app/Models/User.php:93-99`

The only saving hook handles names; no application control clears `email_verified_at` when `email` changes.

```php
static::saving(function ($user) {
    if ($user->isDirty(['first_name', 'last_name'])) {
        $user->name = $user->getComputedFullName();
    }
});
```

#### Reachability

Requires an authenticated account that was previously verified.

- **Attacker:** verified public user

- **Entry point:** `/profile` save action

- **Outcome:** verification state transferred to an unverified address

#### Severity

**Low** — The trust-state invariant is definitely broken, but the attacker already controls the account and the source establishes identity confusion rather than account takeover or privilege escalation.

Additional runtime or deployment evidence could raise or lower this severity.

Impact assessment:
- **Level:** low
- **Why:** Enables identity misrepresentation and misdirected communications without taking another account.

Likelihood assessment:
- **Level:** high
- **Why:** The ordinary profile form exposes the transition.

#### Remediation

When `email` changes, clear `email_verified_at`, send verification to the replacement address, and block verified-only workflows until confirmation; alternatively keep the current address active until the new one is confirmed.

Tests:
- Change a verified user's email and assert `email_verified_at` becomes null.
- Assert verified-only routes reject the session until the new verification link is completed.

Preventive controls:
- Centralize verified-email changes in one service that binds address replacement to re-verification.

## Reviewed Surfaces

| Surface | Risk Area | Outcome | Notes |
| --- | --- | --- | --- |
| Taxon input and HTML rendering | not recorded | Reported | Validated stored-XSS paths through authority tooltips and EASIN helper HTML. |
| Filament resource action authorization | not recorded | Reported | Validated permission bypasses across literature, NIS suggestion, and taxon custom actions. |
| Taxon duplicate-deletion Livewire handler | not recorded | Reported | Validated delete-permission and duplicate-precondition bypass. |
| System plugin authorization | not recorded | Reported | Validated scientist access to the logs explorer and deletion actions; other explicitly gated system plugins were reviewed as controls. |
| Email verification lifecycle | not recorded | Reported | Validated stale verification after profile email replacement. |
| Tracked backup artifacts | not recorded | Reported | Validated database table-data and PostgreSQL role-verifier material in Git-tracked backups. |
| Public submissions and import downloads | not recorded | No issue found | Representative personal queries are owner-scoped; failed-import downloads enforce authentication and import ownership. |
| Fixed-destination external service clients and CAPTCHA | not recorded | No issue found | Reviewed configured WoRMS, GBIF, EASIN, Crossref, WhatsApp, and CAPTCHA boundaries without a validated injection or SSRF path. |
| Public literature PDF upload | not recorded | Needs follow-up | MIME-only validation plus retention of the client extension could allow active same-origin HTML if a PDF/HTML polyglot named `.html` is accepted and production Caddy serves it as HTML. Static repository evidence did not establish the final response MIME or polyglot acceptance. |
| Application, deployment, authentication, authorization, rendering and upload surfaces | not recorded | Needs follow-up | Independent validation is in progress for saved candidates. |
| Scan storage recovery | not recorded | Needs follow-up | Existing scan relocated to private Linux filesystem with mode 0700, retaining scan identity and backing up registry. Security review remains incomplete. |

## Open Questions And Follow Up

- What exact Shield permissions are assigned to the production scientist role?
- Are the tracked database dump and role verifiers derived from production or another sensitive environment?
- How does the deployed FrankenPHP/Caddy image label a MIME-valid PDF stored with an `.html` extension?
- Needs a controlled deployment-level check of MIME validation and Caddy response content type; no PoC was generated during this read-only scan.
  - Follow-up prompt: Review deferred unit pdf-upload-extension and close its stated proof gap.
- Independent baseline finding awaiting parent validation.
  - Follow-up prompt: Review deferred unit easin-helper-xss and close its stated proof gap.
