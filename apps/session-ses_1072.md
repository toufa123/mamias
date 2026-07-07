# MAMIAS early warning system

**Session ID:** ses_1072e079bffeQaoznCNLLHXvPq
**Created:** 6/24/2026, 9:49:30 AM
**Updated:** 6/24/2026, 1:03:10 PM

---

## User

what could be done for MAMIAS web application,to adreess early warming system like EASIN or other EWS ? 

---

## Assistant (Plan · DeepSeek V4 Flash Free · 100.7s)

_Thinking:_

The user is asking about implementing an Early Warning System (EWS) like EASIN (European Alien Species Information Network) for the MAMIAS web application. Let me first understand the current application structure to provide a well-informed plan.

Let me explore the codebase to understand the current domain model, services, and architecture before making recommendations.

The key areas to investigate:
1. Current data model - what species/invasive species data exists
2. Existing services (like WormsService for taxonomy)
3. Filament resources structure
4. Existing models

Let me start by exploring the codebase.

**Tool: task**

**Input:**
```json
{
  "description": "Explore MAMIAS codebase structure",
  "subagent_type": "explore",
  "prompt": "Thoroughly explore the MAMIAS Laravel application at /home/toufa/dev/2026/apps/mamias/apps to understand:\n\n1. What models exist in app/Models/ - list all models and their key fields/relationships\n2. What database migrations exist - especially anything related to species, invasive species, alerts, observations, notifications\n3. What services exist in app/Services/ - especially WormsService\n4. What Filament resources exist - especially related to species, alerts, observations\n5. What factories, seeders exist\n6. The database schema - use the laravel-boost tools (database-schema) if available, or check migrations\n7. Any existing routes related to APIs, webhooks, or EWS\n8. Check config/ for any related configuration\n9. Check jobs/ for any queue jobs\n\nReturn a comprehensive summary of:\n- All models with their fields (especially species-related ones)\n- All existing services\n- All Filament resources\n- Database schema for key tables\n- Any existing EWS/alert/observation related code\n- The current users table structure (for notification preferences)\n- Queue/job infrastructure"
}
```

**Output:**
```
<task id="ses_1072def1effe39fyRwV7FMV4Zd" state="completed">
<task_result>
Now I have a complete picture. Here is the comprehensive summary:

---

## COMPREHENSIVE MAMIAS APPLICATION ANALYSIS

---

### 1. ALL MODELS

#### 1.1 `User` (`app/Models/User.php`)
- **Table:** `users` (Laravel default, plus profile fields)
- **Key fields:** `name`, `email`, `password`, `email_verified_at`
- **Profile fields:** `first_name`, `last_name`, `title`, `phone`, `has_whatsapp` (bool), `country`, `taxonomic_area` (json), `subregions` (json), `countries` (json), `bio`
- **Casts:** `has_whatsapp` (boolean), `taxonomic_area` (array), `subregions` (array), `countries` (array)
- **Relationships:**
  - `literatures()`: HasMany via `created_by`
  - `nisSuggestions()`: HasMany
  - `introEventRecords()`: HasMany via `created_by`
  - `occurrences()`: HasMany
- **Roles:** Uses `Spatie\Permission\Traits\HasRoles` -- roles: `super_admin`, `scientist`, `user`
- **Filament contracts:** `FilamentUser`, `HasAvatar`, `HasName`, `MustVerifyEmail`

#### 1.2 `Taxon` (`app/Models/Taxon.php`) -- **CORE SPECIES MODEL**
- **Table:** `taxas`
- **Key fields:**
  - `aphia_id` (int, WoRMS identifier)
  - `scientificname` (string, unique, indexed)
  - `authority` (string)
  - `worms_status` (Worms_Status enum)
  - `catalogue_status` (Catalogue_Status enum)
  - `unacceptreason` (text)
  - `rank`, `kingdom`, `phylum`, `class`, `order`, `family`, `genus`
  - `lsid` (string, LSID urn)
  - `Easin_id` (text, EASIN identifier)
  - `is_extinct` (boolean)
  - `environments` (json array)
  - `synonyms_data` (json array of synonyms)
  - `proposed_accepted_name` (string)
  - `fetched_at` (datetime)
  - `url` (string), `notes` (text)
  - `created_by`, `updated_by` (userstamps)
- **Traits:** `SoftDeletes`, `Userstamps`
- **Booted:** Taxonomizer normalization on `saving`
- **Relationships:**
  - `introEvents()`: HasMany (IntroEventRecord)
  - `nisSuggestions()`: HasMany (NisSuggestion)

#### 1.3 `Occurrence` (`app/Models/Occurrence.php`) -- **OBSERVATION/REPORT MODEL**
- **Table:** `occurrences`
- **Key fields:**
  - `user_id` (FK to users)
  - `intro_event_record_id` (FK to intro_event_records)
  - `location` (json, cast to CoordinatesCast) -- lat/lng array
  - `location_point` (geometry Point, 4326 via Magellan/PostGIS)
  - `depth` (decimal 8,2)
  - `acfor_scale` (AcforScale enum)
  - `habitats` (json array)
  - `photo_paths` (json array)
  - `notes` (text)
  - `observed_at` (datetime)
  - `status` (OccurrenceStatus enum: pending/approved/rejected)
  - `moderation_notes` (text)
- **Traits:** `HasSpatialLocation` (provides `scopeNear()`, `scopeWithinBoundingBox()`, `scopeOrderByDistance()`, `scopeWithDistanceFrom()`)
- **Booted:** Auto-generates `location_point` from `location` on save
- **Relationships:**
  - `user()`: BelongsTo(User)
  - `introEventRecord()`: BelongsTo(IntroEventRecord)
  - `taxon()`: HasOneThrough (via IntroEventRecord)

#### 1.4 `NisSuggestion` (`app/Models/NisSuggestion.php`) -- **SPECIES SUGGESTION/REPORT MODEL**
- **Table:** `nis_suggestions`
- **Key fields:**
  - `user_id` (FK to users)
  - `aphia_id` (int, nullable)
  - `suggested_scientific_name` (string, required)
  - `authority` (string, nullable)
  - `worms_status` (string, nullable)
  - `suggested_common_name` (string, nullable)
  - `location` (json, cast to CoordinatesCast)
  - `location_point` (geometry Point, 4326)
  - `depth` (decimal 8,2)
  - `kingdom` (string)
  - `acfor_scale` (AcforScale enum)
  - `habitats` (json array)
  - `photo_paths` (json array)
  - `document_paths` (json array)
  - `status` (LiteratureStatus enum: pending/approved/rejected)
  - `rejection_reason` (text)
  - `taxon_id` (FK to taxas, nullable)
  - `resubmitted_from_id` (FK to self, nullable)
  - `created_by`, `updated_by`, `deleted_by` (userstamps)
- **Traits:** `SoftDeletes`, `HasSpatialLocation`, `Userstamps`
- **Booted:** Strip_tags on name fields; auto-generate `location_point` from `location`
- **Relationships:**
  - `user()`: BelongsTo(User)
  - `literatures()`: BelongsToMany via `nis_suggestion_literature`
  - `taxon()`: BelongsTo(Taxon)
  - `resubmittedFrom()`: BelongsTo(self)
  - `resubmissions()`: HasMany(self)

#### 1.5 `IntroEventRecord` (`app/Models/IntroEventRecord.php`) -- **INTRODUCTION EVENT MODEL**
- **Table:** `intro_event_records`
- **Key fields:**
  - `taxon_id` (FK to taxas)
  - `first_introduction_year` (int, nullable)
  - `first_country` (json array)
  - `nis_status` (NisStatus enum)
  - `establishment_status` (EstablishmentStatus enum)
  - `literature_id` (FK to literatures)
  - `notes` (text)
  - `created_by`, `updated_by`
- **Relationships:**
  - `taxon()`: BelongsTo(Taxon)
  - `literature()`: BelongsTo(Literature)
  - `subregionRecords()`: HasMany
  - `pathwayRecords()`: HasMany
  - `occurrences()`: HasMany

#### 1.6 `Literature` (`app/Models/Literature.php`)
- **Table:** `literatures`
- **Key fields:** `code` (unique, auto-generated `mamias######`), `doi` (unique), `type` (LiteratureType enum), `short_ref`, `full_ref`, `link`, `file_path`, `status`, `created_by`, `updated_by`
- **ObservedBy:** `LiteratureObserver`
- **Relationships:**
  - `introEvents()`: HasMany(IntroEventRecord)

#### 1.7 `PathwayRecord` (`app/Models/PathwayRecord.php`)
- **Table:** `pathway_records`
- **Key fields:** `intro_event_id`, `category` (CbdPathwayCategory), `subcategory` (CbdPathwaySubcategory), `pathway_type` (PathwayType), `description`, `uncertainty` (DataQuality)
- **Relationships:**
  - `introEvent()`: BelongsTo(IntroEventRecord)

#### 1.8 `SubregionRecord` (`app/Models/SubregionRecord.php`)
- **Table:** `subregion_records`
- **Key fields:** `intro_event_id`, `subregion` (Subregion enum), `nis_status` (NisStatus), `first_arrival_year`, `notes`
- **Relationships:**
  - `introEvent()`: BelongsTo(IntroEventRecord)

#### 1.9 Trait: `HasSpatialLocation` (`app/Models/Traits/HasSpatialLocation.php`)
- Adds `location_point` cast as Magellan Point
- Scopes: `near($lat, $lng, $meters)`, `withinBoundingBox()`, `orderByDistance()`, `withDistanceFrom()`
- Used by: `Occurrence` and `NisSuggestion`

---

### 2. DATABASE SCHEMA -- KEY TABLES

#### `users` table
```
id, name, email (unique), email_verified_at (nullable), password, remember_token,
first_name (nullable), last_name (nullable), title (nullable), phone (nullable),
has_whatsapp (default false), country (nullable), taxonomic_area (json, nullable),
subregions (json, nullable), countries (json, nullable), bio (text, nullable),
created_at, updated_at
```

#### `taxas` table
```
id, aphia_id (nullable, int), url (nullable), scientificname (nullable, unique, indexed),
authority (nullable), worms_status (nullable), catalogue_status (nullable),
unacceptreason (nullable, text), rank (nullable), kingdom (nullable, indexed),
phylum (nullable, indexed), class (nullable), order (nullable), family (nullable, indexed),
genus (nullable), lsid (nullable), Easin_id (nullable, text), is_extinct (nullable),
environments (nullable), synonyms_data (nullable, json), fetched_at (nullable, datetime),
notes (nullable, text), created_by (nullable), updated_by (nullable),
deleted_at (nullable), created_at, updated_at
+ indexes on kingdom, phylum, family, rank, catalogue_status, aphia_id
```

#### `occurrences` table
```
id, user_id (FK->users), intro_event_record_id (FK->intro_event_records),
location (json), location_point (geometry Point 4326, indexed with GIST),
depth (decimal 8,2), acfor_scale (string 20), habitats (json, nullable),
photo_paths (json, nullable), notes (text, nullable), observed_at (datetime),
status (string), moderation_notes (text, nullable), created_at, updated_at
```

#### `nis_suggestions` table
```
id, user_id (FK->users), aphia_id (nullable, int),
suggested_scientific_name (string), authority (nullable),
worms_status (string 100, nullable), suggested_common_name (nullable),
location (json, nullable), location_point (geometry Point 4326, indexed GIST),
depth (decimal 8,2), kingdom (nullable), acfor_scale (nullable),
habitats (json, nullable), bibliography (nullable, text), doi (nullable),
photo_paths (json, nullable), document_paths (json, nullable),
status (string), rejection_reason (nullable, text),
taxon_id (nullable, FK->taxas), resubmitted_from_id (nullable, FK->nis_suggestions),
created_by (nullable, FK->users), updated_by (nullable, FK->users),
deleted_by (nullable, FK->users), deleted_at, created_at, updated_at
+ indexes on user_id, status
```

#### `intro_event_records` table
```
id, taxon_id (FK->taxas), first_introduction_year (nullable),
first_country (string, nullable), nis_status (nullable),
establishment_status (nullable), literature_id (FK->literatures),
notes (nullable, text), created_by, updated_by, created_at, updated_at
+ indexes on nis_status, establishment_status, first_country
```

#### `literatures` table
```
id, code (unique, nullable), short_ref (nullable), full_ref (nullable, text),
link (nullable, text), doi (unique, nullable), type (nullable),
status (default 'pending'), file_path (nullable), created_by, updated_by,
created_at, updated_at
+ indexes on created_by, status
```

#### `pathway_records` table
```
id, intro_event_id (FK->intro_event_records), category (string),
subcategory (string), pathway_type (string), description (nullable, text),
notes (nullable, text), created_by, updated_by, created_at, updated_at
+ indexes on category, pathway_type
```

#### `subregion_records` table
```
id, intro_event_id (FK->intro_event_records), subregion (string),
nis_status (nullable), first_arrival_year (nullable, int),
notes (nullable, text), created_by, updated_by, created_at, updated_at
+ indexes on subregion, nis_status
```

#### `nis_suggestion_literature` (pivot)
```
nis_suggestion_id (FK), literature_id (FK), timestamps
Primary: (nis_suggestion_id, literature_id)
```

#### `notifications` table (Laravel standard)
```
id (uuid), type (string), notifiable_type, notifiable_id, data (json),
read_at (nullable), created_at, updated_at
```

#### `jobs` table (database queue)
```
id, queue (indexed), payload, attempts, reserved_at, available_at, created_at
+ job_batches, failed_jobs tables
```

---

### 3. ALL SERVICES

| Service | File | Purpose |
|---|---|---|
| **WormsService** | `app/Services/WormsService.php` | WoRMS REST API integration: search species, get records by AphiaID, match taxa, get synonyms, phyla fetching, populate Taxon model from WoRMS data. Uses caching with `worms_v2.*` keys. |
| **WhatsAppService** | `app/Services/WhatsAppService.php` | WhatsApp number validation via GreenAPI (or E.164 fallback). 7-day cache. |
| **EasinService** | `app/Services/EasinService.php` | Fetches EASIN ID from JRC API for a scientific name. 24h cache. |
| **TaxonService** | `app/Services/TaxonService.php` | Orchestrates WoRMS sync for Taxon records, change detection, form state management. Dependencies: WormsService, TaxonNormalizer, TaxonStateHelper, EasinService. |
| **TaxonNormalizer** | `app/Services/TaxonNormalizer.php` | Scientific name normalization (encoding, nomen rules, affinity, rank, kingdom-specific rules), LSID generation. |
| **TaxonStateHelper** | `app/Services/TaxonStateHelper.php` | Compares current vs incoming WoRMS data states, formats changed field labels, builds fetched data states for form population. |
| **CapService** | `app/Services/CapService.php` | CAPTCHA verification service (internal CAP instance at cap:3000). |
| **DoiMetadataService** | `app/Services/DoiMetadataService.php` | Fetches DOI metadata from Crossref API, formats references, maps to LiteratureType. |

---

### 4. ALL FILAMENT RESOURCES

| Resource | Navigation Group | Icon | Pages |
|---|---|---|---|
| **UserResource** | Use management | `tabler-users` | List, Create, Edit |
| **TaxonResource** | MAMIAS database | `tabler-fish` | List, Create, Edit |
| **IntroEventRecordResource** | MAMIAS database | `tabler-calendar-event` | List, Create, Edit (with Occurrences RelationManager) |
| **OccurrenceResource** (implied) | (auto-discovered) | (auto) | View/Edit actions: Approve, Reject |
| **NisSuggestionResource** | MAMIAS database | `tabler-bulb` | List, View only (no create/edit in panel) |
| **LiteratureResource** | MAMIAS database | `tabler-book` | List, Create, Edit |
| **CommandRunResource** | System | `heroicon-o-command-line` | List (super_admin only) |

Additional Filament components:
- **NisSuggestionActions** -- approve/reject actions with email notifications
- **OccurrenceActions** -- approve/reject with email notifications
- **OccurrenceLocationsMapEntry**, **SpeciesLocationsMapEntry** -- map infolist entries
- **IntroEventRecordsTable**, **NisSuggestionsTable**, **OccurrencesTable**, etc.
- **RelationManagers:** `OccurrencesRelationManager` (on both UserResource and IntroEventRecordResource)

**Navigation Groups:** Dashboard, Use management, MAMIAS database, System, Settings

---

### 5. EWS / ALERT / OBSERVATION-RELATED CODE

**Direct EWS code: NONE exists yet.** There is no Early Warning System module, no webhook endpoints, no dedicated alert models/tables/controllers/routes.

**What exists that relates to alerts/notifications:**

1. **Alert Box Plugin** (`config/filament-alert-box.php`, migration `2026_05_14_141918_create_alert_box_settings.php`):
   - A Filament settings page using `spatie/laravel-settings` to manage admin alert boxes (info/tip/success/warning/danger banners in the panel UI)
   - Stores `alert-box.alerts` as a settings value

2. **Database Notifications** (Laravel built-in):
   - Table `notifications` exists
   - Panel has `->databaseNotifications()` enabled
   - `NewLiteratureReferenceNotification` sends both `mail` and `database`

3. **Mail Notifications** (via Queue):
   - `OccurrenceApproved` -- mail when occurrence is approved
   - `OccurrenceRejected` -- mail when occurrence is rejected
   - `NisSuggestionApproved` -- mail when suggestion is approved
   - `NisSuggestionRejected` -- mail when suggestion is rejected
   - `NewLiteratureReferenceNotification` -- mail + database on new literature
   - `VerifyEmail` -- custom email verification

4. **Refresh Sidebar Plugin**: Auto-refreshes sidebar navigation when notifications arrive via Laravel Echo

5. **CommandRunner Plugin**: Super-admin command runner, has warning icon in nav

**No EWS-specific code exists:**
- No dedicated EWS models/tables
- No webhooks
- No API routes
- No alert notification preferences on users
- No push/SMS notification infrastructure
- No early warning business logic

---

### 6. FACTORIES & SEEDERS

**Factories (8 total):**
| Factory | Model |
|---|---|
| `UserFactory` | User |
| `TaxonFactory` | Taxon |
| `OccurrenceFactory` | Occurrence |
| `NisSuggestionFactory` | NisSuggestion |
| `LiteratureFactory` | Literature |
| `IntroEventRecordFactory` | IntroEventRecord |
| `SubregionRecordFactory` | SubregionRecord |
| `PathwayRecordFactory` | PathwayRecord |

**Seeders (3 total):**
| Seeder | Purpose |
|---|---|
| `RolesSeeder` | Creates roles: `super_admin`, `scientist`, `user` |
| `DeveloperLoginUsersSeeder` | Creates dev users for local login |
| `DatabaseSeeder` | Calls RolesSeeder + DeveloperLoginUsersSeeder, creates a test@example.com public user with `user` role |

---

### 7. ROUTES

**Web routes** (`routes/web.php`):
- `GET /login` -> redirects to Filament login
- `GET /email-verification/prompt` -> redirects to Filament verification
- `GET /` -> `mamias.home` view (public landing)
- `GET /about` -> `mamias.about` view
- `GET /profile` (auth+verified) -> Livewire `PublicProfile`
- `GET /references` (auth+verified) -> Livewire `MyReferences`
- `GET /my-species-reports` (auth+verified) -> Livewire `MySpeciesReports`
- `GET /my-suggestions` (auth+verified) -> Livewire `MySuggestions`
- `GET mamias/decompose` (auth, role:super_admin) -> Decomposer

**Console routes** (`routes/console.php`): Only `inspire` command.

**Breadcrumbs** (`routes/breadcrumbs.php`): Home > About, Profile, References, My Species Reports, Suggestions.

**No API routes.** No webhook routes. No EWS routes.

---

### 8. QUEUE/JOB INFRASTRUCTURE

**Queue Config** (`config/queue.php`):
- Default connection: `database` (database-driven queue)
- Supports: sync, database, beanstalkd, sqs, redis, deferred, background, failover
- Failed jobs: `database-uuids` driver
- Job batching: enabled

**Jobs (2 custom + 1 trait):**
| Job | Purpose | Timeout | Retries |
|---|---|---|---|
| `FetchTaxaFromWormsJob` | Batch-refreshes Taxon records from WoRMS API | 1800s | 3 (30, 60, 120) |
| `FetchEasinIdsJob` | Batch-fetches EASIN IDs for Taxon records | 1800s | 3 (30, 60, 120) |

**Concern:** `TracksJobProgress` -- provides progress tracking via cache keys (`worms-fetch-progress-{userId}`, `easin-fetch-progress-{userId}`) with estimated time remaining.

---

### 9. LIVE WIRE COMPONENTS (Public-Facing)

| Component | Route | Purpose |
|---|---|---|
| `PublicProfile` | `/profile` | User profile page |
| `MyReferences` | `/references` | User's bibliographic references |
| `MySpeciesReports` | `/my-species-reports` | User's species reports |
| `MySuggestions` | `/my-suggestions` | User's species suggestions |
| `AddLiterature` | (embedded) | Add literature component |
| `CreateSuggestion` | (embedded) | Create suggestion component |
| `ImportWizard` | (embedded) | Data import wizard |

---

### 10. ENUMS (16 total)

| Enum | Values |
|---|---|
| `AcforScale` | ACFOR abundance scale |
| `AbundanceCategory` | Abundance categories |
| `Catalogue_Status` | Catalogue status (WoRMS data-related) |
| `CbdPathwayCategory` | CBD pathway categories |
| `CbdPathwaySubcategory` | CBD pathway subcategories |
| `DataQuality` | Data quality levels (uncertainty) |
| `EstablishmentStatus` | Invasive/established/cryptogenic/etc. |
| `Environment` | Marine environment types |
| `Habitat` | Habitat types |
| `LiteratureStatus` | pending/approved/rejected |
| `LiteratureType` | Article/Thesis/Book/etc. |
| `NisStatus` | NIS status values |
| `OccurrenceStatus` | pending/approved/rejected |
| `PathwayType` | primary/secondary |
| `Subregion` | Geographic subregions |
| `Worms_Status` | WoRMS taxon status values |

---

### 11. KEY CONFIGURATION FILES

| Config File | Key Settings |
|---|---|
| `services.php` | `greenapi` (instance_id, token), `cap` (site_key, secret_key, urls) |
| `queue.php` | Default `database`, failover to deferred |
| `filament-alert-box.php` | Alert box plugin settings |
| `filament-leaflet.php` | Map tile layer settings |
| `filament.php` | Panel theme, colors, SP mode, path `/mamias` |
| `permission.php` | Spatie permission config |
| `filament-shield.php` | Shield plugin (role-permission UI) |
| `magellan.php` | PostGIS/Magellan config |

---

### 12. SUMMARY OF GAPS (for future EWS/Alert development)

Based on this analysis, here is what is **missing** for an Early Warning System:

1. **No EWS models** -- no `Alert`, `AlertSubscription`, `NotificationPreference`, `WebhookEndpoint` etc.
2. **No EWS-specific database tables**
3. **No API routes** -- no REST or webhook endpoints
4. **No notification preference system on users** -- `users` table has no `notification_preferences`, `alert_channels`, `alert_regions`, etc.
5. **No push notification infrastructure** -- only mail + database notifications exist
6. **No SMS/WhatsApp outbound notification logic** (WhatsAppService only validates, it does not send)
7. **No scoring/alerting business logic** -- no service that evaluates occurrences/suggestions against thresholds to trigger alerts
8. **No webhook dispatch capability**
</task_result>
</task>
```

---

## Assistant (Plan · DeepSeek V4 Flash Free · 2060.9s)

_Thinking:_

Now I have a comprehensive understanding of the MAMIAS application. The user is asking about what could be done to address an Early Warning System (EWS) like EASIN (European Alien Species Information Network). Let me think about this carefully and formulate a plan.

Let me first research what EASIN actually does to make informed recommendations.

EASIN (European Alien Species Information Network) is a European Commission initiative that:
1. Provides a centralized information system on alien and invasive species in Europe
2. Offers early warning and rapid response mechanisms
3. Tracks species distribution and spread
4. Provides alerts for new or potentially invasive species
5. Connects with citizen science and monitoring networks

For MAMIAS (Mediterranean Marine Invasive Alien Species), an EWS would need to:

1. **Alert Generation**: Automatically detect when a new invasive species is reported in a new area
2. **Notification System**: Notify relevant users/stakeholders when alerts are triggered
3. **Risk Assessment**: Score species based on invasiveness potential, spread rate, impact
4. **Geographic Monitoring**: Track new occurrences in regions where species haven't been previously recorded
5. **Subscription System**: Users subscribe to alerts by region, taxonomic group, or species
6. **Public Dashboard**: Show active alerts and recent detections

Let me think about how this maps to the existing MAMIAS codebase:

**Existing strengths to leverage:**
- `Occurrence` model with spatial data (PostGIS) - tracks new observations
- `NisSuggestion` model - citizen science reports of potential NIS
- `IntroEventRecord` model - tracks first introductions
- `Taxon` model with `Easin_id` field - already linked to EASIN
- `SubregionRecord` - tracks spread by subregion
- `User` model with `subregions`, `countries`, `taxonomic_area` - user interests
- `WormsService`, `EasinService`, `TaxonService` - taxonomy APIs
- Database notifications already configured
- Queue infrastructure (database-driven)
- WhatsAppService (though only validation, not sending)

**What needs to be built:**

1. **Alert Model & Tables**
   - `alerts` table: tracks generated alerts
   - `alert_subscriptions` table: user subscriptions to alerts
   - `alert_logs` table: delivery tracking

2. **EWS Service Layer**
   - `EwsService`: evaluates occurrences/suggestions against criteria
   - `AlertService`: creates and manages alerts
   - `NotificationService`: multi-channel delivery (email, database, SMS, push)

3. **Alert Criteria/Triggers**
   - **New species in region**: An occurrence reported for a species not previously recorded in that subregion
   - **Range expansion**: Species found significantly outside known range
   - **High-impact species**: Detection of species with high invasiveness potential
   - **Rapid spread**: Multiple occurrences in short time

4. **User Subscription System**
   - Subscribe to alerts by: subregion, taxonomic group, species, keyword
   - Preferred notification channels: email, database notification, SMS, WhatsApp
   - Frequency: immediate, daily digest, weekly digest

5. **Alert Dashboard**
   - Filament resource for managing alerts
   - Public-facing alert page
   - Map visualization of active alerts

6. **Integration with EASIN**
   - Webhook/API to receive EASIN alerts
   - Push MAMIAS occurrences to EASIN
   - Sync with EASIN species list

7. **Webhook/API Endpoints**
   - REST API for external systems to receive alerts
   - Webhook endpoints for real-time event distribution

Let me formulate a comprehensive plan. Since I'm in plan mode, I should not make any edits. Let me present this as a well-structured plan.

Excellent — I now have a thorough understanding of the MAMIAS codebase. Let me formulate a concrete architectural plan.

---

## Early Warning System (EWS) Plan for MAMIAS

Based on a full codebase audit, the application already has strong foundations: spatial observations (`Occurrence` with PostGIS), citizen science reports (`NisSuggestion`), introduction events (`IntroEventRecord`), taxonomic data (`Taxon` with EASIN IDs), database notifications, and a queue infrastructure. **No EWS code exists yet** — what follows is what needs to be built.

---

### Phase 1: Data Model & Subscriptions

| What | Details |
|---|---|
| **`Alert` model** | `species_id`, `subregion`, `event_type` (new_arrival / range_expansion / high_impact / rapid_spread), `severity` (info/warning/critical), `title`, `description`, `source` (occurrence_id / nis_suggestion_id / external), `published_at`, `expires_at` |
| **`AlertSubscription` model** | `user_id`, `subregion` (nullable), `taxon_id` (nullable), `taxonomic_group` (nullable), `min_severity`, `channels` (json: mail/database/sms), `frequency` (immediate/daily/weekly), `last_sent_at` |
| **User notification preferences** | Add `notification_preferences` JSON column to `users` table — default channels, quiet hours, max frequency |

**Why:** Users already have `subregions`, `countries`, and `taxonomic_area` fields. Subscription records let them granularly opt into what they care about.

---

### Phase 2: Alert Engine (Business Logic)

Create `app/Services/Ews/` with these services:

| Service | Responsibility |
|---|---|
| **`EwsEvaluator`** | Runs on every new `Occurrence` / `NisSuggestion` / `IntroEventRecord` creation. Evaluates: (a) is this species new to this subregion? (b) how far is it from nearest known occurrence? (c) does the species have high-impact status? (d) is the report rate unusual? |
| **`AlertGenerator`** | Creates `Alert` records when threshold conditions are met. Deduplicates (same species + region within N days produces one alert). Attaches source record IDs. |
| **`AlertDistributor`** | Matches new alerts against all `AlertSubscription` records. Dispatches notification jobs per channel per user (batched to avoid queue floods). |

**Trigger points** — wire into existing `booted()` traits or observers on:
- `Occurrence::created`
- `NisSuggestion::created` (when `status === 'approved'`)
- `IntroEventRecord::created`

**Production note:** Keep evaluation synchronous (fast, SQL-only) and dispatch distribution to the existing database-backed queue.

---

### Phase 3: Notification Channels (beyond mail)

Currently only mail + database notifications exist. EWS needs more:

| Channel | Implementation |
|---|---|
| **Email** | Already works — `MailNotification` with queue. Reuse pattern. |
| **Database** | Already works — Laravel `notifications` table. Reuse. |
| **SMS** | New: `SmsChannel`. Use a provider like Twilio, or build on existing WhatsApp infra. |
| **WhatsApp outbound** | New: Extend `WhatsAppService` with `sendMessage($to, $body)` via GreenAPI. Currently only *validates* numbers. |
| **Push (browser)** | New: Via Laravel Echo + WebSockets (Pusher/reverb) for in-panel real-time alerts. Filament already has `databaseNotifications()` enabled. |

---

### Phase 4: EASIN Integration & External Data Sources

MAMIAS already fetches `Easin_id` via `EasinService`. Go further:

| Feature | Details |
|---|---|
| **EASIN webhook receiver** | Route `POST /api/ew/easin-alert` — accept EASIN alerts (formatted JSON), create `Alert` records, tag as `source: easin` |
| **EASIN data push** | Periodic `FetchEasinAlertsJob` — poll EASIN API for recent alerts |
| **GBIF/OCC integration** | Pull occurrence data from GBIF for species already in MAMIAS. Create `ExternalOccurrence` model or store in a separate table. |
| **RAS (Rapid Alert System)** | Add integration point for RAS notifications from European Commission. |

---

### Phase 5: API & Webhooks (Outbound)

For external consumers of MAMIAS EWS alerts:

| Endpoint | Purpose |
|---|---|
| `GET /api/ew/alerts` | Public alert feed (paginated, filterable by species/region/severity) |
| `POST /api/ew/webhooks` | Register webhook URLs per user per subscription |
| `GET /api/ew/subscriptions` | User's active subscriptions |

**Auth:** Laravel Sanctum tokens (no API auth currently exists).

---

### Phase 6: Filament UI (Panel)

| Page/Widget | Purpose |
|---|---|
| **`AlertResource`** | CRUD for super_admin/scientist to manage alerts. Table: species, region, severity, source, published_at. Filters by severity, region, date. |
| **`AlertSubscriptionResource`** | User-facing resource to manage their subscriptions |
| ****Active Alerts Widget** | Dashboard widget — map with active alert pins, severity badges, count |
| **Alert log** | View delivery status per channel per user |
| **Navigation badge** | Use the existing `RefreshSidebarPlugin` — dispatch `refresh-sidebar` when new alerts are created to update badge count |

---

### Phase 7: Public-Facing EWS Page

Extend existing public routes with:

| Route | Component |
|---|---|
| `GET /early-warning` | Livewire `PublicAlerts` — public alert list + map |
| `GET /early-warning/{alert}` | Detail page for a specific alert |

Uses existing spatial map entries / Leaflet integration.

---

### Priority & Sequencing Recommendation

| Priority | Phase | Effort | Why |
|---|---|---|---|
| **P0** | Phase 1 (Models + Subscriptions) | 2-3 days | Foundation for everything |
| **P0** | Phase 2 (Alert Engine) | 3-4 days | Core business logic |
| **P1** | Phase 6 (Filament UI) | 2-3 days | Admin visibility |
| **P1** | Phase 3 (SMS + WhatsApp outbound) | 2 days | Critical for rapid response |
| **P2** | Phase 4 (EASIN integration) | 2-3 days | External data enrichment |
| **P2** | Phase 7 (Public page) | 1-2 days | Stakeholder transparency |
| **P3** | Phase 5 (API/Webhooks) | 2 days | External consumers |

**Total estimate: ~14-20 days** for a complete EWS implementation, depending on how many notification channels are needed initially.

---

**Tool: question**

**Input:**
```json
{
  "questions": [
    {
      "question": "Which phases would you like to prioritize first?",
      "header": "EWS Priority",
      "options": [
        {
          "label": "Phase 1 + 2 (Core engine)",
          "description": "Models, subscriptions, alert generation logic — the foundation"
        },
        {
          "label": "Phase 1 + 6 (Models + Admin UI)",
          "description": "Data layer first, then Filament panel so admins can manage alerts manually"
        },
        {
          "label": "Everything P0 first",
          "description": "Phases 1, 2, 3, and 6 — core models + engine + SMS + admin UI"
        },
        {
          "label": "Full scope",
          "description": "All 7 phases in priority order"
        }
      ]
    },
    {
      "question": "For notification channels, which should we support initially?",
      "header": "Channels",
      "options": [
        {
          "label": "Email + Database only (Recommended)",
          "description": "Reuse existing infrastructure, zero new provider dependencies"
        },
        {
          "label": "Email + Database + WhatsApp",
          "description": "Extend WhatsAppService to send outbound messages via GreenAPI"
        },
        {
          "label": "All channels",
          "description": "Email, database, WhatsApp, SMS (Twilio), browser push"
        }
      ]
    },
    {
      "question": "Should EASIN integration be pull-based (polling) or push-based (webhook receiver)?",
      "header": "EASIN Integration",
      "options": [
        {
          "label": "Both (Recommended)",
          "description": "Poll EASIN periodically + accept incoming webhooks from their side"
        },
        {
          "label": "Poll only",
          "description": "Jobs fetch EASIN alerts on schedule"
        },
        {
          "label": "Webhook only",
          "description": "Only accept EASIN-pushed alerts"
        }
      ]
    }
  ]
}
```

**Output:**
```
User has answered your questions: "Which phases would you like to prioritize first?"="Unanswered", "For notification channels, which should we support initially?"="Unanswered", "Should EASIN integration be pull-based (polling) or push-based (webhook receiver)?"="Unanswered". You can now continue with the user's answers in mind.
```

---

## Assistant (Plan · DeepSeek V4 Flash Free · 3.7s)

_Thinking:_

The user didn't answer the questions. Let me ask them again more directly.

Would you like me to refine any part of this plan, or should I ask you the priority questions again to narrow scope before we proceed?

---

## User

can you check also the Earlt warning Systems for aquatic Invasions by GuardIAS tech

---

## Assistant (Plan · DeepSeek V4 Flash Free · 6.7s)

_Thinking:_

The user wants me to research "GuardIAS tech" and their Early Warning Systems for aquatic invasions. Let me search the web for information about GuardIAS and their EWS technology, and also look at what EASIN does, to compare and potentially integrate these approaches into the MAMIAS plan.

**Tool: websearch**

**Input:**
```json
{
  "query": "GuardIAS tech Early Warning System aquatic invasions",
  "numResults": 10
}
```

**Output:**
```
Title: Work Package 2
URL: https://guardias.eu/work-package-2/
Published: N/A
Author: N/A
Highlights:
## Work Package 2 - Improving Early Detection and Monitoring
...
* **D2.1 **Benefit-Cost Analysis and Technical Report on the use of innovative robotics, remote sensing, hydroacoustics, and eDNA for detecting, mapping, and monitoring aquatic IAS.
* **D2.2 **Compiled curated library and protocols of IAS-relevant molecular information
...
* **D2.3 **iNaturalist SEEK app AI identifying aquatic IAS using GuardIAS annotated image library
...
* **D2.4 **Early Warning System to help early detection of marine and freshwater IAS 
* **D2.5 **Workflow for detecting and prioritising emerging alien species based on species occurrence cubes 
**2026 Updates **
...
The GuardIAS iNaturalist project [Aquatic Invasive Alien Species Occurring in Europe](https://www.inaturalist.org/projects/aquatic-invasive-alien-species-occuring-in-europe?tab=species) aims to improve AI training by increasing the number of IAS images and enhance detection accuracy through expert validation of observations. The team also aims to expand the spatial and temporal scale of IAS monitoring across Europe through contributions from citizen naturalists and increase public awareness of IAS through community events and active engagement. By making IAS identification faster and more reliable, this approach will support early detection and long-term monitoring efforts. The project currently has 83 members and includes 251 invasive species. More than 100 experts have been contacted directly to contribute by providing reference material and by identifying or confirming observations, particularly for species not yet recognised by iNaturalist’s integrated AI system. Finally, the iNaturalist observation data are being used to analyse IAS distribution patterns and compare them with records from other monitoring platforms.
...
GuardIAS software development and ecology experts are also developing an early alert tool – a technical component of an early warning system that detects and signals risks by generating alerts. The [GBIF Alert system](https://alert.riparias.be/), originally developed within the [LIFE RIPARIAS](https://www.riparias.be/nl) project, harvests and visualises GBIF data and allows users to configure email notifications for new records of alien species within areas of interest. GuardIAS will further develop and upscale this tool to the European level in collaboration with [EASIN ](https://easin.jrc.ec.europa.eu/easin)and [OneSTOP](https://www.onestop-project.eu/).

---

Title: The GuardIAS Project
URL: https://guardias.eu/
Published: N/A
Author: N/A
Highlights:
GuardIAS focuses on aquatic invasive alien species, those found in both freshwater and marine environments. The project aims to improve the detection and prevent the spread of invasive alien species in both marine and freshwater habitats using a range of novel methods — such as artificial intelligence (AI), environmental DNA, satellite imagery, underwater robotics and monitoring systems, and the development of an early alert system.
...
GuardIAS aims to work against the threats posed by aquatic invasive alien species through using new techniques to target all stages of invasive alien species ‘invasion’.
...
, there is a
...
GuardIAS aims to work against the threats posed by aquatic invasive alien species through using new techniques to target all stages of invasive alien species ‘invasion’.
...
There are 7 Work Packages (WP) focusing on different stages of invasive alien species ‘invasion’ in the GuardIAS project. Each Work Package is a distinct project area, but all are interlinked through GuardIAS as a whole.

 

Work Package 1
...
##### IMPROVING EARLY DETECTION AND MONITORING

Work Package 2 will develop methods and new technology for cost-effective early detection and monitoring of invasive alien species. This will include...

READ MORE
...
itising INVASIVE ALIEN SPECIES threats
...
Work Package 3 will develop ways for artificial intelligence to be used to identify and prioritise the major threats posed by aquatic invasive alien species on..

READ MORE
...
## Our sister project OneSTOP supports the EU IAS
...
by pioneering a joined
...
up approach to minimise the introduction, establishment, spread and impact of terrestrial

---

Title: Projects
URL: https://guardias.eu/projects/
Published: N/A
Author: N/A
Highlights:
* [WP2: Improving Early Detection &amp; Monitoring
...
work-package-2/)
...
There are 7 Work Packages (WP) focusing on different stages of invasive alien species ‘invasion’ in the GuardIAS project. Each Work Package is a distinct project area, but all are interlinked through GuardIAS as a whole.
...
##### IMPROVING EARLY DETECTION AND MONITORING
...
Work Package 2 will develop methods and new technology for cost-effective early detection and monitoring of invasive alien species. This will include...
...
MORE](https://guardias.eu/work-package-2/)
...
##### Prioritising INVASIVE ALIEN SPECIES threats
...
Work Package 3 will develop ways for artificial intelligence to be used to identify and prioritise the major threats posed by aquatic invasive alien species on..
[
...
MORE](https://
...
ias.eu/?page_id=806)
...
GuardIAS will set up an early warning mechanism in the SE Aegean Sea – the likely entry point from Turkey – by engaging fishers, divers, and NGOs. Upon detection, coordinated eradication efforts will be initiated, with Prof Stelios. Katsanevakis, member of the Greek National IAS Committee, ensuring effective coordination with competent authorities.
...
and Malta.
...
GuardIAS will implement an early warning system in Greece, involving divers and NGOs, and conduct regular eDNA monitoring at potential entry points such as ports and marinas. Upon detection, coordinated eradication efforts will be initiated with Professor Stelios. Katsanevakis, member of the Greek National IAS Committee, ensuring effective coordination with competent authorities.

---

Title: Early Alert Tool &  Workflows for Detecting and Prioritising IAS - Speaker Deck
URL: https://speakerdeck.com/damianooldoni/early-alert-tool-and-workflows-for-detecting-and-prioritising-ias
Published: 2026-05-11T13:54:51.000Z
Author: N/A
Highlights:
### Early Alert Tool & Workflows for Detecting and Prioritising Emerging
...
### Early Alert
...
### Early Alert Tool: technical requirements document Technical Requirement Document is
...
ready 🎉 Based on GuardIAS workshops (+ OneSTOP). Feedback is welcome 🔍 bit.ly/alert-specs
...
### Deliverable 2.5 Workflow for detecting and prioritising emerging alien species
...
based on species occurrence cubes almost ready. Species occurrence cubes - Aggregate GBIF occurrence data along spatial, temporal and taxonomic dimensions to create “data cubes”. - Example: calculate the #occurrences per grid cell, year, species - Concept from TrIAS project (Oldoni D & al 2020): done in R from raw occurrences. - Now a GBIF download service, with support from EU-funded B-Cubed project. - Learn more:documentation, blogpost, tutorial and webinar, tech specs. https://www.gbif.org/occurrence-cubes
...
### Deliverable 2.5 Workflow for detecting and prioritising emerging alien species
...
Cubes used in TrIAS as data inputs to detect and prioritise emerging alien species (GRIIS Belgium) trias-project.github.io/indicators/
...
### Deliverable 2.5 Open science approach: apply the TrIAS workflow to
...
marine species (LME) - Calculate #occs and #grid cells (km2) ~ year ➡ modelling ➡ emerging score at year level - Automatise the workflow: weekly https://guardias-eu.github.io/indicators
...
### Deliverable 2.5 Open science approach: apply the TrIAS workflow to
...
marine species (LME) - Calculate a total emerging score and rank species by it guardias-eu.github.io/indicators/
...
### Deliverable 2.5 Open science approach: apply the TrIAS workflow to
...
marine species (LME) - Appearing species guardias-eu.github.io/indicators/
...
### Deliverable 2.5 Open science approach: apply the TrIAS workflow to
...
marine species (LME) - Reappearing species guardias-eu.github.io/indicators/
...
### Deliverable 2.5 Future improvements - Taxonomic scope: include terrestrial en
...
freshwater species - Spatial scope: include EU countries - Research effort bias correction: add class level info (class level occurrence cubes), as in TrIAS guardias-eu.github.io/indicators/

---

Title: Work Package 4
URL: https://guardias.eu/work-package-4/
Published: N/A
Author: N/A
Highlights:
## Work Package 4 - Management Options For Eradication And Control
...
Work Package 4 is all about action—eradicating, containing, and controlling priority invasive species. Once we know where and on which species to act—based on insights from impact studies, risk assessments, and modelling—the key question becomes: what management measures are actually feasible?
...
* **D4.1 **Management feasibility assessment based on open dataset with management interventions for aquatic IAS
...
* **D4.2 **Guidance to contingency planning and setting up early warning systems (with WP2) for marine and freshwater IAS
...
To launch a European community of practice, the team will host three key stakeholder workshops in 2026, starting with invasive freshwater plants in Ghent, Belgium (18–19 May), followed by crayfish in Florence, Italy (25–26 May), and eDNA in Belfast, Northern Ireland (23-27 August).
...
We are also analysing existing freshwater and marine plans to help authorities draft tailored response frameworks, while ongoing data collection populates an open database for our decision-support tools.
...
Without a doubt, the most significant development is the European early warning tool we’re building in collaboration with the EASIN team and our sister project, OneSTOP. This tool is designed to ensure that new records of invasive species are swiftly directed to the appropriate authority and field managers. It builds on an open-source platform developed at our institute for the LIFE RIPARIAS project, and we’re hopeful it can be fully integrated into the EASIN ecosystem. Besides this, working with the smart modellers of Belinda Gallardo’s WP3 team who work on species prioritisation and Jonathan Jeschke’s team who are strong on social-ecological interactions will be great. Such networks are novel tools that can help us solve conflict in invasive species management.
...
“For over 35 years, since I first began studying non-indigenous species, I have dreamed of tools that could reveal the ‘Big Picture’ of the transfer, spread, and impacts of invasive aquatic species. Thanks to advances in new technologies, the GuardIAS project now offers an outstanding opportunity to make that vision a reality.”
...
He coordinates research on invasive species and is very active in international projects focused on sustainable control, surveillance and early warning systems, citizen science, horizon scanning and risk assessment, impact research and human dimensions.

---

Title: GuardIAS – IECS Ltd
URL: https://www.iecs.ltd/projects/guardias/
Published: N/A
Author: N/A
Highlights:
GuardIAS – IECS Ltd

guardias.eu

- Media

#### Funded by

## Guarding European Waters from IAS

GuardIAS is a three-year Horizon Europe project dedicated to protecting Europe’s freshwater and marine ecosystems from the growing threat of aquatic invasive alien species (IAS). These species, introduced either accidentally or deliberately, can cause serious harm to native biodiversity, ecosystem functioning and socio-economic activities.

The project takes a comprehensive approach to IAS management, targeting all stages of invasion: prevention, early detection, prioritisation, control and public engagement. It combines cutting-edge technologies such as artificial intelligence, environmental DNA (eDNA), satellite imagery, underwater robotics and real-time monitoring systems to develop a robust early alert system for aquatic IAS.

GuardIAS is structured around seven interlinked Work Packages, each addressing a specific phase of the invasion timeline. These include enhancing prevention strategies, improving detection and monitoring, prioritising threats through AI modelling, and developing decision-support tools for eradication and control. A strong emphasis is placed on citizen science and stakeholder engagement, using STEAM approaches and platforms like iNaturalist to expand data collection and public awareness.

The project’s Learning Sites span diverse European regions and ecosystems, enabling the testing and demonstration of scalable solutions. A Caribbean site has also been included to explore global comparability and resilience to extreme events under the UN Sustainable Development Goals framework.

By integrating science, technology and community action, GuardIAS aims to strengthen Europe’s capacity to respond to biological invasions, preserve biodiversity and support sustainable ecosystem management across freshwater and marine environments.

Manage consent

---

Title: The GuardIAS Project
URL: https://guardias.eu/home/
Published: N/A
Author: N/A
Highlights:
GuardIAS focuses on aquatic invasive alien species, those found in both freshwater and marine environments. The project aims to improve the detection and prevent the spread of invasive alien species in both marine and freshwater habitats using a range of novel methods — such as artificial intelligence (AI), environmental DNA, satellite imagery, underwater robotics and monitoring systems, and the development of an early alert system.
...
GuardIAS aims to work against the threats posed by aquatic invasive alien species through using new techniques to target all stages of invasive alien species ‘invasion’.
...
GuardIAS focuses on aquatic IAS, there is a project running parallel that focuses on terrestrial IAS (One Stop).
...
GuardIAS aims to work against the threats posed by aquatic invasive alien species through using new techniques to target all stages of invasive alien species ‘invasion’.
...
GuardIAS focuses on aquatic IAS, there is a project running parallel that focuses on terrestrial IAS (One Stop).
...
There are 7 Work Packages (WP) focusing on different stages of invasive alien species ‘invasion’ in the GuardIAS project. Each Work Package is a distinct project area, but all are interlinked through GuardIAS as a whole.
...
current invasive alien species
...
##### IMPROVING EARLY DETECTION AND MONITORING
...
Work Package 2 will develop methods and new technology for cost-effective early detection and monitoring of invasive alien species. This will include...
...
Work Package 3 will develop ways for artificial intelligence to be used to identify and prioritise the major threats posed by aquatic invasive alien species on..
...
## Our sister project OneSTOP supports the EU IAS Regulation by pioneering a joined-up approach to minimise the introduction, establishment, spread and impact of terrestrial invasive alien species

---

Title: Work Package 3
URL: https://guardias.eu/work-package-3/
Published: N/A
Author: N/A
Highlights:
## Work Package 3 - Prioritising Invasive Alien Species Threats
...
Invasive species often sound like an inevitable consequence of globalization, impossible to tackle effectively. In Work Package 3 we will use available information on the traits, impacts, current and likely future spread of invaders, to identify, quantify and prioritise major threats to native species and sites of conservation concern. This will allow optimising our management resources on the sites and species with highest probability of success, contributing to protecting biodiversity.
...
Work Package 3 will develop reproducible workflows to identify, quantify and prioritise major threats posed by aquatic IAS on threatened species and sites of conservation interest. This objective involves identifying priority invasive species and native species under threat, modelling the likely spread of priority species under climate change scenarios, assess the cumulative risks posed
...
The methodological approach in Work Package 3 unfolds across four interrelated steps. First, we will identify priority aquatic IAS and the Red Listed Species most vulnerable to them.
...
Second, we will use machine-learning algorithms to predict the current and future distribution of priority IAS under different climate scenarios. In addition, we wil combine these projections with Red List distribution data to pinpoint future hotspots where IAS impacts on biodiversity are likely to be most severe. Cross-comparisons between native and invasive ranges will allow us to map areas of highest cumulative risk.
...
Third, we will integrate species traits, distributions, and impact data into CIMPAL, a standardised tool to quantify the cumulative ecological risks of IAS across Europe.
...
Finally, we will use spatial prioritisation tools to identify where and which IAS management actions should be targeted, optimising the allocation of limited resources. We will also design a cost-effective monitoring network to enhance early detection in high-risk areas.
...
OneSTOP,
...
new standard for
...
anticipate and address biological
...
will provide the
...
we still lack
...
Our work aims to fill that gap and generate lasting tools
...
strategic planning and risk reduction
...
“*Aquatic ecosystems are highly vulnerable to biological invasions. By integrating predictive models, cumulative impact assessments, and spatial prioritisation, WP3 provides the tools needed to mitigate their impacts on the species and ecosystems most at risk*.
...
*Our freshwater and marine ecosystems are under silent threat from invasive species. With smart planning and targeted action, we can defend Europe’s native aquatic biodiversity before it’s too late.”*

---

Title: Guarding European Waters from IAS (GuardIAS)
      -  Research Institute for Nature and Forest
URL: https://pureportal.inbo.be/en/projects/guarding-european-waters-from-ias-guardias/
Published: N/A
Author: N/A
Highlights:
, innovative techniques, and information systems within a transdisciplinary framework to provide effective, technologically feasible, and socially acceptable solutions. We will apply Artificial Intelligence (AI) and new data workflows to systematically query multiple biodiversity databases for speciesdistribution, environmental tolerances, biological traits, and genetic information, enhancing EASIN and harmonizing key datasets. This will improve IAS impact profiling and effective invasion prevention andmanagement. The world-leading citizen science (CS) platform iNaturalist (using AI to identify species from smartphone photos) will be enhanced with expert-verified images of aquatic IAS to aid early detection at borders and track the geographic spread of IAS. Among the most important pathways for aquatic IAS introduction and further spread are the discharge of ballastwater, hull biofouling, and the movement of recreational boats. While the introduction of IAS through ballast water is expected to be effectively tackled by the IMO Ballast Water Management Convention (BWMC),biofouling remains a primary global pathway for IAS introductions,since it is regulated only by voluntary IMO guidelines. For this reason, GuardIAS focuses on innovative prevention of new introductions through hull biofouling by exploring emerging nanotechnology-based antifouling coatings. GuardIAS will also investigate the movement patterns of recreational boats along European coastlines, a significant but understudied factor in IAS secondary dispersal. An eDNA reference library and assay panel will be developed to aid effective IAS detection. Macroecologicaland biogeographic trait analysis and cutting-edge models (e.g. the Nobel-Prize-winning MRIO - Multi-Region Input-Output analysis) will assess the risks of IAS introduction and spread, including risks to threatened species,critical habitats, and areas of conservation importance at present and under future invasion scenarios. Systematic conservation planning tools will be used for the first time to prioritize IAS monitoring and management actionsbased on their impacts on Red List species and under invasion scenarios. GuardIAS will boost data collection, monitoring, early detection, and IAS literacy through innovative Citizen Science initiatives, including BioArtBlitz events (a novel type of event where figurative and performative arts will be used as a vehicle for communication and engagement), eDNA sampling, sound analysis in a Zooniverse project, and marina events for boaters. We will further engage stakeholders (policy makers, industry, scientists, aquatic managers and control officers, the public, youth, and educational institutions) through innovative tools, such as applied games.

---

Title: Guarding European Waters from IAS (GuardIAS) | Instituut voor Natuur- en Bosonderzoek
URL: https://www.vlaanderen.be/inbo/en-GB/projects/guarding-european-waters-from-ias-guardias-evinbo
Published: N/A
Author: N/A
Highlights:
IAS builds on the best practises in global biosecurity and combines cutting-edge science, new technologies, innovative techniques, and information systems within a transdisciplinary framework to provide effective, technologically feasible, and socially acceptable solutions. We will apply Artificial Intelligence (AI) and new data workflows to systematically query multiple biodiversity databases for speciesdistribution, environmental tolerances, biological traits, and genetic information, enhancing EASIN and harmonizing key datasets. This will improve IAS impact profiling and effective invasion prevention andmanagement. The world-leading citizen science (CS) platform iNaturalist (using AI to identify species from smartphone photos) will be enhanced with expert-verified images of aquatic IAS to aid early detection at borders and track the geographic spread of IAS. Among the most important pathways for aquatic IAS introduction and further spread are the discharge of ballastwater, hull biofouling, and the movement of recreational boats. While the introduction of IAS through ballast water is expected to be effectively tackled by the IMO Ballast Water Management Convention (BWMC),biofouling remains a primary global pathway for IAS introductions,since it is regulated only by voluntary IMO guidelines. For this reason, GuardIAS focuses on innovative prevention of new introductions through hull biofouling by exploring emerging nanotechnology-based antifouling coatings. GuardIAS will also investigate the movement patterns of recreational boats along European coastlines, a significant but understudied factor in IAS secondary dispersal. An eDNA reference library and assay panel will be developed to aid effective IAS detection. Macroecologicaland biogeographic trait analysis and cutting-edge models (e.g. the Nobel-Prize-winning MRIO - Multi-Region Input-Output analysis) will assess the risks of IAS introduction and spread, including risks to threatened species,critical habitats, and areas of conservation importance at present and under future invasion scenarios. Systematic conservation planning tools will be used for the first time to prioritize IAS monitoring and management actionsbased on their impacts on Red List species and under invasion scenarios. GuardIAS will boost data collection, monitoring, early detection, and IAS literacy through innovative Citizen Science initiatives, including BioArtBlitz events (a novel type of event where figurative and performative arts will be used as a vehicle for communication and engagement), eDNA sampling, sound analysis in a Zooniverse project, and marina events for boaters. We will further engage stakeholders (policy makers, industry, scientists, aquatic managers and control officers,
```

**Tool: websearch**

**Input:**
```json
{
  "query": "GuardIAS aquatic invasive species early warning technology 2025 2026",
  "numResults": 10
}
```

**Output:**
```
Title: Work Package 2
URL: https://guardias.eu/work-package-2/
Published: N/A
Author: N/A
Highlights:
- Improving Early Detection and Monitoring
...
* **D2.1 **Benefit-Cost Analysis and Technical Report on the use of innovative robotics, remote sensing, hydroacoustics, and eDNA for detecting, mapping, and monitoring aquatic IAS.
...
* **D2.3 **
...
aquatic IAS using GuardIAS
...
* **D2.4 **Early Warning System to help early detection of marine and freshwater IAS 
* **D2.5 **Workflow for detecting and prioritising emerging alien species based on species occurrence cubes 
**2026 Updates **
...
Satellite imagery is being used to conduct a time series analysis of marine IAS throughout 2026. The bioacoustics team is expanding research with new freshwater species recordings and lionfish vocalisations, while BRUV (Baited Remote Underwater Video) systems equipped with hydrophones are being tested. A GuardIAS Sound Library is being developed in collaboration with [VLIZ](https://www.vliz.be/en/what-we-do/data-information/data).
...
The GuardIAS iNaturalist project [Aquatic Invasive Alien Species Occurring in Europe](https://www.inaturalist.org/projects/aquatic-invasive-alien-species-occuring-in-europe?tab=species) aims to improve AI training by increasing the number of IAS images and enhance detection accuracy through expert validation of observations. The team also aims to expand the spatial and temporal scale of IAS monitoring across Europe through contributions from citizen naturalists and increase public awareness of IAS through community events and active engagement. By making IAS identification faster and more reliable, this approach will support early detection and long-term monitoring efforts. The project currently has 83 members and includes 251 invasive species. More than 100 experts have been contacted directly to contribute by providing reference material and by identifying or confirming observations, particularly for species not yet recognised by iNaturalist’s integrated AI system. Finally, the iNaturalist observation data are being used to analyse IAS distribution patterns and compare them with records from other monitoring platforms.
...
GuardIAS software development and ecology experts are also developing an early alert tool – a technical component of an early warning system that detects and signals risks by generating alerts. The [GBIF Alert system](https://alert.riparias.be/), originally developed within the [LIFE RIPARIAS](https://www.riparias.be/nl) project, harvests and visualises GBIF data and allows users to configure email notifications for new records of alien species within areas of interest. GuardIAS will further develop and upscale this tool to the European level in collaboration with [EASIN ](https://easin.jrc.ec.europa.eu/easin)and [OneSTOP](https://www.onestop-project.eu/).

---

Title: N/A
URL: https://guardias.eu/wp-content/uploads/2026/02/GuardIAS-Newsletter-Issue-1-2026.pdf
Published: N/A
Author: N/A
Highlights:
Across Europe’s rivers, lakes, and seas, invasive alien species are reshaping ecosystems faster than we can respond - GuardIAS aims to help change that. By combining artificial intelligence, environmental DNA, robotics, applied gaming, advanced modelling, and hands-on citizen science, GuardIAS is developing practical, science-based tools to prevent, detect, and manage aquatic invasions at every stage of the invasion pathway. From early warning systems and smarter monitoring to real-world eradication trials and community engagement, our work bridges research, policy, and society to help safeguard Europe’s waters for the future.
...
Case study 11: Rugulopteryx okamurae The first pom-pom sampling campaign has been completed across the Ionian sea (Greece) to track the spread of the invasive alga Rugulopteryx okamurae. This milestone is essential for establishing an early warning system at key entry points like ports and marinas. Read the full update here
...
MOOC 
The first MOOC,"IAS Prevention," launched in October 2025 and has already 
attracted 169 participants from
...
, including students, 
researchers
...
and public agency
...
team will release
...
second 
MOOC
...
on early detection and monitoring
...
early spring
...
026
...
Read the
...
How GuardIAS Intelligence Analyst System (G.I.A.S.) is Revolutionising Data Gathering for Invasive Species Significant progress has been made on the G.I.A.S. platform, which is now moving through its 2026 roadmap starting with initial beta testing this month. This AI-assisted system simplifies the time-consuming process of data aggregation, allowing researchers to focus on analysis rather than manual data reconciliation. Discover how it works
...
This work package leverages AI to predict invasion risks and develops non-toxic coatings to stop species from spreading via ship hulls. In 2026, the team will launch the beta version of the G.I.A.S. platform to automate ecological data gathering and complete the integration of global datasets from EASIN, AquaNIS, and WRiMS. Explore WP1 updates
...
Work Package 2 focuses on using innovative technology and citizen science to track invasive species across Europe. Key 2026 activities include satellite-based time-series analysis of marine invaders, the development of a GuardIAS Sound Library, and upscaling the GBIF Alert system to a European level for early risk detection. Explore WP2 updates
...
GuardIAS is a three-year Horizon Europe project focused on detecting
...
preventing the spread of aquatic invasive species.
...
Using novel methods like AI, eDNA, robotics, and satellite imagery, the project aims to build an early alert system, increase public awareness, and help decision-makers prioritize action.

---

Title: The GuardIAS Project
URL: https://guardias.eu/
Published: N/A
Author: N/A
Highlights:
GuardIAS is a three-year Horizon Europe project that started on January 1, 2025, which brings together a wide range of stakeholders and experts in Invasive Alien Species (IAS) management.
...
GuardIAS focuses on aquatic invasive alien species, those found in both freshwater and marine environments. The project aims to improve the detection and prevent the spread of invasive alien species in both marine and freshwater habitats using a range of novel methods — such as artificial intelligence (AI), environmental DNA, satellite imagery, underwater robotics and monitoring systems, and the development of an early alert system.
...
Information gained from the research will be used to increase public awareness regarding threats from invasive species spread, enhance public engagement, and enable decision makers to prioritise where and when to act.

 

WORK PACKAGES
...
GuardIAS aims to work against the threats posed by aquatic invasive alien species through using new techniques to target all stages of invasive alien species ‘invasion’.
...
GuardIAS aims to work against the threats posed by aquatic invasive alien species through using new techniques to target all stages of invasive alien species ‘invasion’.
...
There are 7 Work Packages (WP)
...
stages of invasive alien species ‘invasion’ in the GuardIAS project. Each Work Package is a distinct project area, but all are
...
through GuardIAS as a whole.
...
##### IMPROVING EARLY DETECTION AND MONITORING

Work Package 2 will develop methods and new technology for cost-effective early detection and monitoring of invasive alien species. This will include...

READ MORE
...
##### Prioritising INVASIVE ALIEN SPECIES threats
...
Work Package 3 will develop ways for artificial intelligence to be used to identify and prioritise the major threats posed by aquatic invasive alien species on..

READ MORE

---

Title: GuardIAS - Guarding European Waters from IAS | GuardIAS | Project | Fact Sheet | HORIZON | CORDIS | European Commission
URL: https://cordis.europa.eu/project/id/101181413
Published: 2026-04-24T14:07:11.000Z
Author: N/A
Highlights:
The GuardIAS project is an ambitious initiative aimed at transforming aquatic environment governance and management in Europe. Its primary goal is to align with the EU Biodiversity Strategy for 2030 and the IAS Regulation, focusing on mitigating the impacts of invasive alien species (IAS) on biodiversity, ecosystem services, and human health. GuardIAS plans to utilize advanced science, technology, and artificial intelligence to develop innovative tools and methodologies. These tools will be instrumental in preventing, detecting, eradicating, and managing IAS.The project is set to advance through interdisciplinary collaboration, integrating data from various sources to create comprehensive IAS impact profiles and management strategies. A significant aspect of GuardIAS is stakeholder engagement and societal involvement. This will be achieved through Citizen Science initiatives and innovative engagement strategies (such as serious games and BioArtBlitz events), aiming to involve the wider community in addressing IAS challenges.GuardIAS will also establish robust solutions for IAS management at multiple governance levels, from local to international. The project will include the development of beyond-the-state-of-the-art technologies and methodologies, such as nano-coatings for marine vessels to prevent IAS spread and eDNA approaches for efficient detection. Additionally, GuardIAS will conduct macroecological and biogeographic analyses to assess IAS risks under various future scenarios, including climate change.The project's comprehensive approach, encompassing science, technology, and community engagement, positions it as a key player in the EU's efforts to preserve biodiversity and protect ecosystems from the growing threat of invasive species. GuardIAS is not only a response to current environmental challenges but also a proactive step towards sustainable management and conservation of aquatic environments in Europe.
...
Last update: 24 April 202

---

Title: Projects
URL: https://guardias.eu/projects/
Published: N/A
Author: N/A
Highlights:
* [WP2: Improving Early Detection &amp; Monitoring
...
](https://guardias.eu/work-package-2/)
...
There are 7 Work Packages (WP) focusing on different stages of invasive alien species ‘invasion’ in the GuardIAS project. Each Work Package is a distinct project area, but all are interlinked through GuardIAS as a whole.
...
##### IMPROVING EARLY DETECTION AND MONITORING
...
Work Package 2 will develop methods and new technology for cost-effective early detection and monitoring of invasive alien species. This will include...
...
[READ MORE](https://guardias.eu/work-package-2/)
...
##### Prioritising INVASIVE ALIEN SPECIES threats
...
Work Package 3 will develop ways for artificial intelligence to be used to identify and prioritise the major threats posed by aquatic invasive alien species on..
[READ MORE](https://guardias.eu/?page_id=806)
...
* take action on pathways of unintentional introduction (i.e. prevention)
* take measures for the early detection and rapid eradication of these species
* manage species that are already widely spread in their territory
...
GuardIAS plans to enhance this approach by engaging local communities in citizen science activities to maximise the impacts of the projects, testing the use of static underwater cameras in northern-central Italy to detect the species in protected areas
...
control efforts and success of
...
can be applied
...
sites for management based on biodiversity
...
GuardIAS will set up an early warning mechanism in the SE Aegean Sea – the likely entry point from Turkey – by engaging fishers, divers, and NGOs. Upon detection, coordinated eradication efforts will be initiated, with Prof Stelios. Katsanevakis, member of the Greek National IAS Committee, ensuring effective coordination with competent authorities.
...
, Cyprus, and Malta.
...
GuardIAS will implement an early warning system in Greece, involving divers and NGOs, and conduct regular eDNA monitoring at potential entry points such as ports and marinas. Upon detection, coordinated eradication efforts will be initiated with Professor Stelios. Katsanevakis, member of the Greek National IAS Committee, ensuring effective coordination with competent authorities.

---

Title: Early Alert Tool &  Workflows for Detecting and Prioritising IAS - Speaker Deck
URL: https://speakerdeck.com/damianooldoni/early-alert-tool-and-workflows-for-detecting-and-prioritising-ias
Published: 2026-05-11T13:54:51.000Z
Author: N/A
Highlights:
# Early Alert Tool & Workflows for Detecting and Prioritising IAS
...
Slides of the presentation given during the online General Assembly of the GuardIAS project.
...
### Early Alert Tool & Workflows for Detecting and Prioritising Emerging
...
IAS Damiano Oldoni (INBO) 11-05-2026 https://bit.ly/emergingIAS
...
### Early Alert Tool: new features Homepage. Better, nicer user interface:
...
### Early Alert Tool: new features Homepage. Filter on time: -
...
### Early Alert Tool: new features Hompeage. Filter on verification status
...
### Early Alert Tool: new features Hompeage. Basic summary statistics added:
...
### Early Alert Tool:
...
page. Nic
...
### Early Alert Tool:
...
### Early Alert Tool: technical requirements document Technical Requirement Document is
...
ready 🎉 Based on GuardIAS workshops (+ OneSTOP). Feedback is welcome 🔍 bit.ly/alert-specs
...
### Deliverable 2.5 Workflow for detecting and prioritising emerging alien species
...
based on species occurrence cubes almost ready. Species occurrence cubes - Aggregate GBIF occurrence data along spatial, temporal and taxonomic dimensions to create “data cubes”. - Example: calculate the #occurrences per grid cell, year, species - Concept from TrIAS project (Oldoni D & al 2020): done in R from raw occurrences. - Now a GBIF download service, with support from EU-funded B-Cubed project. - Learn more:documentation, blogpost, tutorial and webinar, tech specs. https://www.gbif.org/occurrence-cubes
...
### Deliverable 2.5 Workflow for detecting and prioritising emerging alien species
...
Cubes used in TrIAS as data inputs to detect and prioritise emerging alien species (GRIIS Belgium) trias-project.github.io/indicators/
...
### Deliverable 2.5 Open science approach: apply the TrIAS workflow to
...
marine species (LME) - Calculate #occs and #grid cells (km2) ~ year ➡ modelling ➡ emerging score at year level - Automatise the workflow: weekly https://guardias-eu.github.io/indicators
...
### Deliverable 2.5 Open science approach: apply the TrIAS workflow to
...
marine species (LME) - Calculate a total emerging score and rank species by it guardias-eu.github.io/indicators/
...
### Deliverable 2.5 Open science approach: apply the TrIAS workflow to
...
marine species (LME) - Appearing species guardias-eu.github.io/indicators/
...
### Deliverable 2.5 Open science approach: apply the TrIAS workflow to
...
marine species (LME) - Reappearing species guardias-eu.github.io/indicators/
...
### Deliverable 2.5 Future improvements - Taxonomic scope: include terrestrial en
...
freshwater species - Spatial scope: include EU countries - Research effort bias correction: add class level info (class level occurrence cubes), as in TrIAS guardias-eu.github.io/indicators/

---

Title: News from the GuardIAS project | Instituut voor Natuur- en Bosonderzoek
URL: https://www.vlaanderen.be/inbo/en-gb/news-march-2026/news-from-the-guardias-project/
Published: N/A
Author: N/A
Highlights:
News from the GuardIAS project | Instituut voor Natuur- en Bosonderzoek 

 

 

### Protected Nature

### Water

### Soil & air

### Forest

### Wildlife Management

### Climate

### Invasive species

### Agriculture

### Nature & Society

### Data & Infrastructure

### All indicators

### Protected Nature

### Water

### Soil & air

### Forest

### Wildlife Management

### Climate

### Invasive species

### Agriculture

### Nature & Society

### Data & Infrastructure

# News March 2026

 

 

## News from the GuardIAS project

Watercourses, wetlands and marine ecosystems are affected by invasive species more than other systems. Just think of the rapid advance of round gobies, American crayfish, overbite clam, North Asian weatherfish and zebra mussels. Some of these are ecosystem engineers that change ecosystems faster than we can respond. Once they have established themselves, they are difficult to get rid of.

The EU Horizon project GuardIAS aims to reduce the effects on endangered native species and nature reserves. It wants to provide practical solutions for the management of aquatic invasive species. To this end, it will test innovative technologies, artificial intelligence, eDNA and advanced modelling techniques for better prioritisation, detection and control. The project, which started in January 2025 (report), has now been running for a year. Time for a first assessment.

The very first GuardIAS newsletter provides an overview of the project's activities and what is still to come. You can read more about the eleven case studies on management, three of which are led by INBO: marbled crayfish, bullfrog and African clawed frog. You will also get a sneak preview of the GuardIAS Intelligence Analyst System (GIAS) tool, a specific AI for invasive species. GuardIAS also organises training courses, a summer school in Norway on eDNA, and various citizen science activities, including an iNaturalist survey to improve image recognition of Union List species.

Damiano Oldoni, Fleur Petersen, Diederik Strubbe, Teun Everts, Rein Brys en Tim Adriaens

Image above: American crayfish (photo INBO)

0">

#### Warning

- {{validation.errorMessage}}

 

## Newsletter

Subscribe to our English newsletter(s) here

---

Title: 17/02/2026
URL: https://guardias.eu/2026/02/17/
Published: 2026-02-17T00:00:00.000Z
Author: N/A
Highlights:
17/02/2026

## GuardIAS Newsletter #1: Turning Knowledge into Action Against Aquatic Invasions

Across Europe’s rivers, lakes, and seas, invasive alien species are reshaping ecosystems faster than we can respond – GuardIAS aims to help change that. By combining artificial intelligence, environmental DNA, […]

---

Title: A Year of Action: GuardIAS and OneSTOP’s First Year Tackling Invasive Alien Species Across Europe
URL: https://guardias.eu/a-year-of-action-guardias-and-onestops-first-year-tackling-invasive-alien-species-across-europe/
Published: 2026-03-26T23:59:13.000Z
Author: N/A
Highlights:
A Year of Action: GuardIAS and OneSTOP’s First Year Tackling Invasive Alien Species Across Europe

### Protected: Welcome to the Second GuardIAS Conference!

15/06/2026

### GuardIAS Conference Workshops

10/06/2026

### Second GuardIAS Conference Proceedings

10/06/2026

### Submitting an Abstract & Presenting at GuardIAS Conference November 19th and 20th

10/06/2026

### Recording: Leading the global response to biological invasions: A dialogue between OneSTOP & GuardIAS

25/05/2026

### New dates scheduled for the citizen science marina events in Italy

06/05/2026

---

Title: News
URL: https://www.onestop-project.eu/news/year-action-guardias-and-onestops-first-year-tackling-invasive-alien-species-across-europe
Published: N/A
Author: N/A
Highlights:
A Year of Action: GuardIAS and
...
STOP’s First Year Tackling Invasive Alien Species Across Europe
...
Invasive alien species (IAS) don't respect borders. They move through water, air, soil, and supply chains — and they're reshaping European ecosystems faster than current technologies can track. In early 2025, two Horizon Europe projects were launched to tackle the threat of invasive alien species: GuardIAS, focusing on aquatic IAS in freshwater and marine environments, and OneSTOP, targeting terrestrial IAS. Both projects use advanced scientific research, cutting-edge technology, and artificial intelligence to develop new tools and methods that will prevent, detect, prioritise, eradicate, and manage invasive alien species — covering all steps of the biological invasion process. In addition, public engagement is a significant aspect of both projects, taking form in five Living Labs in OneSTOP and eleven case studies in GuardIAS.
...
GuardIAS works for the prevention, detection, control and dissemination of underwater IAS
...
GuardIAS is using AI to predict species introductions and invasiveness by analysing biological traits, distributions, and environmental tolerances drawn from global datasets. Risk modelling identifies high-risk pathways and at-risk locations, accounting for the influence of global trade and climate change. In addition, GuardIAS is exploring nature-inspired, non-toxic coatings to reduce biofouling on ships — one of the primary vectors for aquatic species spread. Together, these tools are designed to give decision-makers earlier, smarter options for prevention.
...
included the first
...
sampling campaign for Rug
...
e, a
...
native to the northwestern
...
lines as a
...
. In the Netherlands, marbled crayfish are being tracked to
...
their spread and inform
...
north, pink
...
detection is scaling up in Northern Norway as this Pacific invader spreads into new European
...
. Another case study is tracking the macroalga, Undaria
...
atifida (
...
ame), while work
...
species to monitor.
...
In parallel, four of the GuardIAS
...
Italy, red
...
methods are being
...
Both projects have been working together on developing an Early Alert System for new IAS introductions, which will ensure that what's detected in the field travels quickly to the people who need to act on it. In addition, OneSTOP and GuardIAS have been collaborating on developing a joint modelling framework for making predictions on IAS spread and impact.
...
Looking ahead, GuardIAS is developing the GuardIAS Intelligence Analyst System (G.I.A.S.) and will eventually provide a dedicated tool for data gathering and knowledge synthesis across the project.
```

---

## Assistant (Plan · DeepSeek V4 Flash Free · 6.0s)

_Thinking:_

Excellent research. Let me also look for the GBIF Alert system and the specific technical requirements document mentioned, plus the EASIN integration details.

**Tool: websearch**

**Input:**
```json
{
  "query": "GBIF Alert system alert.riparias.be early warning invasive species technical",
  "numResults": 5
}
```

**Output:**
```
Title: riparias/gbif-alert - GitHub
URL: https://github.com/riparias/gbif-alert
Published: 2021-08-25T09:58:51.000Z
Author: N/A
Highlights:
GBIF Alert is a GBIF occurrence based alert system.
...
# GBIF Alert

 
[![Django CI](https://github.com/riparias/gbif-alert/actions/workflows/django_tests.yml/badge.svg)](https://github.com/riparias/gbif-alert/actions/workflows/django_tests.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Automatic deployment - demo server](https://github.com/riparias/gbif-alert/actions/workflows/deploy_demo.yml/badge.svg?branch=devel)](https://github.com/riparias/gbif-alert/actions/workflows/deploy_demo.yml)
 

GBIF Alert is a [GBIF](https://www.gbif.org)-based early alert system for invasive species.
...
under the [MIT license
...
GBIF Alert allows you to monitor a list of species, and be notified of new occurrences on GBIF via email.

Multiple websites using GBIF alert (called *instances*) exists, in order to target different communities:
...
- **You are an end-user that just want to be informed of new occurrence in the GBIF network?** Join [an existing instance](#user-content-gbif-alert-instances-in-the-wild) that covers your area and species of interest, register and start configuring your alerts! Here is a demonstration video: https://www.youtube.com/watch?v=bixaTGRIZ4A

- **You have more technical knowledge and want to install your own instance of GBIF Alert?** No problem: GBIF Alert is fully configurable, and we provide facilities to make it easy to install and deploy.
...
- LIFE RIPARIAS Early Alert: [production](https://alert.riparias.be) / [development](https://dev-alert.riparias.be) (Targets riparian invasive species in Belgium)
- [GBIF Alert demo instance](https://gbif-alert-demo.thebinaryforest.net/) (Always in sync with the `devel` branch of this repository)
- The Belgian Biodiversity Platform uses GBIF alert under the hood as an API for the ManaIAS project.

---

Title: LIFE RIPARIAS early alert: Home
URL: https://alert.riparias.be/
Published: N/A
Author: N/A
Highlights:
Home - LIFE RIPARIAS early alert

## Hello

Manage invasive alien species? Want to know where they are? Sign in, configure your alerts and receive updates in your mailbox from all data sources.

Home - LIFE RIPARIAS early alert
## Hello
Manage invasive alien species? Want to know where they are? Sign in, configure your alerts and receive updates in your mailbox from all data sources.

---

Title: Using GBIF-mediated data to better manage invasive alien species
URL: https://biss.pensoft.net/article/93879/
Published: 2022-08-23T11:35:48.000Z
Author: Nicolas Noé, Tim Adriaens, Bram D'hondt, Lien Reyserhove, Peter Desmet, Damiano Oldoni
Highlights:
LIFE RIPARIAS Early Alert: Using GBIF-mediated data to better manage invasive alien species

Conference Abstract

Biodiversity Information Science and Standards 6: e93879 https://doi.org/10.3897/biss.6.93879(23 Aug 2022)

Other versions:

(Verbatim name)

ID

(Catalog Number)

Invalid DOI was provided, please try again

Facebook

Mendeley

- ContentsContents
- Article InfoArticle Info
- CiteCite
- MetricsMetrics
- CommentComment
- RelatedRelated

- MediaMedia
- RefsRefs
- CitedCited

Article metadata

References

Subscribe to email alerts for current Article's categories

Powered by

This website uses cookies in order to improve your web experience. Read our Cookies Policy

OK

---

Title: About this site - LIFE RIPARIAS early alert
URL: https://alert.riparias.be/about-site
Published: N/A
Author: N/A
Highlights:
About this site - LIFE RIPARIAS early alert

# About this site

## Life RIPARIAS

The LIFE RIPARIAS(Reaching Integrated and Prompt Action in Response to Invasive Alien Species) project aims to optimize the management of invasive alien species in river basins across regional borders in Belgium. The project receives funding from the LIFE Programme of the European Union (LIFE19 NAT/BE/000953). A primary objective of RIPARIAS is to improve dataflows on invasive alien species in Belgium for management interventions.

## This early warning tool

The early detection of and rapid response to invasive species is essential to tackle their impact and manage efficiently. Managers need to be rapidly informed when invasive species occur in their area and a coordinated response also requires information on invasive alien species in neighbouring areas. Data on invasive alien species in Belgium were typically scattered among a multitude of actors and databases. This early alert tool centralizes openly available data from various sources by harvesting them from the Global Biodiversity Information Facility (GBIF). They include data from official monitoring schemes, scientific projects and citizen science. Currently, data are displayed for the target species of the LIFE RIPARIAS project and for all invasive species of the IAS Regulation (EU) No 1143/2014.

## FAQ

### How does the alert mechanism works?

Every signed in user has the possibility to create alerts. A new alert can be configured to target specific species, in specific locations and originating from specific datasets. The user can also choose at which frequency they want to receive notification e-mails.

Once such an alert is configured, the user will receive a notification e-mail for each alert when there are matching observations in the "unseen" status.

An observation will be considered as seen after the user has viewed the observation details page. There is also the possibility for a user to batch-mark all the observations corresponding to an alert as "seen".

## Feedback and data contribution

We are always interested to hear you opinion or suggestions for improvement. And of course, if you have open data to contribute to the system, we can help you get them on there. We welcome any reactions on info@alert.riparias.be.

---

Title: riparias/gbif-alert
URL: https://github.com/riparias/early-warning-webapp
Published: 2021-08-25T09:58:51.000Z
Author: N/A
Highlights:
GBIF Alert is a GBIF occurrence based alert system.
...
# GBIF Alert

 
[![Django CI](https://github.com/riparias/gbif-alert/actions/workflows/django_tests.yml/badge.svg)](https://github.com/riparias/gbif-alert/actions/workflows/django_tests.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Automatic deployment - demo server](https://github.com/riparias/gbif-alert/actions/workflows/deploy_demo.yml/badge.svg?branch=devel)](https://github.com/riparias/gbif-alert/actions/workflows/deploy_demo.yml)
 

GBIF Alert is a [GBIF](https://www.gbif.org)-based early alert system for invasive species.
...
GBIF Alert allows you to monitor a list of species, and be notified of new occurrences on GBIF via email.

Multiple websites using GBIF alert (called *instances*) exists, in order to target different communities:
...
- **You are an end-user that just want to be informed of new occurrence in the GBIF network?** Join [an existing instance](#user-content-gbif-alert-instances-in-the-wild) that covers your area and species of interest, register and start configuring your alerts! Here is a demonstration video: https://www.
...
.com/watch?v=bixaTGRIZ4A

- **You have more technical knowledge and want to install your own instance of GBIF Alert?** No problem: GBIF Alert is fully configurable, and we provide facilities to make it easy to install and deploy.
...
- LIFE RIPARIAS Early Alert: [production](https://alert.riparias.be) / [development](https://dev-alert.riparias.be) (Targets riparian invasive species in Belgium)
- [GBIF Alert demo instance](https://gbif-alert-demo.thebinaryforest.net/) (Always in sync with the `devel` branch of this repository)
- The Belgian Biodiversity Platform uses GBIF alert under the hood as an API for the ManaIAS project.
```

**Tool: websearch**

**Input:**
```json
{
  "query": "GuardIAS G.I.A.S. Intelligence Analyst System platform technical",
  "numResults": 5
}
```

**Output:**
```
Title: Work Package 1 - GuardIAS project
URL: https://guardias.eu/work-package-1/
Published: N/A
Author: N/A
Highlights:
Work Package 1 uses Artificial Intelligence to improve how we detect, understand, and manage invasive alien species in aquatic environments. By integrating data from global and regional sources like EASIN, AquaNIS, WRIMS, OBIS and others, WP1 consolidates key information on species biological traits, distributions, and environmental tolerances. Analyzing current patterns of invasive species introduction and spread, this Work Package aims to better understand and predict future threats. The innovative use of AI will further streamline and enhance the prediction and management of invasions, offering more accurate and efficient biosecurity measures.
...
, non-toxic coatings
...
smarter prevention.
...
* **D1.1 **AI-based algorithm to query IAS databases and integrate relevant information
* **D1.2 **Harmonized information across major IAS databases
* **D1.3 **Biogeographic and ecological traits predicting aquatic IAS invasiveness
...
* **D1.4 **Shipping-related biosecurity risk model based on current and predicted economic flows
...
* **D1.5 **Technical report on primary and secondary pathways of introduction and spread for aquatic alien species in Europe
...
* **D1.6 **Development and assessment of superhydrophobic coatings
...
* **D1.7 **Techno
...
economic evaluation report of
...
Our 2026 roadmap includes launching the GuardIAS Intelligence Analyst System (G.I.A.S.) beta version to automate how we gather and harmonize ecological data from global databases and literature. The team will also complete the integration of datasets from EASIN, AquaNIS, and WRiMS to ensure seamless across-platforms data exchange.
...
see great potential in improved
...
Marine biologist with over 40 years of experience in temperate seas and the Arctic. His research focuses on climate change, human impacts, and the spread of invasive alien species. He has pioneered international tools such as AquaNIS and other information system on IAS, IAS impact assessment and bioinvasion indicators.

---

Title: Streamlining Knowledge: How G.I.A.S. is Revolutionising Data Gathering for Invasive Species
URL: https://guardias.eu/streamlining-knowledge-how-g-i-a-s-is-revolutionising-data-gathering-for-invasive-species/
Published: 2026-01-29T21:35:13.000Z
Author: N/A
Highlights:
Streamlining Knowledge: How G.I.A.S. is Revolutionising Data Gathering for Invasive Species

### Recording: Leading the global response to biological invasions: A dialogue between OneSTOP & GuardIAS

25/05/2026

### New dates scheduled for the citizen science marina events in Italy

06/05/2026

### A Year of Action: GuardIAS and OneSTOP’s First Year Tackling Invasive Alien Species Across Europe

26/03/2026

### GuardIAS & INVASIVESNET Virtual Conference – Cost-efficient IAS detection and monitoring: novel tools and Citizen Science

16/03/2026

### Testing Briefs & Videos

04/03/2026

### GuardIAS Policy Brief:Aligning conservation policy with ecological reality beyond national borders

23/02/2026

---

Title: 29/01/2026
URL: https://guardias.eu/2026/01/29/
Published: 2026-01-29T00:00:00.000Z
Author: N/A
Highlights:
29/01/2026

## Streamlining Knowledge: How G.I.A.S. is Revolutionising Data Gathering for Invasive Species

The GuardIAS Intelligence Analyst System (G.I.A.S.) is designed to aggregate knowledge from multiple databases about a given species much faster using AI. The AI interprets non-normalised data across databases that […]

---

Title: News
URL: https://onestop-project.eu/news/year-action-guardias-and-onestops-first-year-tackling-invasive-alien-species-across-europe
Published: N/A
Author: N/A
Highlights:
Looking ahead, GuardIAS is developing the GuardIAS Intelligence Analyst System (G.I.A.S.) and will eventually provide a dedicated tool for data gathering and knowledge synthesis across the project.

---

Title: Intelligence Analysis System (IAS)
URL: https://www.globalsecurity.org/intell/systems/ias.htm
Published: N/A
Author: John Pike
Highlights:
The Intelligence Analysis System (IAS) automates the Marine Air-Ground Task Force (MAGTF) intelligence activities of direction, collection, processing, production and dissemination of critical tactical intelligence from embedded databases and multiple sources. Inter-operability with other systems such as Navy Tactical Command System-Afloat, Joint Deployable Intelligence Support System (JDISS) and all Marine Air Ground Intelligence systems is maintained to ensure a common intelligence picture of the battlefield. The IAS configuration also provides administrative support through the use of word processing, graphics, spreadsheet, and data base management programs.
...
The Joint Intelligence Fusion System (JIFS) uses Object Request Broker (ORB) technology within the Common Object Request Broker Architecture (CORBA) framework. CORBA is a standard that enables different software applications at dispersed locations to interact without prior knowledge of their network location. ORB serves as an invisible intermediary which translates between applications and transports information across the network. Using this software infrastructure, the project demonstrates joint database level interoperability between the Army's All Source Analysis System-Remote Workstation (ASAS-RWS), the Air Force's Combat Information System (CIS) and the Marine Corps' Intelligence Analysis System (IAS). JIFS is an extension of all of these intelligence processing platforms and is designed to be highly scaleable to not only other intelligence systems but also to any C2 system.
...
The IAS architecture is scalable from a single, stand alone, portable workstation at the battalion/squadron level; to a four station, on line, moveable, intermediate suite at the MEU level; to a mobile mounted multi-station real time, service-wide intelligence communications link at the MEF level. The IAS will deploy either as a MEF IAS, in IAS suites, or as single IAS workstations. The MEF IAS serves as the hub of the Marine Air-Ground Intelligence System (MAGIS). It provides intelligence functionality to the echelon-tailored, MAGTF all-source intelligence fusion centers and is compatible with the DII COE. MEF IAS is a shelterized, mobile system with multiple analyst workstations in a client-server LAN configuration. IAS suites, for intermediate commands, are configured in either a two or a four workstation LAN. Single IAS workstations are for battalion and squadron-sized units.
```

**Tool: websearch**

**Input:**
```json
{
  "query": "GuardIAS early alert tool technical specification requirements document",
  "numResults": 5
}
```

**Output:**
```
Title: GuardIAS workshop - Early Alert System - IAS AI Tool
      -  Research Institute for Nature and Forest
URL: https://pureportal.inbo.be/en/activities/guardias-workshop-early-alert-system-ias-ai-tool/
Published: N/A
Author: N/A
Highlights:
GuardIAS workshop - Early Alert System - IAS AI Tool - Research Institute for Nature and Forest

# GuardIAS workshop - Early Alert System - IAS AI Tool

- Adriaens, T.(Speaker)
- Oldoni, D.(Speaker)

- Management Team
- Open science lab for biodiversity

Activity: Participating in or organising an event types › Organisation and participation in conference, workshop, training, seminar, meeting

| Period |
| --- |
| Event type | Workshop |
| Degree of Recognition | International |

## Documents & Links

File: application/pdf, 1.65 MB

Type: Pdffile

Link to PDF slides at speakerdeck.

## Related content

### Projects

## Guarding European Waters from IAS (GuardIAS)

Project: EVINBO - Europees

### Activities

## GuardIAS General Assemblee meeting

Activity: Participating in or organising an event types › Organisation and participation in conference, workshop, training, seminar, meeting

---

Title: GuardIAS stakeholder workshop on early alert and AI tool
      -  Research Institute for Nature and Forest
URL: https://pureportal.inbo.be/en/activities/guardias-stakeholder-workshop-on-early-alert-and-ai-tool/
Published: N/A
Author: N/A
Highlights:
GuardIAS stakeholder workshop on early alert and AI tool - Research Institute for Nature and Forest

# GuardIAS stakeholder workshop on early alert and AI tool

- Adriaens, T.(Speaker)
- Oldoni, D.(Speaker)
- Petersen, F.(Participant)

- Management Team
- Open science lab for biodiversity
- InvasionLab

Activity: Participating in or organising an event types › Organisation and participation in conference, workshop, training, seminar, meeting

| Period |
| --- |
| Event type | Workshop |
| Degree of Recognition | International |

## Documents & Links

File: application/pdf, 113 KB

Type: Pdffile

## Related content

### Projects

## Guarding European Waters from IAS (GuardIAS)

Project: EVINBO - Europees

### Activities

## GuardIAS General Assemblee meeting

Activity: Participating in or organising an event types › Organisation and participation in conference, workshop, training, seminar, meeting

---

Title: GuardIAS-OneStop-EASIN technical workshop
      -  Research Institute for Nature and Forest
URL: https://pureportal.inbo.be/en/activities/guardias-onestop-easin-technical-workshop/
Published: N/A
Author: N/A
Highlights:
GuardIAS-OneStop-EASIN technical workshop - Research Institute for Nature and Forest 

# GuardIAS-OneStop-EASIN technical workshop

- Oldoni, D.(Organiser)
- Reyserhove, L.(Organiser)
- Adriaens, T.(Participant)

- Management Team
- Open science lab for biodiversity

Activity: Participating in or organising an event types › Organisation and participation in conference, workshop, training, seminar, meeting

| Period |
| --- |
| Event type | Workshop |
| Location | Ispra, Italy Show on map |
| Degree of Recognition | International |

## Related content

### Projects

## Guarding European Waters from IAS (GuardIAS)

Project: EVINBO - Europees

## OneBiosecurity Systems and Technology for People, Places and Pathways (OneSTOP)

Project: EVINBO - Europees

### Research output

## OneSTOP: OneBiosecurity systems and technology for people, places and pathways

Research output: Contribution to journal › A1: Web of Science-article

## LIFE RIPARIAS Early Alert: Using GBIF-mediated data to better manage invasive alien species

Research output: Contribution to journal › A2: Article in a journal with peer review, not included in A1

## Bridge from observation to action: innovative tools from the LIFE RIPARIAS project in support of effective management frameworks

Research output: Contribution to conference › Paper/Powerpoint/Abstract

## LIFE RIPARIAS early alert tool

Research output: Contribution to conference › Paper/Powerpoint/Abstract

## GuardIAS–Guarding European Waters from Invasive Alien Species

Research output: Contribution to journal › A1: Web of Science-article › peer-review

### Activities

## Joint kick-off meeting Horizon Europe projects Guarding GuardIAS and OneStop

Activity: Participating in or organising an event types › Organisation and participation in conference, workshop, training, seminar, meeting

## GuardIAS General Assembly

Activity: Participating in or organising an event types › Organisation and participation in conference, workshop, training, seminar, meeting

## GuardIAS General Assembly meeting

Activity: Participating in or organising an event types › Organisation and participation in conference, workshop, training, seminar, meeting

---

Title: From local alerts to European policy impact: toward faster detection and communication of invasive species threats
      -  Research Institute for Nature and Forest
URL: https://pureportal.inbo.be/en/activities/from-local-alerts-to-european-policy-impact-toward-faster-detecti/
Published: N/A
Author: N/A
Highlights:
PresenterDamiano OldoniAuthorsNicolas Noé - The binary forest, BelgiumTim Adriaens - Research Institute for Nature and Forest (INBO), BelgiumAna Cristina Cardoso - Joint Research Centre, ItalyFabio D'Amico - Engineering Ingegneria Informatica S.p.A, ItalyQuentin Groom - Meise Botanic Garden, BelgiumStelios Katsanevakis - University of the Aegean, GreeceLien Reyserhove - Research Institute for Nature and Forest (INBO), BelgiumDamiano Oldoni - Research Institute for Nature and Forest (INBO), BelgiumAbstractIt is universally recognised that early detection of invasive alien species (IAS) incursions is the most cost-effective way to prevent invasion once they have been introduced. At the TDWG 2022 annual conference we presented an early alert tool (https://alert.riparias.be/), an open-source system designed to send e-mail alerts to researchers and managers of IAS based on GBIF (Global Biodiversity Information Facility) occurrence data (https://doi.org/10.3897/biss.6.93879). The tool was developed by the LIFE RIPARIAS project (https://www.riparias.be/en) and it was initially tailored to a specific project area and thirty target species. However, the importance of the tool transcended the project, as other actors within the Belgian IAS landscape were interested in using it. Adding some specific areas of interest and extending the taxonomic scope were relatively easy improvements with a high positive impact.The software consists of two independent elements: the tool core functionality, or engine, and the configuration setup, which specifies the areas and species of interest, the text elements and the general web appearance. The engine, called GBIF Alert, is an open source software (https://github.com/riparias/gbif-alert) to use in combination with a configuration setup to create an alert tool based on Darwin Core Archive and tailored to the specific target community, and so transcending the initial Belgian IAS community. This flexibility was not only awarded with winning the 2023 Ebbe Nielsen Challenge, but is also leading to further development in the context of two European Horizon projects started in 2025: GuardIAS (https://guardias.eu/) and OneSTOP (https://onestop-project.eu/). Thanks to the collaboration with the European Alien Species Information Network (EASIN, https://easin.jrc.ec.europa.eu/easin), the tool is evolving to become a European initiative with potential for direct impact on decision-making.
...
## Documents & Links
...
File: application/pdf, 4.48 MB
...
: Development and Integration of GBIF

---

Title: Guarding European Waters from IAS (GuardIAS) - Activities
      -  Research Institute for Nature and Forest
URL: https://pureportal.inbo.be/en/projects/guarding-european-waters-from-ias-guardias/activities/
Published: N/A
Author: N/A
Highlights:
### GuardIAS workshop - Early Alert System - IAS AI Tool
...
Adriaens, T.(Speaker) & Oldoni, D.(Speaker)
...
### GuardIAS stakeholder workshop on early alert and AI tool
...
Adriaens, T.(Speaker), Oldoni, D.(Speaker) & Petersen, F.(Participant)
```

---

## Assistant (Plan · DeepSeek V4 Flash Free · 18.2s)

_Thinking:_

Now I have a very comprehensive picture of GuardIAS tech and its EWS approach. Let me synthesize this into an updated plan for MAMIAS that incorporates GuardIAS technology.

Key findings about GuardIAS:

1. **GBIF Alert System** (https://github.com/riparias/gbif-alert) - Open source MIT-licensed Django app that monitors GBIF occurrences and sends email alerts. This is the core tech being upscaled by GuardIAS to European level.

2. **GuardIAS Intelligence Analyst System (G.I.A.S.)** - AI-assisted system that aggregates knowledge from multiple databases (EASIN, AquaNIS, WRiMS, OBIS, GBIF) about species. Beta in 2026.

3. **Species Occurrence Cubes** - GBIF-based data cubes (spatial × temporal × taxonomic) for detecting and prioritizing emerging species. Being adapted for marine species with weekly automation.

4. **EASIN Integration** - GuardIAS is developing the early alert tool in collaboration with EASIN team. Planning full integration into EASIN ecosystem.

5. **iNaturalist Integration** - AI-powered species identification from smartphone photos enhanced with expert-verified IAS images.

6. **Multiple detection methods**: eDNA, satellite imagery, hydroacoustics, BRUV systems, underwater robotics.

7. **GuardIAS-OneSTOP-EASIN technical workshop** already happened - they're working on joint technical specs.

So for MAMIAS, the alignment is:

MAMIAS has:
- Its own occurrence/sightings data (already in PostGIS)
- NisSuggestion (citizen science)
- IntroEventRecord (introduction events)
- EASIN_id on Taxon model
- WormsService for taxonomy
- Existing queue infrastructure
- Database notifications + email

GuardIAS/EWS approach that MAMIAS could adopt:
1. **GBIF Alert as model** - MAMIAS could build a similar alert system but using its own occurrence data as the primary source (rather than GBIF), complemented by GBIF/EASIN data pulls
2. **Occurrence Cubes approach** - Use the TrIAS workflow adapted for Mediterranean marine species. MAMIAS has spatial data ready.
3. **GIAS integration** - If GIAS becomes available, MAMIAS could consume species intelligence data
4. **EASIN push/pull** - MAMIAS already has EasinService, extend it for bidirectional data flow
5. **iNaturalist data pull** - Pull observations from iNaturalist projects for Mediterranean region

The plan should now be updated to reflect these concrete connections to existing European-level infrastructure.

Let me now formulate the updated comprehensive plan.

Excellent — GuardIAS is directly relevant. Let me synthesize what I found.

---

## GuardIAS EWS — Key Findings

GuardIAS (Horizon Europe, 2025-2028) is building a European-level Early Alert System for aquatic IAS, and several components are directly applicable to MAMIAS:

### 1. GBIF Alert Engine (MIT open source)
The core tech — `github.com/riparias/gbif-alert` — is a Django app that:
- Harvests GBIF occurrence data
- Lets users configure alerts per species + area
- Sends email notifications on new matching records
- **GuardIAS is upscaling this to European level in collaboration with EASIN**

MAMIAS can **either** deploy its own GBIF Alert instance for the Mediterranean **or** integrate with the upcoming GuardIAS/EASIN instance.

### 2. Species Occurrence Cubes (TrIAS → GuardIAS)
GBIF occurrence cubes aggregate data by grid cell × year × species. GuardIAS is automating this weekly for marine species (Large Marine Ecosystems), scoring each species on an "emerging" scale. **MAMIAS occurrences fit directly into this workflow** — you already have `location_point` (PostGIS Point, 4326).

### 3. G.I.A.S. (GuardIAS Intelligence Analyst System)
Beta launching in 2026. AI-assisted data aggregation across EASIN, AquaNIS, WRiMS, OBIS, GBIF. MAMIAS could become a **data consumer** (species intelligence) and potentially a **data provider** (push MAMIAS occurrences upstream).

### 4. EASIN Integration Pipeline
GuardIAS, OneSTOP, and EASIN already held a joint technical workshop (Ispra, Italy). The early alert tool is being designed for full EASIN ecosystem integration. **MAMIAS already has `Easin_id` on Taxa and `EasinService`** — well-positioned to connect.

### 5. iNaturalist
GuardIAS has a dedicated iNaturalist project for aquatic IAS with 251+ species. MAMIAS could pull iNaturalist observations for the Mediterranean into its alert evaluation.

---

## Updated EWS Plan for MAMIAS (with GuardIAS alignment)

```
P0 ─ Foundation (2-3 weeks)
├── Alert + AlertSubscription models
├── Alert Engine (EwsEvaluator) — evaluates new Occurrences/NisSuggestions
│   └── Detection logic: new species in subregion, range expansion, high-impact species
├── User notification preferences (email + database + SMS channels)
└── Filament AlertResource (admin CRUD + dashboard widget)

P1 ─ GuardIAS/EASIN Integration (1-2 weeks)
├── GBIF Alert compatibility — MAMIAS own data as primary source
├── Occurrence Cubes workflow — weekly emerging species scoring (MAMIAS data)
└── EASIN bidirectional sync — push MAMIAS occurrences, pull EASIN alerts

P2 ─ External Data & Citizen Science (1-2 weeks)
├── iNaturalist Mediterranean IAS pull → feed into alert evaluation
├── GBIF occurrence pull for MAMIAS species
└── Webhook receiver for GuardIAS/EASIN push alerts

P3 ─ Public Facing & API (1-2 weeks)
├── Public alert page (Livewire) + map
├── REST API for external alert consumers
└── Outbound webhooks for real-time alert distribution
```

**The key intersection:** MAMIAS already has the core data model (Occurrence + spatial + NisSuggestion + IntroEventRecord) that GuardIAS' alert engine evaluates. The quickest path to EWS capability is building the **Alert model + EwsEvaluator** (P0), then wiring into GuardIAS/EASIN infrastructure (P1).

Would you like me to refine the technical architecture for any specific phase?

---

## User

yes

---

## Assistant (Plan · DeepSeek V4 Flash Free · 155.8s)

_Thinking:_

The user said "yes" to refining the technical architecture. Let me dive deeper into specific areas to produce a more detailed technical plan. Let me explore the existing codebase more thoroughly to understand the specific patterns I need to align with, and also research the GBIF Alert system architecture more deeply.

**Tool: task**

**Input:**
```json
{
  "description": "Deep-dive MAMIAS patterns & services",
  "subagent_type": "explore",
  "prompt": "Very thorough exploration of /home/toufa/dev/2026/apps/mamias/apps focusing on technical implementation patterns:\n\n1. **Notification pattern**: Read app/Notifications/ - all notification classes. How do they send? Mail + database? Show the full NisSuggestionApproved notification and OccurrenceApproved notification.\n\n2. **Observer pattern**: Check app/Providers/EventServiceProvider.php, app/Observers/ if any exist. How are model events wired?\n\n3. **Queue jobs pattern**: Read app/Jobs/FetchEasinIdsJob.php and app/Jobs/FetchTaxaFromWormsJob.php fully. How are jobs dispatched? How does TracksJobProgress trait work?\n\n4. **Service pattern**: Read app/Services/EasinService.php fully. Show how API calls are cached. Read app/Services/WormsService.php partially (first 100 lines + method signatures).\n\n5. **Filament resource pattern**: Read one complete resource like app/Filament/Resources/NisSuggestionResource.php to see the structure. Also check if there are Schemas/* and Tables/* files.\n\n6. **Model booted pattern**: Read app/Models/Occurrence.php and app/Models/IntroEventRecord.php fully to see booted() traits and event wiring.\n\n7. **Enum pattern**: Read an enum like app/Enums/OccurrenceStatus.php and app/Enums/NisStatus.php to see the pattern.\n\n8. **Spatial trait**: Read app/Models/Traits/HasSpatialLocation.php fully.\n\n9. **Config**: Read app/config/services.php fully to see how external API keys are configured.\n\n10. **Livewire pattern**: Read a public Livewire component (e.g. MySuggestions or PublicProfile) to see the pattern for public components.\n\nReturn ALL file contents - do not summarize. I need exact code for implementation reference."
}
```

**Output:**
```
<task id="ses_106ba9f24ffezgcx7RkEttELs1" state="completed">
<task_result>
Here are all the files and their complete contents, organized by pattern:

---

## 1. Notification Pattern

All notification classes live in `app/Notifications/`. There are 6 notifications total. They send via **mail** (and one also via `database`). They all use `Queueable`.

### NisSuggestionApproved (mail only)

**File:** `/home/toufa/dev/2026/apps/mamias/apps/app/Notifications/NisSuggestionApproved.php`
```php
<?php

namespace App\Notifications;

use App\Models\NisSuggestion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NisSuggestionApproved extends Notification
{
    use Queueable;

    public function __construct(public NisSuggestion $suggestion) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[MAMIAS] Your species suggestion has been approved')
            ->greeting('Great news, '.$notifiable->name.'!')
            ->line("Your suggestion for *{$this->suggestion->suggested_scientific_name}* has been approved and added to the MAMIAS catalogue as a new taxon draft.")
            ->line('Our team will enrich the record with additional data from WoRMS and other sources.')
            ->line('Thank you for contributing to the MAMIAS Non-Indigenous Species database.');
    }
}
```

### OccurrenceApproved (mail only)

**File:** `/home/toufa/dev/2026/apps/mamias/apps/app/Notifications/OccurrenceApproved.php`
```php
<?php

namespace App\Notifications;

use App\Models\Occurrence;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OccurrenceApproved extends Notification
{
    use Queueable;

    public function __construct(public Occurrence $occurrence) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[MAMIAS] Your occurrence report has been approved')
            ->greeting('Great news, '.$notifiable->name.'!')
            ->line("Your occurrence report for *{$this->occurrence->taxon?->scientificname}* has been approved and is now visible in the MAMIAS database.")
            ->line('Thank you for contributing to the MAMIAS Non-Indigenous Species database.');
    }
}
```

### NisSuggestionRejected (mail only)

**File:** `/home/toufa/dev/2026/apps/mamias/apps/app/Notifications/NisSuggestionRejected.php`
```php
<?php

namespace App\Notifications;

use App\Models\NisSuggestion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NisSuggestionRejected extends Notification
{
    use Queueable;

    public function __construct(public NisSuggestion $suggestion) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[MAMIAS] Your species suggestion was not accepted')
            ->greeting('Hello, '.$notifiable->name.'.')
            ->line("Unfortunately, your suggestion for *{$this->suggestion->suggested_scientific_name}* has not been accepted at this time.")
            ->when($this->suggestion->rejection_reason, function (MailMessage $mail): MailMessage {
                return $mail->line('**Reason:** '.$this->suggestion->rejection_reason);
            })
            ->line('You are welcome to submit a revised suggestion with additional supporting information.')
            ->line('Thank you for your contribution to the MAMIAS database.');
    }
}
```

### OccurrenceRejected (mail only)

**File:** `/home/toufa/dev/2026/apps/mamias/apps/app/Notifications/OccurrenceRejected.php`
```php
<?php

namespace App\Notifications;

use App\Models\Occurrence;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OccurrenceRejected extends Notification
{
    use Queueable;

    public function __construct(public Occurrence $occurrence) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[MAMIAS] Your occurrence report was not accepted')
            ->greeting('Hello, '.$notifiable->name.'.')
            ->line("Unfortunately, your occurrence report for *{$this->occurrence->taxon?->scientificname}* has not been accepted at this time.")
            ->when($this->occurrence->moderation_notes, function (MailMessage $mail): MailMessage {
                return $mail->line('**Reason:** '.$this->occurrence->moderation_notes);
            })
            ->line('Thank you for your contribution to the MAMIAS database.');
    }
}
```

### NewLiteratureReferenceNotification (mail + database, with Filament notification actions)

**File:** `/home/toufa/dev/2026/apps/mamias/apps/app/Notifications/NewLiteratureReferenceNotification.php`
```php
<?php

namespace App\Notifications;

use App\Models\Literature;
use Filament\Actions\Action as FilamentAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLiteratureReferenceNotification extends Notification
{
    use Queueable;

    public function __construct(public Literature $literature) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $creatorName = $this->literature->creator?->name ?? 'A user';
        $title = $this->literature->short_ref ?: $this->literature->code;

        return (new MailMessage)
            ->subject('[MAMIAS] New Reference Submitted: '.$this->literature->code)
            ->line("{$creatorName} has submitted a new bibliographic reference.")
            ->line("Reference: {$title}")
            ->action('Review Reference', route('filament.mamias.resources.literatures.edit', ['record' => $this->literature]))
            ->line('Thank you for keeping the MAMIAS database up to date.');
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('New Reference Submitted')
            ->icon('tabler-file-plus')
            ->iconColor('success')
            ->body("{$this->literature->code} submitted by ".($this->literature->creator?->name ?? 'a user'))
            ->actions([
                FilamentAction::make('view')
                    ->button()
                    ->url(route('filament.mamias.resources.literatures.edit', ['record' => $this->literature])),
            ])
            ->getDatabaseMessage();
    }
}
```

### VerifyEmail (extends Filament's VerifyEmail)

**File:** `/home/toufa/dev/2026/apps/mamias/apps/app/Notifications/VerifyEmail.php`
```php
<?php

namespace App\Notifications;

use Filament\Auth\Notifications\VerifyEmail as FilamentVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmail extends FilamentVerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify Email Address')
            ->greeting('Welcome to MAMIAS!')
            ->line('Please click the button below to verify your email address and activate your account.')
            ->action('Visit MAMIAS', $url)
            ->line('If you did not create an account, no further action is required.');
    }
}
```

---

## 2. Observer Pattern

There is **no** `EventServiceProvider.php` in the app (auto-discovery is used). There is one observer in `app/Observers/`.

**File:** `/home/toufa/dev/2026/apps/mamias/apps/app/Observers/LiteratureObserver.php`
```php
<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Literature;
use App\Models\User;
use App\Notifications\NewLiteratureReferenceNotification;
use Illuminate\Support\Facades\Notification;

class LiteratureObserver
{
    public function creating(Literature $literature): void
    {
        if (empty($literature->code)) {
            $literature->code = Literature::generateNextCode();
        }
    }

    public function created(Literature $literature): void
    {
        $adminsAndScientists = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['super_admin', 'scientist']);
        })->get();

        if ($adminsAndScientists->isNotEmpty()) {
            Notification::send($adminsAndScientists, new NewLiteratureReferenceNotification($literature));
        }
    }
}
```

---

## 3. Queue Jobs Pattern

### FetchEasinIdsJob

**File:** `/home/toufa/dev/2026/apps/mamias/apps/app/Jobs/FetchEasinIdsJob.php`
```php
<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksJobProgress;
use App\Models\Taxon;
use App\Models\User;
use App\Services\EasinService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class FetchEasinIdsJob implements ShouldQueue
{
    use Queueable;
    use TracksJobProgress;

    public int $timeout = 1800;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    private const SECONDS_PER_TAXON = 1;

    private const PROGRESS_CACHE_PREFIX = 'easin-fetch-progress-';

    /**
     * @param  array<int>  $taxonIds
     */
    public function __construct(
        private readonly array $taxonIds,
        private readonly ?int $userId = null,
    ) {}

    public function handle(EasinService $easinService): void
    {
        $total = count($this->taxonIds);
        $startTime = microtime(true);
        $processed = 0;
        $totals = ['found' => 0, 'not_found' => 0, 'skipped' => 0];

        $this->updateProgress($processed, $total, $startTime);

        try {
            Taxon::whereIn('id', $this->taxonIds)
                ->chunkById(50, function (Collection $chunk) use ($easinService, &$totals, &$processed, $total, $startTime): void {
                    foreach ($chunk as $taxon) {
                        if (! $taxon->scientificname) {
                            $totals['skipped']++;
                            $processed++;
                            $this->updateProgress($processed, $total, $startTime);

                            continue;
                        }

                        $easinId = $easinService->fetchEasinId($taxon->scientificname);
                        if ($easinId) {
                            $taxon->update(['Easin_id' => $easinId]);
                            $totals['found']++;
                        } else {
                            $totals['not_found']++;
                        }

                        $processed++;
                        $this->updateProgress($processed, $total, $startTime);
                    }
                });
        } catch (\Throwable $e) {
            Log::error("FetchEasinIdsJob failed: {$e->getMessage()}");

            $this->setProgress([
                'status' => 'failed',
                'processed' => $processed,
                'total' => $total,
                'percentage' => $total > 0 ? round(($processed / $total) * 100) : 0,
                'estimatedTime' => '',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        $this->setProgress([
            'status' => 'completed',
            'processed' => $total,
            'total' => $total,
            'percentage' => 100,
            'estimatedTime' => '',
            'totals' => $totals,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('FetchEasinIdsJob failed permanently', [
            'taxon_ids' => $this->taxonIds,
            'user_id' => $this->userId,
            'message' => $exception->getMessage(),
        ]);

        if ($this->userId) {
            $user = User::find($this->userId);
            if ($user) {
                Notification::make()
                    ->title('EASIN ID fetch failed')
                    ->body('The EASIN ID lookup failed after multiple attempts: '.$exception->getMessage())
                    ->danger()
                    ->sendToDatabase($user);
            }
        }
    }
}
```

### FetchTaxaFromWormsJob

**File:** `/home/toufa/dev/2026/apps/mamias/apps/app/Jobs/FetchTaxaFromWormsJob.php`
```php
<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksJobProgress;
use App\Models\Taxon;
use App\Models\User;
use App\Services\TaxonService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class FetchTaxaFromWormsJob implements ShouldQueue
{
    use Queueable;
    use TracksJobProgress;

    public int $timeout = 1800;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    private const SECONDS_PER_TAXON = 1;

    private const PROGRESS_CACHE_PREFIX = 'worms-fetch-progress-';

    /**
     * @param  array<int>  $taxonIds
     */
    public function __construct(
        private readonly array $taxonIds,
        private readonly ?int $userId = null,
    ) {}

    public function handle(TaxonService $taxonService): void
    {
        $total = count($this->taxonIds);
        $startTime = microtime(true);
        $processed = 0;
        $totals = ['updated' => 0, 'missing_aphia_id' => 0, 'not_found' => 0];

        $this->updateProgress($processed, $total, $startTime);

        try {
            Taxon::whereIn('id', $this->taxonIds)
                ->chunkById(50, function (Collection $chunk) use ($taxonService, &$totals, &$processed, $total, $startTime): void {
                    $result = $taxonService->refreshFromWorms($chunk, function () use (&$processed, $total, $startTime) {
                        $processed++;
                        $this->updateProgress($processed, $total, $startTime);
                    });

                    $totals['updated'] += $result['updated'];
                    $totals['missing_aphia_id'] += $result['missing_aphia_id'];
                    $totals['not_found'] += $result['not_found'];
                });
        } catch (\Throwable $e) {
            Log::error("FetchTaxaFromWormsJob failed: {$e->getMessage()}");

            $this->setProgress([
                'status' => 'failed',
                'processed' => $processed,
                'total' => $total,
                'percentage' => $total > 0 ? round(($processed / $total) * 100) : 0,
                'estimatedTime' => '',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        $this->setProgress([
            'status' => 'completed',
            'processed' => $total,
            'total' => $total,
            'percentage' => 100,
            'estimatedTime' => '',
            'totals' => $totals,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('FetchTaxaFromWormsJob failed permanently', [
            'taxon_ids' => $this->taxonIds,
            'user_id' => $this->userId,
            'message' => $exception->getMessage(),
        ]);

        if ($this->userId) {
            $user = User::find($this->userId);
            if ($user) {
                Notification::make()
                    ->title('WoRMS sync failed')
                    ->body('The taxonomy update failed after multiple attempts: '.$exception->getMessage())
                    ->danger()
                    ->sendToDatabase($user);
            }
        }
    }

    public static function estimateDuration(int $count): string
    {
        return self::formatDuration($count * self::SECONDS_PER_TAXON);
    }
}
```

### TracksJobProgress Trait

**File:** `/home/toufa/dev/2026/apps/mamias/apps/app/Jobs/Concerns/TracksJobProgress.php`
```php
<?php

namespace App\Jobs\Concerns;

use Illuminate\Support\Facades\Cache;

trait TracksJobProgress
{
    private function updateProgress(int $processed, int $total, float $startTime): void
    {
        $percentage = $total > 0 ? round(($processed / $total) * 100) : 0;

        if ($processed > 0) {
            $elapsed = microtime(true) - $startTime;
            $rate = $processed / $elapsed;
            $remaining = $rate > 0 ? ($total - $processed) / $rate : 0;
            $estimatedTime = self::formatDuration($remaining);
        } else {
            $estimatedTime = self::formatDuration($total * static::SECONDS_PER_TAXON);
        }

        $this->setProgress([
            'status' => 'running',
            'processed' => $processed,
            'total' => $total,
            'percentage' => $percentage,
            'estimatedTime' => $estimatedTime,
        ]);
    }

    private function setProgress(array $data): void
    {
        if ($this->userId === null) {
            return;
        }

        $cacheKey = static::PROGRESS_CACHE_PREFIX.$this->userId;
        Cache::put($cacheKey, $data, now()->addHour());
    }

    private static function formatDuration(float $seconds): string
    {
        if ($seconds < 60) {
            return round($seconds).' seconds';
        }

        if ($seconds < 3600) {
            return round($seconds / 60, 1).' minutes';
        }

        return round($seconds / 3600, 1).' hours';
    }
}
```

**How jobs are dispatched:** Via the constructor, jobs accept `array $taxonIds` and optional `?int $userId`. They are dispatched normally (e.g., `FetchEasinIdsJob::dispatch($taxonIds, auth()->id())`). The `TracksJobProgress` trait stores progress in the cache under `{PROGRESS_CACHE_PREFIX}{userId}`, updated in `updateProgress()` and finalized in `setProgress()`.

---

## 4. Service Pattern

### EasinService (full - shows API caching)

**File:** `/home/toufa/dev/2026/apps/mamias/apps/app/Services/EasinService.php`
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class EasinService
{
    private string $baseUrl = 'https://easin.jrc.ec.europa.eu/apixg/catxg/term';

    public function fetchEasinId(string $scientificName): ?string
    {
        if (strlen($scientificName) < 4) {
            return null;
        }

        $term = rawurlencode($scientificName);
        $url = "{$this->baseUrl}/{$term}";

        return Cache::remember('easin_id_'.md5($scientificName), 86400, function () use ($url) {
            try {
                $response = Http::timeout(10)->get($url);

                if ($response->successful()) {
                    $data = $response->json();

                    if (is_array($data) && count($data) > 0) {
                        return $data[0]['EASINID'] ?? $data[0]['easinId'] ?? null;
                    }
                }
            } catch (\Exception $e) {
                logger()->error('EASIN API request failed', [
                    'scientific_name' => $scientificName,
                    'message' => $e->getMessage(),
                ]);
            }

            return null;
        });
    }
}
```

**Caching pattern:** `Cache::remember('easin_id_'.md5($scientificName), 86400, ...)` -- caches for 24 hours. The `Cache::remember()` wraps the HTTP call.

### WormsService (first 100 lines + method signatures)

**File:** `/home/toufa/dev/2026/apps/mamias/apps/app/Services/WormsService.php`
```php
<?php

namespace App\Services;

use App\Enums\Catalogue_Status;
use App\Enums\Environment;
use App\Enums\Worms_Status;
use App\Models\Taxon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WormsService
{
    private const KINGDOMS = [
        2 => 'Animalia',
        6 => 'Bacteria',
        7 => 'Chromista',
        4 => 'Fungi',
        3 => 'Plantae',
        5 => 'Protozoa',
        10 => 'Viruses',
    ];

    private string $baseUrl = 'https://www.marinespecies.org/rest';

    private int $requestTimeoutSeconds = 30;

    private int $connectTimeoutSeconds = 10;

    final public function getPhyla(): array
    {
        return Cache::remember('worms_phyla_grouped_v2', 86400, function () {
            $groupedPhyla = [];

            foreach (self::KINGDOMS as $aphiaId => $name) {
                $phyla = [];
                $this->fetchPhylaRecursive($aphiaId, $phyla);

                if (! empty($phyla)) {
                    ksort($phyla);
                    $count = count($phyla);
                    $groupedPhyla["{$name} ({$count})"] = $phyla;
                }
            }

            ksort($groupedPhyla);

            return $groupedPhyla;
        });
    }

    final public function getRecordByAphiaID(int $aphiaId): ?array
    {
        $response = $this->wormsRequest("{$this->baseUrl}/AphiaRecordByAphiaID/{$aphiaId}");

        if (! $response || $response->status() === 204 || $response->failed()) {
            return null;
        }

        return $response->json();
    }

    public function searchSpecies(string $search, bool $like = true, bool $marineOnly = true): array
    {
        if (strlen($search) < 3) {
            return [];
        }

        $response = $this->wormsRequest("{$this->baseUrl}/AphiaRecordsByName/".urlencode($search), [
            'like' => $like ? 'true' : 'false',
            'marine_only' => $marineOnly ? 'true' : 'false',
        ]);

        return $this->processResponse($response);
    }

    public function matchTaxa(string $name, bool $marineOnly = true): array
    {
        $response = $this->wormsRequest("{$this->baseUrl}/AphiaRecordsByMatchNames", [
            'scientificnames[]' => $name,
            'marine_only' => $marineOnly ? 'true' : 'false',
        ]);

        return $this->processResponse($response);
    }

    public function getRecordByName(string $name): ?array
    {
        $records = $this->searchSpecies($name, like: false);

        return ! empty($records) ? $records[0] : null;
    }

    final public function getSynonyms(int $aphiaId): array
    {
        $response = $this->wormsRequest("{$this->baseUrl}/AphiaSynonymsByAphiaID/{$aphiaId}");

        return $this->processResponse($response);
    }

    private function processResponse(?Response $response): array
    {
        if (! $response || $response->status() === 204 || $response->failed()) {
            return [];
        }

        $records = $response->json();

        if (isset($records['AphiaID'])) {
            return [$records];
        }

        return is_array($records) ? $records : [];
    }

    final protected function fetchPhylaRecursive(int $aphiaId, array &$phyla, int $depth = 0): void
    {
        if ($depth > 3) {
            return;
        }

        $response = $this->wormsRequest("{$this->baseUrl}/AphiaChildrenByAphiaID/{$aphiaId}");

        if (! $response) {
            return;
        }

        if ($response->failed()) {
            return;
        }

        $children = $response->json();

        if (! is_array($children)) {
            return;
        }

        foreach ($children as $child) {
            if ($child['status'] !== 'accepted') {
                continue;
            }

            if ($child['rank'] === 'Phylum') {
                $phyla[$child['scientificname']] = $child['scientificname'];
            } elseif (in_array($child['rank'], ['Subkingdom', 'Kingdom', 'Infrakingdom', 'Superphylum'])) {
                $this->fetchPhylaRecursive($child['AphiaID'], $phyla, $depth + 1);
            }
        }
    }

    final public function populateTaxonFromWorms(Taxon $taxon, array $data): void
    {
        $data = $this->handleUnacceptedName($taxon, $data);

        $this->mapTaxonFields($taxon, $data);

        $this->expandSynonyms($taxon, persist: false);

        app(TaxonNormalizer::class)->normalize($taxon);

        $taxon->fetched_at = now();
    }

    final public function expandSynonyms(Taxon $taxon, bool $persist = true): int
    {
        $aphiaId = $taxon->aphia_id;
        if (! $aphiaId) {
            return 0;
        }

        $synonyms = $this->getSynonyms($aphiaId);

        if (empty($synonyms)) {
            $taxon->synonyms_data = [];
            if ($persist) {
                $taxon->saveQuietly();
            }

            return 0;
        }

        $filteredSynonyms = array_map(function ($synonym) {
            return [
                'AphiaID' => $synonym['AphiaID'] ?? null,
                'scientificname' => $synonym['scientificname'] ?? null,
                'authority' => $synonym['authority'] ?? null,
                'status' => $synonym['status'] ?? null,
                'unacceptreason' => $synonym['unacceptreason'] ?? null,
            ];
        }, $synonyms);

        $taxon->synonyms_data = $filteredSynonyms;
        if ($persist) {
            $taxon->saveQuietly();
        }

        return count($filteredSynonyms);
    }

    private function handleUnacceptedName(Taxon $taxon, array $data): array
    {
        if (($data['status'] ?? '') !== 'unaccepted' || empty($data['valid_AphiaID'])) {
            return $data;
        }

        $acceptedData = $this->getRecordByAphiaID((int) $data['valid_AphiaID']);
        if (! $acceptedData) {
            return $data;
        }

        $originalName = $data['scientificname'] ?? '';
        if ($data['authority'] ?? null) {
            $originalName .= " {$data['authority']}";
        }

        $note = "Original name provided: {$originalName} (unaccepted)";
        if ($taxon->notes) {
            if (! str_contains($taxon->notes, $note)) {
                $taxon->notes .= "\n".$note;
            }
        } else {
            $taxon->notes = $note;
        }

        return $acceptedData;
    }

    private function mapTaxonFields(Taxon $taxon, array $data): void
    {
        $taxon->aphia_id = $data['AphiaID'] ?? $taxon->aphia_id;
        $taxon->scientificname = $data['scientificname'] ?? $taxon->scientificname;
        $taxon->authority = $data['authority'] ?? $taxon->authority;
        $taxon->kingdom = $data['kingdom'] ?? $taxon->kingdom;
        $taxon->phylum = $data['phylum'] ?? $taxon->phylum;
        $taxon->class = $data['class'] ?? $taxon->class;
        $taxon->order = $data['order'] ?? $taxon->order;
        $taxon->family = $data['family'] ?? $taxon->family;
        $taxon->genus = $data['genus'] ?? $taxon->genus;
        $taxon->rank = $data['rank'] ?? $taxon->rank;
        $statusValue = $data['status'] ?? '';
        if ($statusValue instanceof Worms_Status) {
            $taxon->worms_status = $statusValue;
        } else {
            $taxon->worms_status = Worms_Status::tryFrom((string) $statusValue) ?? $taxon->worms_status;
        }
        $taxon->unacceptreason = $data['unacceptreason'] ?? $taxon->unacceptreason;
        $taxon->is_extinct = ! empty($data['isExtinct']);
        $taxon->url = $data['url'] ?? $taxon->url;

        if (isset($data['status'])) {
            $taxon->catalogue_status = Catalogue_Status::fromWormsData($data['status']);
        }

        $taxon->environments = Environment::fromWormsData($data);
    }

    final protected function wormsRequest(string $url, array $query = []): ?Response
    {
        return Http::connectTimeout($this->connectTimeoutSeconds)
            ->timeout($this->requestTimeoutSeconds)
            ->retry(2, 200, throw: false)
            ->get($url, $query);
    }
}
```

---

## 5. Filament Resource Pattern

### NisSuggestionResource (full)

**File:** `/home/toufa/dev/2026/apps/mamias/apps/app/Filament/Resources/NisSuggestions/NisSuggestionResource.php`
```php
<?php

namespace App\Filament\Resources\NisSuggestions;

use App\Filament\Resources\NisSuggestions\Pages\ListNisSuggestions;
use App\Filament\Resources\NisSuggestions\Pages\ViewNisSuggestion;
use App\Filament\Resources\NisSuggestions\Schemas\NisSuggestionInfolist;
use App\Filament\Resources\NisSuggestions\Tables\NisSuggestionsTable;
use App\Models\NisSuggestion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NisSuggestionResource extends Resource
{
    protected static ?string $model = NisSuggestion::class;

    protected static string|BackedEnum|null $navigationIcon = 'tabler-bulb';

    protected static ?string $modelLabel = 'Species Suggestion';

    protected static ?string $pluralModelLabel = 'Species Suggestions';

    protected static ?int $navigationSort = 4;

    protected static string|null|\UnitEnum $navigationGroup = 'MAMIAS database';

    public static function table(Table $table): Table
    {
        return NisSuggestionsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return NisSuggestionInfolist::configure($schema);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNisSuggestions::route('/'),
            'view' => ViewNisSuggestion::route('/{record}'),
        ];
    }
}
```

### Pages

**ListNisSuggestions:** `/home/toufa/dev/2026/apps/mamias/apps/app/Filament/Resources/NisSuggestions/Pages/ListNisSuggestions.php` -- extends `ListRecords`, has tabs for pending/approved/rejected/trashed.

**ViewNisSuggestion:** `/home/toufa/dev/2026/apps/mamias/apps/app/Filament/Resources/NisSuggestions/Pages/ViewNisSuggestion.php` -- extends `ViewRecord`, has header actions for approve/reject/delete/force-delete/restore.

### Schemas

- `/home/toufa/dev/2026/apps/mamias/apps/app/Filament/Resources/NisSuggestions/Schemas/NisSuggestionForm.php` -- static configurator (`configure(Schema $schema)` and `getComponents()`). Uses WoRMS search integration via `WormsService`.
- `/home/toufa/dev/2026/apps/mamias/apps/app/Filament/Resources/NisSuggestions/Schemas/NisSuggestionInfolist.php` -- static configurator, uses sections with species info, location (map), photos, documents, review.

### Tables

`/home/toufa/dev/2026/apps/mamias/apps/app/Filament/Resources/NisSuggestions/Tables/NisSuggestionsTable.php` -- static `configure(Table $table)`, standalone column helper methods (`getScientificNameColumn()`, etc.), filters, record actions.

### Actions

`/home/toufa/dev/2026/apps/mamias/apps/app/Filament/Resources/NisSuggestions/Actions/NisSuggestionActions.php` -- static factory methods `makeApproveAction()` and `makeRejectAction()` returning `Action` objects with modals and post-action logic (sending notifications, creating Taxon).

---

## 6. Model `booted()` Pattern

### Occurrence

**File:** `/home/toufa/dev/2026/apps/mamias/apps/app/Models/Occurrence.php`
```php
<?php

namespace App\Models;

use App\Casts\CoordinatesCast;
use App\Enums\AcforScale;
use App\Enums\OccurrenceStatus;
use App\Models\Traits\HasSpatialLocation;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

#[Fillable([
    'user_id',
    'intro_event_record_id',
    'location',
    'location_point',
    'depth',
    'acfor_scale',
    'habitats',
    'photo_paths',
    'notes',
    'observed_at',
    'status',
    'moderation_notes',
])]
class Occurrence extends Model
{
    use HasFactory, HasSpatialLocation;

    protected function casts(): array
    {
        return [
            'location' => CoordinatesCast::class,
            'depth' => 'float',
            'acfor_scale' => AcforScale::class,
            'habitats' => 'array',
            'photo_paths' => 'array',
            'observed_at' => 'datetime',
            'status' => OccurrenceStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Occurrence $occurrence): void {
            if (! $occurrence->isDirty('location')) {
                return;
            }

            $coords = $occurrence->location;
            $first = is_array($coords) ? ($coords[0] ?? null) : $coords;

            $occurrence->location_point = $first && isset($first['lat'], $first['lng'])
                ? Point::makeGeodetic((float) $first['lat'], (float) $first['lng'])
                : null;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function introEventRecord(): BelongsTo
    {
        return $this->belongsTo(IntroEventRecord::class);
    }

    public function taxon(): HasOneThrough
    {
        return $this->hasOneThrough(
            Taxon::class,
            IntroEventRecord::class,
            'id',
            'id',
            'intro_event_record_id',
            'taxon_id',
        );
    }
}
```

### IntroEventRecord

**File:** `/home/toufa/dev/2026/apps/mamias/apps/app/Models/IntroEventRecord.php`
```php
<?php

namespace App\Models;

use App\Enums\EstablishmentStatus;
use App\Enums\NisStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mattiverse\Userstamps\Traits\Userstamps;

#[Fillable(['taxon_id', 'first_introduction_year', 'first_country', 'nis_status', 'establishment_status', 'literature_id', 'notes'])]
class IntroEventRecord extends Model
{
    use HasFactory, Userstamps;

    protected function casts(): array
    {
        return [
            'first_country' => 'array',
            'nis_status' => NisStatus::class,
            'establishment_status' => EstablishmentStatus::class,
        ];
    }

    public function taxon(): BelongsTo
    {
        return $this->belongsTo(Taxon::class);
    }

    public function literature(): BelongsTo
    {
        return $this->belongsTo(Literature::class);
    }

    public function subregionRecords(): HasMany
    {
        return $this->hasMany(SubregionRecord::class, 'intro_event_id');
    }

    public function pathwayRecords(): HasMany
    {
        return $this->hasMany(PathwayRecord::class, 'intro_event_id');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(Occurrence::class, 'intro_event_record_id');
    }
}
```

---

## 7. Enum Pattern

### OccurrenceStatus

**File:** `/home/toufa/dev/2026/apps/mamias/apps/app/Enums/OccurrenceStatus.php`
```php
<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum OccurrenceStatus: string implements HasColor, HasIcon, HasLabel
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'Pending Review',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'tabler-clock',
            self::APPROVED => 'tabler-check',
            self::REJECTED => 'tabler-x',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }
}
```

### NisStatus

**File:** `/home/toufa/dev/2026/apps/mamias/apps/app/Enums/NisStatus.php`
```php
<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum NisStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case NIS = 'NIS';
    case Cryptogenic = 'Cryptogenic';
    case Questionable = 'Questionable';

    public function getLabel(): ?string
    {
        return $this->value;
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::NIS => 'Species introduced outside its native range',
            self::Cryptogenic => 'Species with unknown native range or pathway of introduction',
            self::Questionable => 'Species with unresolved taxonomic status or not verified by experts',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NIS => 'success',
            self::Cryptogenic => 'warning',
            self::Questionable => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::NIS => 'tabler-world',
            self::Cryptogenic => 'tabler-help',
            self::Questionable => 'tabler-alert-circle',
        };
    }
}
```

### Additional enums read:

- **AcforScale** (`/home/toufa/dev/2026/apps/mamias/apps/app/Enums/AcforScale.php`): string enum implementing `HasColor, HasLabel`. Has `getAnimalDescription()` and `getPlantDescription()` methods returning abundance descriptions.
- **Catalogue_Status** (`/home/toufa/dev/2026/apps/mamias/apps/app/Enums/Catalogue_Status.php`): string enum implementing `HasColor, HasIcon, HasLabel`. Has a static `fromWormsData()` method.
- **Environment** (`/home/toufa/dev/2026/apps/mamias/apps/app/Enums/Environment.php`): string enum implementing `HasColor, HasIcon, HasLabel`. Has `fromLabelOrValue()` and `fromWormsData()` static methods.

Pattern: All enums are `string` backed, implement Filament contracts (`HasLabel`, `HasColor`, `HasIcon`, `HasDescription`), use `match ($this)` in each method, and are stored as string values in the database (cast in models via `->casts()`).

---

## 8. Spatial Trait

**File:** `/home/toufa/dev/2026/apps/mamias/apps/app/Models/Traits/HasSpatialLocation.php`
```php
<?php

namespace App\Models\Traits;

use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait HasSpatialLocation
{
    public function initializeHasSpatialLocation(): void
    {
        $this->casts['location_point'] = Point::class;
    }

    public function scopeNear(Builder $query, float $lat, float $lng, float $meters): Builder
    {
        return $query->whereRaw(
            'ST_DWithin(location_point::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
            [$lng, $lat, $meters]
        );
    }

    public function scopeWithinBoundingBox(Builder $query, float $south, float $west, float $north, float $east): Builder
    {
        return $query
            ->whereRaw(
                'location_point && ST_MakeEnvelope(?, ?, ?, ?, 4326)',
                [$west, $south, $east, $north]
            )
            ->whereRaw(
                'ST_Intersects(location_point, ST_MakeEnvelope(?, ?, ?, ?, 4326))',
                [$west, $south, $east, $north]
            );
    }

    public function scopeOrderByDistance(Builder $query, float $lat, float $lng): Builder
    {
        return $query->orderByRaw(
            'location_point <-> ST_SetSRID(ST_MakePoint(?, ?), 4326)',
            [$lng, $lat]
        );
    }

    public function scopeWithDistanceFrom(Builder $query, float $lat, float $lng): Builder
    {
        return $query->addSelect(DB::raw(
            "ST_Distance(location_point::geography, ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326)::geography) as distance_meters"
        ));
    }
}
```

The trait:
- Uses `initializeHasSpatialLocation()` to register the `location_point` cast (automatically called by Laravel trait initialization).
- Provides `scopeNear`, `scopeWithinBoundingBox`, `scopeOrderByDistance`, `scopeWithDistanceFrom` using raw PostGIS queries.
- Uses the Clickbar Magellan `Point` type.

---

## 9. Config

**File:** `/home/toufa/dev/2026/apps/mamias/apps/config/services.php`
```php
<?php

return [

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'greenapi' => [
        'instance_id' => env('GREENAPI_INSTANCE_ID'),
        'token' => env('GREENAPI_TOKEN'),
    ],

    'cap' => [
        'site_key' => env('CAP_SITE_KEY'),
        'secret_key' => env('CAP_SECRET_KEY'),
        'public_url' => env('CAP_PUBLIC_URL', 'http://localhost:3000'),
        'internal_url' => env('CAP_INTERNAL_URL', 'http://cap:3000'),
    ],

];
```

External API keys configured: Postmark, Resend, SES, Slack, GreenAPI (WhatsApp), CAP (Citizen Science platform with internal/external URLs).

---

## 10. Livewire Pattern (Public Components)

### MySuggestions

**File:** `/home/toufa/dev/2026/apps/mamias/apps/app/Livewire/MySuggestions.php`
```php
<?php

namespace App\Livewire;

use App\Enums\LiteratureStatus;
use App\Filament\Resources\NisSuggestions\Schemas\NisSuggestionForm;
use App\Filament\Resources\NisSuggestions\Schemas\NisSuggestionInfolist;
use App\Filament\Resources\NisSuggestions\Tables\NisSuggestionsTable;
use App\Models\NisSuggestion;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\ViewAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class MySuggestions extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(NisSuggestion::where('user_id', auth()->id()))
            ->columns([
                NisSuggestionsTable::getScientificNameColumn(),
                NisSuggestionsTable::getAuthorityColumn(),
                NisSuggestionsTable::getStatusColumn(),
                NisSuggestionsTable::getMapColumn(),
                TextColumn::make('created_at')->label('Submitted')->dateTime()->sortable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modalHeading('Suggestion Details')
                    ->modalWidth(Width::SevenExtraLarge)
                    ->schema(NisSuggestionInfolist::getComponents()),
                $this->editAction(),
                $this->resubmitAction(),
            ])
            ->headerActions([$this->createAction()])
            ->defaultSort('created_at', 'desc');
    }

    public function createAction(): Action
    {
        return Action::make('create')
            ->label('Suggest New NIS')
            ->icon('tabler-bulb')
            ->button()
            ->color('primary')
            ->size('lg')
            ->modalHeading('Suggest a New NIS Species')
            ->modalWidth(Width::SevenExtraLarge)
            ->schema(NisSuggestionForm::getComponents())
            ->action(function (array $data): void {
                $suggestion = NisSuggestion::create([
                    ...$data,
                    'user_id' => auth()->id(),
                    'status' => LiteratureStatus::PENDING,
                ]);

                if ($literatures = $data['literatures'] ?? []) {
                    $suggestion->literatures()->sync($literatures);
                }

                Notification::make()
                    ->title('Suggestion submitted')
                    ->body('Thank you! Your suggestion will be reviewed by our team.')
                    ->success()
                    ->send();
            });
    }

    public function editAction(): Action
    {
        return Action::make('edit')
            ->label('Edit')
            ->icon('tabler-pencil')
            ->color('gray')
            ->visible(fn (NisSuggestion $record): bool => $record->status === LiteratureStatus::PENDING)
            ->modalHeading('Edit Suggestion')
            ->modalWidth(Width::SevenExtraLarge)
            ->schema(NisSuggestionForm::getComponents())
            ->fillForm(fn (NisSuggestion $record): array => $record->toArray())
            ->action(function (NisSuggestion $record, array $data): void {
                $record->update($data);

                if (array_key_exists('literatures', $data)) {
                    $record->literatures()->sync($data['literatures'] ?? []);
                }

                Notification::make()
                    ->title('Suggestion updated')
                    ->success()
                    ->send();
            });
    }

    public function resubmitAction(): Action
    {
        return Action::make('resubmit')
            ->label('Resubmit')
            ->icon('tabler-refresh')
            ->color('warning')
            ->visible(fn (NisSuggestion $record): bool => $record->status === LiteratureStatus::REJECTED)
            ->requiresConfirmation()
            ->modalHeading('Resubmit suggestion')
            ->modalDescription('A new suggestion will be created with the same data for re-review.')
            ->action(function (NisSuggestion $record): void {
                abort_unless($record->user_id === auth()->id(), 403);

                $resubmitted = NisSuggestion::create([
                    'user_id' => auth()->id(),
                    'aphia_id' => $record->aphia_id,
                    'suggested_scientific_name' => $record->suggested_scientific_name,
                    'authority' => $record->authority,
                    'worms_status' => $record->worms_status,
                    'suggested_common_name' => $record->suggested_common_name,
                    'location' => $record->location,
                    'depth' => $record->depth,
                    'photo_paths' => $record->photo_paths,
                    'document_paths' => $record->document_paths,
                    'status' => LiteratureStatus::PENDING,
                    'resubmitted_from_id' => $record->id,
                ]);

                $resubmitted->literatures()->sync($record->literatures->pluck('id'));

                Notification::make()
                    ->title('Suggestion resubmitted')
                    ->body("Your suggestion for \"{$resubmitted->suggested_scientific_name}\" has been resubmitted for review.")
                    ->success()
                    ->send();
            });
    }

    public function getStats(): array
    {
        $row = NisSuggestion::where('user_id', auth()->id())
            ->selectRaw('count(*) as total, count(*) filter (where status = ?) as pending, count(*) filter (where status = ?) as approved, count(*) filter (where status = ?) as rejected', [
                LiteratureStatus::PENDING->value,
                LiteratureStatus::APPROVED->value,
                LiteratureStatus::REJECTED->value,
            ])
            ->first();

        return [
            'total' => $row->total,
            'pending' => $row->pending,
            'approved' => $row->approved,
            'rejected' => $row->rejected,
        ];
    }

    public function getSpeciesLocations(?string $scientificName): array
    {
        if (! $scientificName) {
            return [];
        }

        return NisSuggestion::query()
            ->where('suggested_scientific_name', $scientificName)
            ->whereNotNull('location')
            ->get(['location'])
            ->flatMap(function (NisSuggestion $s): array {
                $data = json_decode($s->getRawOriginal('location'), true);
                if (isset($data['lat'], $data['lng'])) {
                    $data = [$data];
                }

                if (! is_array($data)) {
                    return [];
                }

                return collect($data)
                    ->filter(fn (array $c) => isset($c['lat'], $c['lng']))
                    ->map(fn (array $c) => ['lat' => (float) $c['lat'], 'lng' => (float) $c['lng']])
                    ->all();
            })
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('livewire.my-suggestions', [
            'stats' => $this->getStats(),
        ])->extends('app')->section('content');
    }
}
```

### PublicProfile

**File:** `/home/toufa/dev/2026/apps/mamias/apps/app/Livewire/PublicProfile.php`
```php
<?php

namespace App\Livewire;

use App\Enums\Subregion;
use App\Filament\Forms\Components\CountrySelectWithMedPriority;
use App\Services\WhatsAppService;
use App\Services\WormsService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Nakanakaii\FilamentCountries\Forms\Components\PhoneInput;

class PublicProfile extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public ?array $data = [];

    public bool $isEditing = false;

    public function mount(): void
    {
        $this->form->fill(auth()->user()->attributesToArray());
    }

    public function form(Schema $schema): Schema

...11511 bytes truncated...

The tool call succeeded but the output was truncated. Full output saved to: /home/toufa/.local/share/opencode/tool-output/tool_ef947997b0011uBLiNp2d0jq4O
Use the Task tool to have explore agent process this file with Grep and Read (with offset/limit). Do NOT read the full file yourself - delegate to save context.
```

**Tool: task**

**Input:**
```json
{
  "description": "Deep-dive Filament resource & table patterns",
  "subagent_type": "explore",
  "prompt": "Very thorough exploration of /home/toufa/dev/2026/apps/mamias/apps focusing on Filament UI patterns:\n\n1. Read app/Filament/Resources/UserResource.php fully\n2. Read app/Filament/Resources/UserResource/Schemas/UserForm.php fully\n3. Read app/Filament/Resources/UserResource/Tables/UsersTable.php fully\n4. Read app/Filament/Pages/Auth/Login.php fully\n5. Read app/Providers/Filament/MamiasPanelProvider.php fully\n6. Check what widgets exist in app/Filament/Widgets/ \n7. Read the NisSuggestionActions file (search for it)\n8. Read the OccurrenceActions file (search for it)\n9. Read an Infolist entry like OccurrenceLocationsMapEntry if it exists\n10. Check routes/breadcrumbs.php fully\n11. Read app/Models/User.php fully\n\nReturn ALL file contents completely. I need exact code patterns for implementation."
}
```

**Output:**
```
<task id="ses_106ba96abffeAEeMqMX06Fm1nq" state="completed">
<task_result>
All 11 items have been fully read. Here is the complete output:

---

## 1. `/home/toufa/dev/2026/apps/mamias/apps/app/Filament/Resources/Users/UserResource.php`

```php
<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Daljo25\FilamentTablerIcons\Enums\TablerIcon;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = TablerIcon::Users;

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    protected static ?string $recordTitleAttribute = 'Users';

    protected static ?string $navigationLabel = 'Users';

    protected static string|null|\UnitEnum $navigationGroup = 'Use management';

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
```

---

## 2. `/home/toufa/dev/2026/apps/mamias/apps/app/Filament/Resources/Users/Schemas/UserForm.php`

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Subregion;
use App\Filament\Forms\Components\CountrySelectWithMedPriority;
use App\Services\WhatsAppService;
use App\Services\WormsService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Nakanakaii\FilamentCountries\Forms\Components\PhoneInput;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal Details')
                    ->description('Identity and contact information.')
                    ->icon('tabler-id')
                    ->compact()
                    ->collapsible()
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                Select::make('title')
                                    ->label('Title')
                                    ->options([
                                        'Mr' => 'Mr',
                                        'Mrs' => 'Mrs',
                                        'Ms' => 'Ms',
                                        'Dr' => 'Dr',
                                        'Prof' => 'Prof',
                                    ])
                                    ->default('Mr')
                                    ->native(false)
                                    ->required()
                                    ->columnSpan(3),
                                TextInput::make('first_name')
                                    ->label('First Name')
                                    ->prefixIcon('tabler-user')
                                    ->placeholder('Jane')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(4),
                                TextInput::make('last_name')
                                    ->label('Last Name')
                                    ->prefixIcon('tabler-user')
                                    ->placeholder('Doe')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(5),
                            ]),
                        Grid::make(3)
                            ->schema([
                                Grid::make(1)
                                    ->schema([
                                        PhoneInput::make('phone')
                                            ->label('Phone Number')
                                            ->placeholder('+xxx xxx xxx xxx')
                                            ->tel()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (?string $state, Set $set) {
                                                if (blank($state)) {
                                                    $set('has_whatsapp', false);
                                                    return;
                                                }
                                                $service = app(WhatsAppService::class);
                                                $service->forgetCache($state);
                                                $set('has_whatsapp', $service->isRegistered($state));
                                            })
                                            ->extraInputAttributes(fn (Get $get) => [
                                                'style' => (bool) $get('has_whatsapp') ? 'border: 2px solid #22c55e !important;' : '',
                                            ])
                                            ->hintIcon(
                                                'tabler-brand-whatsapp',
                                                tooltip: 'Include country code (e.g., +1 for USA). If the phone number has WhatsApp it will turn green',
                                            )
                                            ->hintColor(fn (Get $get) => (bool) $get('has_whatsapp') ? 'success' : 'gray')
                                            ->suffixAction(
                                                Action::make('checkWhatsapp')
                                                    ->icon('tabler-brand-whatsapp')
                                                    ->color(fn (Get $get) => (bool) $get('has_whatsapp') ? 'success' : 'gray')
                                                    ->tooltip('Check WhatsApp registration')
                                                    ->action(function (Get $get, Set $set) {
                                                        $phone = $get('phone');
                                                        if (blank($phone)) { return; }
                                                        $service = app(WhatsAppService::class);
                                                        $service->forgetCache($phone);
                                                        $set('has_whatsapp', $service->isRegistered($phone));
                                                    }),
                                            ),
                                        Hidden::make('has_whatsapp'),
                                    ]),
                                CountrySelectWithMedPriority::make('country')
                                    ->label('Country')
                                    ->displayFlags(true)
                                    ->searchable()
                                    ->placeholder('Select your country'),
                                Select::make('roles')
                                    ->label('Roles?')
                                    ->relationship('roles', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->hintIcon('heroicon-m-question-mark-circle',
                                        tooltip: 'Assign one or more roles to this user.'),
                            ]),
                    ]),
                Section::make('Account Information')
                    ->description('Login credentials and verification status.')
                    ->icon('tabler-user-circle')
                    ->compact()
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('email')
                                    ->label('Email Address')
                                    ->prefixIcon('tabler-mail')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('name@example.com'),
                                TextInput::make('password')
                                    ->label('Password?')
                                    ->password()
                                    ->revealable()
                                    ->prefixIcon('tabler-lock')
                                    ->placeholder('Leave blank to keep current password.')
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->required(fn ($operation) => $operation === 'create')
                                    ->maxLength(255)
                                    ->hintIcon('heroicon-m-question-mark-circle',
                                        tooltip: 'Minimum 8 characters recommended.'),
                                DateTimePicker::make('email_verified_at')
                                    ->label('Email Verified At')
                                    ->prefixIcon('tabler-mail-check')
                                    ->native(false)
                                    ->seconds(false)
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Professional Focus')
                    ->description('Area of expertise and geographic interest.')
                    ->icon('tabler-school')
                    ->compact()
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('taxonomic_area')
                                    ->label('Taxonomic Areas?')
                                    ->multiple()
                                    ->searchable()
                                    ->options(function (): array {
                                        try {
                                            return app(WormsService::class)->getPhyla();
                                        } catch (\Throwable) {
                                            return [];
                                        }
                                    })
                                    ->placeholder('Select taxonomic areas...')
                                    ->hintIcon('heroicon-m-question-mark-circle',
                                        tooltip: 'Phyla of scientific interest (sourced from WoRMS).')
                                    ->helperText('Phyla of scientific interest (sourced from WoRMS).')
                                    ->columnSpanFull(),
                                Textarea::make('bio')
                                    ->label('Biography')
                                    ->rows(3)
                                    ->placeholder('A short professional biography...')
                                    ->maxLength(1000)
                                    ->autosize()
                                    ->columnSpanFull(),
                                Fieldset::make('Geographic Areas')
                                    ->schema([
                                        Select::make('subregions')
                                            ->label('EcAp Subregion?')
                                            ->multiple()
                                            ->options(function (): array {
                                                return collect(Subregion::cases())
                                                    ->mapWithKeys(fn (Subregion $case,
                                                    ) => [$case->value => $case->getLabel()])
                                                    ->all();
                                            })
                                            ->searchable()
                                            ->placeholder('Select subregions...')
                                            ->hintIcon('heroicon-m-question-mark-circle',
                                                tooltip: 'Select broad geographic regions of interest.')
                                            ->helperText('Select broad geographic regions of interest.'),
                                        CountrySelectWithMedPriority::make('countries')
                                            ->label('Countries?')
                                            ->displayFlags(true)
                                            ->searchable()
                                            ->multiple()
                                            ->placeholder('Select countries...')
                                            ->hintIcon('heroicon-m-question-mark-circle',
                                                tooltip: 'Select specific countries of interest.')
                                            ->helperText('Select specific countries of interest.'),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
```

---

## 3. `/home/toufa/dev/2026/apps/mamias/apps/app/Filament/Resources/Users/Tables/UsersTable.php`

```php
<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\ColumnManagerLayout;
use Filament\Tables\Table;
use Nakanakaii\FilamentCountries\Tables\Columns\CountryColumn;
use Nakanakaii\FilamentCountries\Tables\Filters\CountryFilter;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->columnManagerLayout(ColumnManagerLayout::Modal)
            ->modifyQueryUsing(fn ($query) => $query->with('roles'))
            ->paginationPageOptions([10, 25, 50, 100])
            ->columns([
                TextColumn::make('ID')
                    ->rowIndex()
                    ->label('No.'),
                ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->circular(),
                TextColumn::make('title')
                    ->searchable()->sortable(),
                TextColumn::make('first_name')
                    ->searchable()->sortable(),
                TextColumn::make('last_name')
                    ->searchable()->sortable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('roles')
                    ->label('Roles')
                    ->getStateUsing(function ($record) {
                        return $record->roles->pluck('name')->join(', ');
                    })
                    ->badge()
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->url(fn ($record) => $record->phone ? 'tel:'.$record->phone : null)
                    ->icon('tabler-phone')
                    ->iconColor('gray'),
                IconColumn::make('has_whatsapp')
                    ->label('WhatsApp')
                    ->icon('tabler-brand-whatsapp')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->url(fn ($record) => $record->has_whatsapp && $record->phone
                        ? 'whatsapp://send?phone='.preg_replace('/\D/', '', $record->phone)
                        : null)
                    ->tooltip(fn ($record) => $record->has_whatsapp ? 'Open WhatsApp' : 'Not on WhatsApp'),
                CountryColumn::make('country')
                    ->displayFlags(true)
                    ->hideName(false)
                    ->imageFlags()
                    ->searchable()->sortable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                CountryFilter::make('country'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modalWidth('6xl')
                    ->modalHeading(fn ($record,
                    ) => trim(($record->first_name ?? '').' '.($record->last_name ?? '')) ?: 'User'),
                EditAction::make()
                    ->modalWidth('7xl'),
                DeleteAction::make()
                    ->modalWidth('3xl'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
```

---

## 4. `/home/toufa/dev/2026/apps/mamias/apps/app/Filament/Pages/Auth/Login.php`

```php
<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Filament\Forms\Components\CapField;
use App\Filament\Pages\Auth\Concerns\ValidatesCapToken;
use App\Support\FilamentAuthRedirect;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Schema;

class Login extends BaseLogin
{
    use HasCustomLayout, ValidatesCapToken;

    public ?string $cap_token = null;

    public function mount(): void
    {
        if (auth()->check()) {
            $this->redirect($this->getRedirectUrl(), navigate: true);
            return;
        }
        parent::mount();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
                CapField::make('cap_token')
                    ->columnSpanFull(),
            ]);
    }

    public function authenticate(): ?LoginResponseContract
    {
        $data = $this->form->getState();
        $this->validateCapToken($data['cap_token'] ?? null);
        return parent::authenticate();
    }

    protected function getRedirectUrl(): string
    {
        return FilamentAuthRedirect::for(auth()->user());
    }
}
```

---

## 5. `/home/toufa/dev/2026/apps/mamias/apps/app/Providers/Filament/MamiasPanelProvider.php`

```php
<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EmailVerificationPrompt;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\ComposerDependencies;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\HealthCheckResults;
use App\Filament\Pages\NpmDependencies;
use App\Filament\Widgets\MamiasInfoWidget;
use App\Http\Middleware\RedirectIfNotPanelUser;
use AzGasim\FilamentUnsavedChangesModal\FilamentUnsavedChangesModalPlugin;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use BinaryBuilds\CommandRunner\CommandRunnerPlugin;
use CmsMulti\FilamentClearCache\FilamentClearCachePlugin;
use Devonab\FilamentEasyFooter\EasyFooterPlugin;
use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;
use Elemind\FilamentECharts\FilamentEChartsPlugin;
use Filament\Actions\Action;
use Filament\Enums\ThemeMode;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentAsset;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\View\View;
use JeffersonGoncalves\Filament\RefreshSidebar\RefreshSidebarPlugin;
use lockscreen\FilamentLockscreen\Lockscreen;
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;
use pxlrbt\FilamentSpotlight\SpotlightPlugin;
use ShuvroRoy\FilamentSpatieLaravelHealth\FilamentSpatieLaravelHealthPlugin;
use YousefAman\ModalRepeater\ModalRepeaterPlugin;

class MamiasPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        FilamentAsset::register([
            Js::make('app-scripts', Vite::asset('resources/js/app.js')),
        ]);
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('mamias')
            ->path('mamias')
            ->darkMode(false)
            ->defaultThemeMode(ThemeMode::Light)
            ->brandName('Filament Demo')
            ->brandLogo(asset('images/mamias.png'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('images/favicon.png'))
            ->maxContentWidth(Width::Full)
            ->spa(hasPrefetching: true)
            ->spaUrlExceptions(fn (): array => [
                url('/mamias'),
                url('/'),
            ])
            ->unsavedChangesAlerts()
            ->sidebarWidth('15rem')
            ->globalSearch(false)
            ->databaseTransactions()
            ->databaseNotifications()
            ->viteTheme('resources/css/filament/mamias/theme.css')
            ->font('Roboto', provider: GoogleFontProvider::class)
            ->login(Login::class)
            ->registration(Register::class)
            ->passwordReset()
            ->emailVerification(EmailVerificationPrompt::class)
            ->plugins([
                SpotlightPlugin::make(),
                RefreshSidebarPlugin::make(),
                CommandRunnerPlugin::make()
                    ->authorize(fn (): bool => auth()->user()->hasRole('super_admin'))
                    ->navigationGroup('System')
                    ->navigationIcon('tabler-alert-triangle'),
                FilamentSpatieLaravelHealthPlugin::make()
                    ->usingPage(HealthCheckResults::class)
                    ->authorize(fn (): bool => auth()->user()->hasRole('super_admin')),
                EnvironmentIndicatorPlugin::make()
                    ->color(fn () => match (app()->environment()) {
                        'production' => null,
                        'staging' => Color::Hex('#FF6B35'),
                        default => Color::Hex('#018d9a'),
                    })
                    ->showDebugModeWarningInProduction(),
                Lockscreen::make()
                    ->usingCustomTableColumns('email', 'password')
                    ->enableRateLimit()
                    ->enableIdleTimeout()
                    ->disableDisplayName('name')
                    ->icon('tabler-file-text')
                    ->enablePlugin(),
                FilamentDeveloperLoginsPlugin::make()
                    ->enabled(app()->environment('local'))
                    ->users([
                        'Admin' => 'atef.ouerghi@spa-rac.org',
                        'Scientist' => 'scientist@mamias.local',
                        'Public User' => 'atef.ouerghi@gmail.com',
                    ]),
                AuthUIEnhancerPlugin::make()
                    ->showEmptyPanelOnMobile(true)
                    ->mobileFormPanelPosition('top')
                    ->formPanelPosition('right')
                    ->formPanelWidth('70%')
                    ->emptyPanelView('auth.empty-panel'),
                EasyFooterPlugin::make()
                    ->withSentence(new HtmlString('<img src="'.asset('images/sparac.png').'" style="margin-right:.4rem;" alt="Laravel Logo" width="150" height="20"> SPA/RAC'))
                    ->withBorder()
                    ->withLinks([
                        ['title' => 'Legal notice', 'url' => '#'],
                        ['title' => 'Terms of use', 'url' => '#'],
                        ['title' => 'Cookies policy', 'url' => '#'],
                    ]),
                FilamentEChartsPlugin::make(),
                FilamentClearCachePlugin::make(),
                FilamentUnsavedChangesModalPlugin::make()
                    ->modalWidth('xl')
                    ->modalIcon('OutlinedExclamationTriangle')
                    ->modalIconColor('danger')
                    ->stayButtonColor('gray')
                    ->leaveButtonColor('warning'),
                ModalRepeaterPlugin::make(),
                FilamentShieldPlugin::make()
                    ->navigationGroup('Use management')
                    ->navigationSort(-1)
                    ->navigationIcon('tabler-shield-check')
                    ->activeNavigationIcon('tabler-shield-check')
                    ->gridColumns([
                        'default' => 1, 'sm' => 2, 'lg' => 3,
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1, 'sm' => 2, 'lg' => 4,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1, 'sm' => 2,
                    ]),
            ])
            ->colors([
                'primary' => [
                    50 => '#f0f9fb',
                    100 => '#d9f0f4',
                    200 => '#b7e2ea',
                    300 => '#85ccd9',
                    400 => '#4cafbf',
                    500 => '#00899d',
                    600 => '#007a8c',
                    700 => '#006b7a',
                    800 => '#005f6b',
                    900 => '#004e59',
                    950 => '#00353d',
                ],
                'gray' => Color::Slate,
                'danger' => Color::Rose,
                'info' => [
                    50 => '#eff6ff',
                    100 => '#dbeafe',
                    200 => '#bfdbfe',
                    300 => '#93c5fd',
                    400 => '#60a5fa',
                    500 => '#005f98',
                    600 => '#004e7c',
                    700 => '#003d61',
                    800 => '#003070',
                    900 => '#00254a',
                    950 => '#001838',
                ],
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])

            ->userMenuItems([
                Action::make('home')
                    ->label('Public site')
                    ->url(fn (): string => url('/'))
                    ->icon('heroicon-o-home'),
                Action::make('decomposer')
                    ->label('Decomposer')
                    ->url(fn (): string => url('mamias/decompose'))
                    ->icon('heroicon-o-cog-6-tooth'),
            ])

            ->navigationGroups([
                NavigationGroup::make('Dashboard'),
                NavigationGroup::make('Use management'),
                NavigationGroup::make('MAMIAS database'),
                NavigationGroup::make('System'),
                NavigationGroup::make('Settings'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                ComposerDependencies::class,
                NpmDependencies::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                MamiasInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                RedirectIfNotPanelUser::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                'panels::body.start',
                fn (): View => view('filament.mobile-notice'),
            );
    }
}
```

---

## 6. `/home/toufa/dev/2026/apps/mamias/apps/app/Filament/Widgets/` -- 9 entries

```
CatalogueEnvironmentChart.php
CatalogueStatsWidget.php
MamiasInfoWidget.php
PendingOccurrencesTableWidget.php
PendingOccurrencesWidget.php
PhylumByKingdomChart.php
SpeciesByKingdomChart.php
SpeciesByPhylumChart.php
WormsFetchProgressWidget.php
```

---

## 7. `/home/toufa/dev/2026/apps/mamias/apps/app/Filament/Resources/NisSuggestions/Actions/NisSuggestionActions.php`

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources\NisSuggestions\Actions;

use App\Enums\Catalogue_Status;
use App\Enums\LiteratureStatus;
use App\Models\NisSuggestion;
use App\Models\Taxon;
use App\Notifications\NisSuggestionApproved;
use App\Notifications\NisSuggestionRejected;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class NisSuggestionActions
{
    public static function makeApproveAction(?Closure $afterAction = null): Action
    {
        return Action::make('approve')
            ->label(fn (NisSuggestion $record): string => $record->status === LiteratureStatus::APPROVED ? 'Re-approve' : 'Approve')
            ->icon('tabler-check')
            ->color('success')
            ->visible(fn (NisSuggestion $record): bool => $record->status !== LiteratureStatus::APPROVED)
            ->modalHeading(fn (NisSuggestion $record): string => $record->status === LiteratureStatus::APPROVED ? 'Re-approve suggestion' : 'Approve suggestion')
            ->modalSubmitActionLabel('Create Taxon & Approve')
            ->schema([
                TextInput::make('scientificname')
                    ->label('Scientific Name')
                    ->default(fn (NisSuggestion $record): string => $record->suggested_scientific_name)
                    ->required(),
                TextInput::make('authority')
                    ->label('Authority')
                    ->default(fn (NisSuggestion $record): ?string => $record->authority),
                Select::make('catalogue_status')
                    ->label('Catalogue Status')
                    ->options(Catalogue_Status::class)
                    ->default(Catalogue_Status::not_checked)
                    ->required(),
                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3),
            ])
            ->action(function (NisSuggestion $record, array $data) use ($afterAction): void {
                $alreadyExists = Taxon::where('scientificname', $data['scientificname'])->exists();
                $taxonId = null;

                if (! $alreadyExists) {
                    $taxon = Taxon::create([
                        'scientificname' => $data['scientificname'],
                        'authority' => $data['authority'],
                        'catalogue_status' => $data['catalogue_status'],
                        'notes' => $data['notes'],
                    ]);
                    $taxonId = $taxon->id;
                }

                $record->update([
                    'status' => LiteratureStatus::APPROVED,
                    'taxon_id' => $taxonId,
                    'rejection_reason' => null,
                ]);

                $record->user?->notify(new NisSuggestionApproved($record));

                $body = $alreadyExists
                    ? "\"{$data['scientificname']}\" already exists in the catalogue."
                    : "Taxon \"{$data['scientificname']}\" was created in the catalogue.";

                Notification::make()
                    ->title('Suggestion approved')
                    ->body($body)
                    ->success()
                    ->send();

                $afterAction?->call($record, $data);
            });
    }

    public static function makeRejectAction(?Closure $afterAction = null): Action
    {
        return Action::make('reject')
            ->label(fn (NisSuggestion $record): string => $record->status === LiteratureStatus::REJECTED ? 'Re-reject' : 'Reject')
            ->icon('tabler-x')
            ->color('danger')
            ->visible(fn (NisSuggestion $record): bool => $record->status !== LiteratureStatus::REJECTED)
            ->schema([
                Textarea::make('rejection_reason')
                    ->label('Rejection reason')
                    ->required()
                    ->rows(3)
                    ->placeholder('Explain why the suggestion is being rejected...'),
            ])
            ->action(function (NisSuggestion $record, array $data) use ($afterAction): void {
                $record->update([
                    'status' => LiteratureStatus::REJECTED,
                    'rejection_reason' => $data['rejection_reason'],
                ]);

                $record->user?->notify(new NisSuggestionRejected($record));

                Notification::make()
                    ->title('Suggestion rejected')
                    ->warning()
                    ->send();

                $afterAction?->call($record, $data);
            });
    }
}
```

---

## 8. `/home/toufa/dev/2026/apps/mamias/apps/app/Filament/Resources/Occurrences/Actions/OccurrenceActions.php`

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources\Occurrences\Actions;

use App\Enums\OccurrenceStatus;
use App\Models\Occurrence;
use App\Notifications\OccurrenceApproved;
use App\Notifications\OccurrenceRejected;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class OccurrenceActions
{
    public static function makeApproveAction(?Closure $afterAction = null): Action
    {
        return Action::make('approve')
            ->label(fn (Occurrence $record): string => $record->status === OccurrenceStatus::APPROVED ? 'Re-approve' : 'Approve')
            ->icon('tabler-check')
            ->color('success')
            ->visible(fn (Occurrence $record): bool => $record->status !== OccurrenceStatus::APPROVED)
            ->modalHeading(fn (Occurrence $record): string => $record->status === OccurrenceStatus::APPROVED ? 'Re-approve occurrence' : 'Approve occurrence')
            ->modalSubmitActionLabel('Approve Occurrence')
            ->schema([
                Textarea::make('moderation_notes')
                    ->label('Moderation notes (optional)')
                    ->rows(3),
            ])
            ->action(function (Occurrence $record, array $data) use ($afterAction): void {
                $record->update([
                    'status' => OccurrenceStatus::APPROVED,
                    'moderation_notes' => $data['moderation_notes'] ?? null,
                ]);

                $record->user?->notify(new OccurrenceApproved($record));

                Notification::make()
                    ->title('Occurrence approved')
                    ->success()
                    ->send();

                $afterAction?->call($record, $data);
            });
    }

    public static function makeRejectAction(?Closure $afterAction = null): Action
    {
        return Action::make('reject')
            ->label(fn (Occurrence $record): string => $record->status === OccurrenceStatus::REJECTED ? 'Re-reject' : 'Reject')
            ->icon('tabler-x')
            ->color('danger')
            ->visible(fn (Occurrence $record): bool => $record->status !== OccurrenceStatus::REJECTED)
            ->schema([
                Textarea::make('moderation_notes')
                    ->label('Rejection reason')
                    ->required()
                    ->rows(3)
                    ->placeholder('Explain why the occurrence is being rejected...'),
            ])
            ->action(function (Occurrence $record, array $data) use ($afterAction): void {
                $record->update([
                    'status' => OccurrenceStatus::REJECTED,
                    'moderation_notes' => $data['moderation_notes'],
                ]);

                $record->user?->notify(new OccurrenceRejected($record));

                Notification::make()
                    ->title('Occurrence rejected')
                    ->warning()
                    ->send();

                $afterAction?->call($record, $data);
            });
    }
}
```

---

## 9. `/home/toufa/dev/2026/apps/mamias/apps/app/Filament/Resources/Occurrences/Schemas/OccurrenceLocationsMapEntry.php`

```php
<?php

namespace App\Filament\Resources\Occurrences\Schemas;

use App\Models\Occurrence;
use EduardoRibeiroDev\FilamentLeaflet\Infolists\MapEntry;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;

class OccurrenceLocationsMapEntry extends MapEntry
{
    public function getPickMarkerData(): array
    {
        $record = $this->getRecord();

        if (! $record instanceof Occurrence) {
            return parent::getPickMarkerData();
        }

        $coords = $record->location;
        $first = is_array($coords) ? ($coords[0] ?? null) : $coords;

        if (! $first || ! isset($first['lat'], $first['lng'])) {
            return parent::getPickMarkerData();
        }

        $pickMarker = Marker::make((float) $first['lat'], (float) $first['lng'])
            ->red()
            ->tooltipContent("{$first['lat']}, {$first['lng']}")
            ->tooltipOptions(['direction' => 'top'])
            ->popupContent("{$record->taxon?->scientificname}<br>Lat: {$first['lat']}<br>Lng: {$first['lng']}");

        return $pickMarker->toArray();
    }
}
```

---

## 10. `/home/toufa/dev/2026/apps/mamias/apps/routes/breadcrumbs.php`

```php
<?php

// routes/breadcrumbs.php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

// Home
Breadcrumbs::for('home', function (BreadcrumbTrail $trail) {
    $trail->push('Home', route('home'));
});

// Home > About MAMIAS
Breadcrumbs::for('about', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('About', route('about'));
});

// My profile
Breadcrumbs::for('profile', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Profile', route('profile'));
});

// My Bibliographic References
Breadcrumbs::for('references', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('My Bibliographic References', route('references'));
});

// My Species Reports
Breadcrumbs::for('my-species-reports', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('My Species Reports', route('my-species-reports'));
});

// My Species Suggestions
Breadcrumbs::for('suggestions', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('My Suggestions', route('suggestions'));
});
```

---

## 11. `/home/toufa/dev/2026/apps/mamias/apps/app/Models/User.php`

```php
<?php

declare(strict_types=1);

namespace App\Models;

/**
 * User Model
 *
 * Represents a user in the MAMIAS system with authentication, profile, and Filament admin panel support.
 *
 * Features:
 * - Authentication with email verification required
 * - User profile attributes (name, location, contact info, bio)
 * - Avatar generation via UI Avatars API
 * - Filament admin panel integration with role-based access control
 * - Automatic name synchronization from first/last name fields
 *
 * @property int $id
 * @property string $name Full name (auto-generated from first_name + last_name)
 * @property string $email User email address (unique, verified required)
 * @property string $password Hashed password
 * @property string|null $first_name User first name
 * @property string|null $last_name User last name
 * @property string|null $phone Phone number
 * @property bool $has_whatsapp Whether user has WhatsApp
 * @property string|null $country Country of residence
 * @property string|null $taxonomic_area Taxonomic research area
 * @property string|null $subregions Geographic subregions (comma-separated or JSON)
 * @property string|null $countries Countries of focus (comma-separated or JSON)
 * @property string|null $title Job title or professional title
 * @property string|null $bio User biography
 * @property \DateTime|null $email_verified_at Timestamp when email was verified
 * @property \DateTime $created_at Timestamp when created
 * @property \DateTime $updated_at Timestamp when last updated
 * @property string|null $remember_token Token for "remember me" functionality
 */

use App\Notifications\VerifyEmail;
use Database\Factories\UserFactory;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'title',
    'email',
    'password',
    'first_name',
    'last_name',
    'taxonomic_area',
    'subregions',
    'countries',
    'phone',
    'has_whatsapp',
    'bio',
    'country',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar, HasName, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Bootstrap the model - run during model initialization.
     *
     * Automatically synchronizes the `name` attribute by concatenating first_name and last_name.
     * This observer runs whenever the user is being saved, ensuring the full name is always kept in sync.
     * Falls back to email local-part if both first and last names are empty.
     */
    protected static function booted(): void
    {
        static::saving(function ($user) {
            if ($user->isDirty(['first_name', 'last_name'])) {
                $user->name = $user->getComputedFullName();
            }
        });
    }

    protected function getComputedFullName(): string
    {
        $fullName = trim("{$this->first_name} {$this->last_name}");

        if ($fullName !== '') {
            return $fullName;
        }

        return str($this->email)->before('@')->toString();
    }

    public function getFormattedNameWithRoles(): ?string
    {
        $name = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        if ($name === '') {
            return null;
        }

        $roles = $this->getRoleNames()->all();

        if (! empty($roles)) {
            $name .= ' ('.implode(', ', $roles).')';
        }

        return $name;
    }

    /**
     * Get the user's avatar URL for Filament admin panel.
     *
     * Generates a dynamic avatar using UI Avatars API based on the user's full name.
     * The avatar is generated with white text (#FFFFFF) on a teal background (#018d9a).
     *
     * This implements the HasAvatar contract required by Filament.
     *
     * @return string|null Absolute URL to user's avatar image, or null if not available
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return 'https://ui-avatars.com/api/?name='.urlencode($this->getFilamentName()).'&color=FFFFFF&background=018d9a';
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->getFilamentAvatarUrl();
    }

    /**
     * Get the display name for Filament admin panel.
     *
     * Returns the user's full name (first_name + last_name) as displayed in the Filament UI.
     * Falls back to email local-part if both first and last names are empty.
     *
     * This implements the HasName contract required by Filament.
     *
     * @return string User's full name formatted for display
     */
    public function getFilamentName(): string
    {
        return $this->getComputedFullName();
    }

    /**
     * Determine if user can access the Filament admin panel.
     *
     * Currently allows all authenticated users to access the admin panel.
     *
     * Uncomment and customize the admin panel check section to restrict access:
     *   - Require specific email domain (e.g., @yourdomain.com)
     *   - Require verified email address (MustVerifyEmail contract)
     *   - Implement role-based access control
     *
     * This implements the FilamentUser contract required by Filament.
     *
     * @param  Panel  $panel  The Filament panel to check access for
     * @return bool True if user is allowed to access the panel, false otherwise
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'mamias') {
            return false;
        }

        if (app()->environment('local')) {
            return true;
        }

        return $this->hasAnyRole(['super_admin', 'scientist']);
    }

    public function sendEmailVerificationNotification(): void
    {
        $notification = app(VerifyEmail::class);
        $notification->url = Filament::getVerifyEmailUrl($this);
        $this->notify($notification);
    }

    /**
     * Get the attributes that should be cast.
     *
     * Casts email_verified_at to datetime object and hashes password before storage.
     * Password is automatically hashed using Laravel's hashing algorithm on save.
     *
     * @return array<string, string> Type cast mapping for model attributes
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'has_whatsapp' => 'boolean',
            'taxonomic_area' => 'array',
            'subregions' => 'array',
            'countries' => 'array',
        ];
    }

    public function literatures(): HasMany
    {
        return $this->hasMany(Literature::class, 'created_by');
    }

    public function nisSuggestions(): HasMany
    {
        return $this->hasMany(NisSuggestion::class);
    }

    public function introEventRecords(): HasMany
    {
        return $this->hasMany(IntroEventRecord::class, 'created_by');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(Occurrence::class);
    }
}
```

---

## Bonus: Additional files read for full pattern context

### `/home/toufa/dev/2026/apps/mamias/apps/app/Filament/Resources/Users/Schemas/UserInfolist.php`

```php
<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Services\WhatsAppService;
use Filament\Actions\Action;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Nakanakaii\Countries\Countries;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'md' => 2, 'xl' => 2])
            ->components([
                Section::make('Personal Details')
                    ->description('Identity and contact information.')
                    ->icon('tabler-id')
                    ->collapsible()
                    ->columnSpan(['default' => 1, 'md' => 1])
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 5])
                            ->schema([
                                ImageEntry::make('avatar_url')->hiddenLabel()->circular()->size(40)->columnSpan(1),
                                TextEntry::make('title')->label('Title')->badge()->color('info')->placeholder('--')->columnSpan(1),
                                TextEntry::make('first_name')->label('First Name')->size(TextSize::Large)->weight(FontWeight::Bold)->placeholder('--')->columnSpan(1),
                                TextEntry::make('last_name')->label('Last Name')->size(TextSize::Large)->weight(FontWeight::Bold)->placeholder('--')->columnSpan(1),

...7641 bytes truncated...

The tool call succeeded but the output was truncated. Full output saved to: /home/toufa/.local/share/opencode/tool-output/tool_ef946eb3b001pqrGL2KKWKRe7v
Use the Task tool to have explore agent process this file with Grep and Read (with offset/limit). Do NOT read the full file yourself - delegate to save context.
```

**Tool: webfetch**

**Input:**
```json
{
  "url": "https://github.com/riparias/gbif-alert",
  "format": "markdown"
}
```

**Output:**
```
                       GitHub - riparias/gbif-alert: GBIF Alert is a GBIF occurrence based alert system. · GitHub                                             

[Skip to content](#start-of-content)   

## Navigation Menu

Toggle navigation

[](/)

[Sign in](/login?return_to=https%3A%2F%2Fgithub.com%2Friparias%2Fgbif-alert)

Appearance settings

  

-   Platform
    
    -   AI CODE CREATION
        
        -   [
            
            GitHub CopilotWrite better code with AI
            
            ](https://github.com/features/copilot)
        -   [
            
            GitHub Copilot appDirect agents from issue to merge
            
            ](https://github.com/features/ai/github-app)
        -   [
            
            MCP RegistryNewIntegrate external tools
            
            ](https://github.com/mcp)
        
    -   DEVELOPER WORKFLOWS
        
        -   [
            
            ActionsAutomate any workflow
            
            ](https://github.com/features/actions)
        -   [
            
            CodespacesInstant dev environments
            
            ](https://github.com/features/codespaces)
        -   [
            
            IssuesPlan and track work
            
            ](https://github.com/features/issues)
        -   [
            
            Code ReviewManage code changes
            
            ](https://github.com/features/code-review)
        
    -   APPLICATION SECURITY
        
        -   [
            
            GitHub Advanced SecurityFind and fix vulnerabilities
            
            ](https://github.com/security/advanced-security)
        -   [
            
            Code securitySecure your code as you build
            
            ](https://github.com/security/advanced-security/code-security)
        -   [
            
            Secret protectionStop leaks before they start
            
            ](https://github.com/security/advanced-security/secret-protection)
        
    -   EXPLORE
        
        -   [Why GitHub](https://github.com/why-github)
        -   [Documentation](https://docs.github.com)
        -   [Blog](https://github.blog)
        -   [Changelog](https://github.blog/changelog)
        -   [Marketplace](https://github.com/marketplace)
        
    
    [View all features](https://github.com/features)
    
-   Solutions
    
    -   BY COMPANY SIZE
        
        -   [Enterprises](https://github.com/enterprise)
        -   [Small and medium teams](https://github.com/team)
        -   [Startups](https://github.com/enterprise/startups)
        -   [Nonprofits](https://github.com/solutions/industry/nonprofits)
        
    -   BY USE CASE
        
        -   [App Modernization](https://github.com/solutions/use-case/app-modernization)
        -   [DevSecOps](https://github.com/solutions/use-case/devsecops)
        -   [DevOps](https://github.com/solutions/use-case/devops)
        -   [CI/CD](https://github.com/solutions/use-case/ci-cd)
        -   [View all use cases](https://github.com/solutions/use-case)
        
    -   BY INDUSTRY
        
        -   [Healthcare](https://github.com/solutions/industry/healthcare)
        -   [Financial services](https://github.com/solutions/industry/financial-services)
        -   [Manufacturing](https://github.com/solutions/industry/manufacturing)
        -   [Government](https://github.com/solutions/industry/government)
        -   [View all industries](https://github.com/solutions/industry)
        
    
    [View all solutions](https://github.com/solutions)
    
-   Resources
    
    -   EXPLORE BY TOPIC
        
        -   [AI](https://github.com/resources/articles?topic=ai)
        -   [Software Development](https://github.com/resources/articles?topic=software-development)
        -   [DevOps](https://github.com/resources/articles?topic=devops)
        -   [Security](https://github.com/resources/articles?topic=security)
        -   [View all topics](https://github.com/resources/articles)
        
    -   EXPLORE BY TYPE
        
        -   [Customer stories](https://github.com/customer-stories)
        -   [Events & webinars](https://github.com/resources/events)
        -   [Ebooks & reports](https://github.com/resources/whitepapers)
        -   [Business insights](https://github.com/solutions/executive-insights)
        -   [GitHub Skills](https://skills.github.com)
        
    -   SUPPORT & SERVICES
        
        -   [Documentation](https://docs.github.com)
        -   [Customer support](https://support.github.com)
        -   [Community forum](https://github.com/orgs/community/discussions)
        -   [Trust center](https://github.com/trust-center)
        -   [Partners](https://github.com/partners)
        
    
    [View all resources](https://github.com/resources)
    
-   Open Source
    
    -   COMMUNITY
        
        -   [
            
            GitHub SponsorsFund open source developers
            
            ](https://github.com/sponsors)
        
    -   PROGRAMS
        
        -   [Security Lab](https://securitylab.github.com)
        -   [Maintainer Community](https://maintainers.github.com)
        -   [Accelerator](https://github.com/accelerator)
        -   [GitHub Stars](https://stars.github.com)
        -   [Archive Program](https://archiveprogram.github.com)
        
    -   REPOSITORIES
        
        -   [Topics](https://github.com/topics)
        -   [Trending](https://github.com/trending)
        -   [Collections](https://github.com/collections)
        
    
-   Enterprise
    
    -   ENTERPRISE SOLUTIONS
        
        -   [
            
            Enterprise platformAI-powered developer platform
            
            ](https://github.com/enterprise)
        
    -   AVAILABLE ADD-ONS
        
        -   [
            
            GitHub Advanced SecurityEnterprise-grade security features
            
            ](https://github.com/security/advanced-security)
        -   [
            
            Copilot for BusinessEnterprise-grade AI features
            
            ](https://github.com/features/copilot/copilot-business)
        -   [
            
            Premium SupportEnterprise-grade 24/7 support
            
            ](https://github.com/premium-support)
        
    
-   [Pricing](https://github.com/pricing)

Search or jump to...

# Search code, repositories, users, issues, pull requests...

Search

Clear

[Search syntax tips](https://docs.github.com/search-github/github-code-search/understanding-github-code-search-syntax)

# Provide feedback

We read every piece of feedback, and take your input very seriously.

 Include my email address so I can be contacted

Cancel Submit feedback

# Saved searches

## Use saved searches to filter your results more quickly

Name  

Query 

To see all available qualifiers, see our [documentation](https://docs.github.com/search-github/github-code-search/understanding-github-code-search-syntax).

Cancel Create saved search

[Sign in](/login?return_to=https%3A%2F%2Fgithub.com%2Friparias%2Fgbif-alert)

[Sign up](/signup?ref_cta=Sign+up&ref_loc=header+logged+out&ref_page=%2F%3Cuser-name%3E%2F%3Crepo-name%3E&source=header-repo&source_repo=riparias%2Fgbif-alert)

Appearance settings

Resetting focus

You signed in with another tab or window. Reload to refresh your session. You signed out in another tab or window. Reload to refresh your session. You switched accounts on another tab or window. Reload to refresh your session. Dismiss alert

[riparias](/riparias) / **[gbif-alert](/riparias/gbif-alert)** Public

-   [Notifications](/login?return_to=%2Friparias%2Fgbif-alert) You must be signed in to change notification settings
-   [Fork 6](/login?return_to=%2Friparias%2Fgbif-alert)
-   [Star 11](/login?return_to=%2Friparias%2Fgbif-alert)
    

-   [Code](/riparias/gbif-alert)
-   [Issues 62](/riparias/gbif-alert/issues)
-   [Pull requests 0](/riparias/gbif-alert/pulls)
-   [Discussions](/riparias/gbif-alert/discussions)
-   [Actions](/riparias/gbif-alert/actions)
-   [Projects](/riparias/gbif-alert/projects)
-   [Security and quality 0](/riparias/gbif-alert/security)
-   [Insights](/riparias/gbif-alert/pulse)

Additional navigation options

-   [Code](/riparias/gbif-alert)
-   [Issues](/riparias/gbif-alert/issues)
-   [Pull requests](/riparias/gbif-alert/pulls)
-   [Discussions](/riparias/gbif-alert/discussions)
-   [Actions](/riparias/gbif-alert/actions)
-   [Projects](/riparias/gbif-alert/projects)
-   [Security and quality](/riparias/gbif-alert/security)
-   [Insights](/riparias/gbif-alert/pulse)

 [](/riparias/gbif-alert)

# riparias/gbif-alert

 main

[Branches](/riparias/gbif-alert/branches)[Tags](/riparias/gbif-alert/tags)

[](/riparias/gbif-alert/branches)[](/riparias/gbif-alert/tags)

Go to file

Code

Open more actions menu

## Folders and files

Name

Name

Last commit message

Last commit date

## Latest commit

## History

[1,386 Commits](/riparias/gbif-alert/commits/main/)

[](/riparias/gbif-alert/commits/main/)1,386 Commits

[.github/workflows](/riparias/gbif-alert/tree/main/.github/workflows "This path skips through empty directories")

[.github/workflows](/riparias/gbif-alert/tree/main/.github/workflows "This path skips through empty directories")

[assets/frontend](/riparias/gbif-alert/tree/main/assets/frontend "This path skips through empty directories")

[assets/frontend](/riparias/gbif-alert/tree/main/assets/frontend "This path skips through empty directories")

[dashboard](/riparias/gbif-alert/tree/main/dashboard "dashboard")

[dashboard](/riparias/gbif-alert/tree/main/dashboard "dashboard")

[djangoproject](/riparias/gbif-alert/tree/main/djangoproject "djangoproject")

[djangoproject](/riparias/gbif-alert/tree/main/djangoproject "djangoproject")

[page\_fragments](/riparias/gbif-alert/tree/main/page_fragments "page_fragments")

[page\_fragments](/riparias/gbif-alert/tree/main/page_fragments "page_fragments")

[scripts](/riparias/gbif-alert/tree/main/scripts "scripts")

[scripts](/riparias/gbif-alert/tree/main/scripts "scripts")

[source\_data/public\_areas](/riparias/gbif-alert/tree/main/source_data/public_areas "This path skips through empty directories")

[source\_data/public\_areas](/riparias/gbif-alert/tree/main/source_data/public_areas "This path skips through empty directories")

[static\_global](/riparias/gbif-alert/tree/main/static_global "static_global")

[static\_global](/riparias/gbif-alert/tree/main/static_global "static_global")

[.dockerignore](/riparias/gbif-alert/blob/main/.dockerignore ".dockerignore")

[.dockerignore](/riparias/gbif-alert/blob/main/.dockerignore ".dockerignore")

[.env.example](/riparias/gbif-alert/blob/main/.env.example ".env.example")

[.env.example](/riparias/gbif-alert/blob/main/.env.example ".env.example")

[.gitattributes](/riparias/gbif-alert/blob/main/.gitattributes ".gitattributes")

[.gitattributes](/riparias/gbif-alert/blob/main/.gitattributes ".gitattributes")

[.gitignore](/riparias/gbif-alert/blob/main/.gitignore ".gitignore")

[.gitignore](/riparias/gbif-alert/blob/main/.gitignore ".gitignore")

[API.md](/riparias/gbif-alert/blob/main/API.md "API.md")

[API.md](/riparias/gbif-alert/blob/main/API.md "API.md")

[CHANGELOG.md](/riparias/gbif-alert/blob/main/CHANGELOG.md "CHANGELOG.md")

[CHANGELOG.md](/riparias/gbif-alert/blob/main/CHANGELOG.md "CHANGELOG.md")

[CLAUDE.md](/riparias/gbif-alert/blob/main/CLAUDE.md "CLAUDE.md")

[CLAUDE.md](/riparias/gbif-alert/blob/main/CLAUDE.md "CLAUDE.md")

[CONTRIBUTING.md](/riparias/gbif-alert/blob/main/CONTRIBUTING.md "CONTRIBUTING.md")

[CONTRIBUTING.md](/riparias/gbif-alert/blob/main/CONTRIBUTING.md "CONTRIBUTING.md")

[Dockerfile](/riparias/gbif-alert/blob/main/Dockerfile "Dockerfile")

[Dockerfile](/riparias/gbif-alert/blob/main/Dockerfile "Dockerfile")

[INSTALL.md](/riparias/gbif-alert/blob/main/INSTALL.md "INSTALL.md")

[INSTALL.md](/riparias/gbif-alert/blob/main/INSTALL.md "INSTALL.md")

[LICENSE](/riparias/gbif-alert/blob/main/LICENSE "LICENSE")

[LICENSE](/riparias/gbif-alert/blob/main/LICENSE "LICENSE")

[README.md](/riparias/gbif-alert/blob/main/README.md "README.md")

[README.md](/riparias/gbif-alert/blob/main/README.md "README.md")

[conftest.py](/riparias/gbif-alert/blob/main/conftest.py "conftest.py")

[conftest.py](/riparias/gbif-alert/blob/main/conftest.py "conftest.py")

[docker-compose.dokploy.yml](/riparias/gbif-alert/blob/main/docker-compose.dokploy.yml "docker-compose.dokploy.yml")

[docker-compose.dokploy.yml](/riparias/gbif-alert/blob/main/docker-compose.dokploy.yml "docker-compose.dokploy.yml")

[docker-compose.yml](/riparias/gbif-alert/blob/main/docker-compose.yml "docker-compose.yml")

[docker-compose.yml](/riparias/gbif-alert/blob/main/docker-compose.yml "docker-compose.yml")

[manage.py](/riparias/gbif-alert/blob/main/manage.py "manage.py")

[manage.py](/riparias/gbif-alert/blob/main/manage.py "manage.py")

[mypy.ini](/riparias/gbif-alert/blob/main/mypy.ini "mypy.ini")

[mypy.ini](/riparias/gbif-alert/blob/main/mypy.ini "mypy.ini")

[package-lock.json](/riparias/gbif-alert/blob/main/package-lock.json "package-lock.json")

[package-lock.json](/riparias/gbif-alert/blob/main/package-lock.json "package-lock.json")

[package.json](/riparias/gbif-alert/blob/main/package.json "package.json")

[package.json](/riparias/gbif-alert/blob/main/package.json "package.json")

[pyproject.toml](/riparias/gbif-alert/blob/main/pyproject.toml "pyproject.toml")

[pyproject.toml](/riparias/gbif-alert/blob/main/pyproject.toml "pyproject.toml")

[tsconfig.app.json](/riparias/gbif-alert/blob/main/tsconfig.app.json "tsconfig.app.json")

[tsconfig.app.json](/riparias/gbif-alert/blob/main/tsconfig.app.json "tsconfig.app.json")

[tsconfig.json](/riparias/gbif-alert/blob/main/tsconfig.json "tsconfig.json")

[tsconfig.json](/riparias/gbif-alert/blob/main/tsconfig.json "tsconfig.json")

[tsconfig.node.json](/riparias/gbif-alert/blob/main/tsconfig.node.json "tsconfig.node.json")

[tsconfig.node.json](/riparias/gbif-alert/blob/main/tsconfig.node.json "tsconfig.node.json")

[uv.lock](/riparias/gbif-alert/blob/main/uv.lock "uv.lock")

[uv.lock](/riparias/gbif-alert/blob/main/uv.lock "uv.lock")

[vite.config.ts](/riparias/gbif-alert/blob/main/vite.config.ts "vite.config.ts")

[vite.config.ts](/riparias/gbif-alert/blob/main/vite.config.ts "vite.config.ts")

View all files

## Repository files navigation

-   [README](#)
-   [Contributing](#)
-   [MIT license](#)

# GBIF Alert

[](#gbif-alert)

[![Django CI](https://github.com/riparias/gbif-alert/actions/workflows/django_tests.yml/badge.svg)](https://github.com/riparias/gbif-alert/actions/workflows/django_tests.yml) [![License: MIT](https://camo.githubusercontent.com/fdf2982b9f5d7489dcf44570e714e3a15fce6253e0cc6b5aa61a075aac2ff71b/68747470733a2f2f696d672e736869656c64732e696f2f62616467652f4c6963656e73652d4d49542d79656c6c6f772e737667)](https://opensource.org/licenses/MIT) [![Automatic deployment - demo server](https://github.com/riparias/gbif-alert/actions/workflows/deploy_demo.yml/badge.svg?branch=devel)](https://github.com/riparias/gbif-alert/actions/workflows/deploy_demo.yml)

GBIF Alert is a [GBIF](https://www.gbif.org)\-based early alert system for invasive species.

🎉 **GBIF Alert has been awarded the first prize of the [GBIF Ebbe Nielsen Challenge 2023](https://www.gbif.org/fr/news/EQgUzZ4YA75BSeLs1naI9/)!** 🎉

It is a reusable website engine powered by [Django](https://www.djangoproject.com/) available under the [MIT license](/riparias/gbif-alert/blob/main/LICENSE). Contributions are welcome! See [CONTRIBUTING.md](/riparias/gbif-alert/blob/main/CONTRIBUTING.md) for more information.

## Getting started

[](#getting-started)

GBIF Alert allows you to monitor a list of species, and be notified of new occurrences on GBIF via email.

Multiple websites using GBIF alert (called *instances*) exists, in order to target different communities:

-   **You are an end-user that just want to be informed of new occurrence in the GBIF network?** Join [an existing instance](#user-content-gbif-alert-instances-in-the-wild) that covers your area and species of interest, register and start configuring your alerts! Here is a demonstration video: [https://www.youtube.com/watch?v=bixaTGRIZ4A](https://www.youtube.com/watch?v=bixaTGRIZ4A)
    
-   **You have more technical knowledge and want to install your own instance of GBIF Alert?** No problem: GBIF Alert is fully configurable, and we provide facilities to make it easy to install and deploy. See [INSTALL.md](/riparias/gbif-alert/blob/main/INSTALL.md) for more information.
    

## GBIF Alert instances in the wild

[](#gbif-alert-instances-in-the-wild)

-   LIFE RIPARIAS Early Alert: [production](https://alert.riparias.be) / [development](https://dev-alert.riparias.be) (Targets riparian invasive species in Belgium)
-   [GBIF Alert demo instance](https://gbif-alert-demo.thebinaryforest.net/) (Always in sync with the `devel` branch of this repository)
-   The Belgian Biodiversity Platform uses GBIF alert under the hood as an API for the ManaIAS project.

## API

[](#api)

GBIF Alert exposes a stable, supported public HTTP API (API v2) for programmatic access to its data.

Each instance documents its own API: visit `/api-docs` on any instance for an overview, with the interactive reference at `/api/v2/docs` and an OGC WFS service at `/api/wfs/observations/`. For example, on the LIFE RIPARIAS instance: [https://alert.riparias.be/api-docs](https://alert.riparias.be/api-docs)

The older `/api/*` JSON endpoints are deprecated in favour of API v2 and will be removed on 30 June 2027.

## About

GBIF Alert is a GBIF occurrence based alert system.

[gbif-alert-demo.thebinaryforest.net/](https://gbif-alert-demo.thebinaryforest.net/ "https://gbif-alert-demo.thebinaryforest.net/")

### Topics

[django](/topics/django "Topic: django") [webapp](/topics/webapp "Topic: webapp") [biodiversity-informatics](/topics/biodiversity-informatics "Topic: biodiversity-informatics") [biodiversity](/topics/biodiversity "Topic: biodiversity") [gbif](/topics/gbif "Topic: gbif") [invasive-species](/topics/invasive-species "Topic: invasive-species") [biodiversity-data](/topics/biodiversity-data "Topic: biodiversity-data")

### Resources

[Readme](#readme-ov-file)

### License

[MIT license](#MIT-1-ov-file)

### Contributing

[Contributing](#contributing-ov-file)

### Uh oh!

There was an error while loading. Please reload this page.

[Activity](/riparias/gbif-alert/activity)

[Custom properties](/riparias/gbif-alert/custom-properties)

### Stars

[**11** stars](/riparias/gbif-alert/stargazers)

### Watchers

[**4** watching](/riparias/gbif-alert/watchers)

### Forks

[**6** forks](/riparias/gbif-alert/forks)

[Report repository](/contact/report-content?content_url=https%3A%2F%2Fgithub.com%2Friparias%2Fgbif-alert&report=riparias+%28user%29)

## [Releases](/riparias/gbif-alert/releases)

[39 tags](/riparias/gbif-alert/tags)

### Uh oh!

There was an error while loading. Please reload this page.

## [Contributors](/riparias/gbif-alert/graphs/contributors)

### Uh oh!

There was an error while loading. Please reload this page.

## Languages

-   [Python 63.6%](/riparias/gbif-alert/search?l=python)
-   [Vue 20.8%](/riparias/gbif-alert/search?l=vue)
-   [TypeScript 12.9%](/riparias/gbif-alert/search?l=typescript)
-   [HTML 1.5%](/riparias/gbif-alert/search?l=html)
-   [CSS 0.4%](/riparias/gbif-alert/search?l=css)
-   [Dockerfile 0.4%](/riparias/gbif-alert/search?l=dockerfile)
-   Other 0.4%

## Footer

[](https://github.com)© 2026 GitHub, Inc.

### Footer navigation

-   [Terms](https://docs.github.com/site-policy/github-terms/github-terms-of-service)
-   [Privacy](https://docs.github.com/site-policy/privacy-policies/github-privacy-statement)
-   [Security](https://github.com/security)
-   [Status](https://www.githubstatus.com/)
-   [Community](https://github.community/)
-   [Docs](https://docs.github.com/)
-   [Contact](https://support.github.com?tags=dotcom-footer)
-   Manage cookies
-   Do not share my personal information

You can’t perform that action at this time.
```

---

## Assistant (Plan · DeepSeek V4 Flash Free · 52.3s)

_Thinking:_

Now I have extremely detailed knowledge of the codebase patterns. Let me construct a thorough technical architecture plan for the EWS. I need to:

1. Align with existing patterns (static configurators, enum contracts, service caching, queue jobs with progress tracking, booted() model events)
2. Reference GuardIAS/EASIN/GBIF Alert integration points
3. Provide concrete file paths, class names, and table schemas
4. Show the actual data flow

Let me organize this into a detailed but readable plan.

Key patterns I must follow:
- Models: PHP 8 attributes for fillable, casts() method, booted() for events
- Enums: string backed, implement HasLabel/HasColor/HasIcon/HasDescription
- Services: Cache::remember pattern, constructor injection
- Jobs: ShouldQueue, Queueable, TracksJobProgress trait, chunkById processing
- Filament: static configurator pattern (Resource with Schemas/ and Tables/ subdirectories)
- Notifications: Queueable, via() returns channels, toMail()/toDatabase() methods
- Observers: can use auto-discovery or register in booted()

Now let me write the refined technical plan.

Now I have a thorough understanding of every code pattern. Here is the refined **technical architecture** for a MAMIAS EWS, aligned with existing code conventions and the GuardIAS ecosystem.

---

## 1. Data Layer — Models & Migrations

### `Alert` model — `app/Models/Alert.php`

Follows the exact pattern of `Occurrence` (booted trait, casts, attributes).

| Column | Type | Purpose |
|---|---|---|
| `id` | bigint AI | |
| `taxon_id` | FK→taxas, nullable | The species triggering the alert |
| `scientific_name` | string(255) | Denormalized for non-Taxon alerts |
| `event_type` | string(20) enum | `new_arrival` / `range_expansion` / `high_impact` / `rapid_spread` |
| `severity` | string(10) enum | `info` / `warning` / `critical` |
| `title` | string(255) | Human-readable headline |
| `description` | text | Markdown body |
| `source_type` | string(50), nullable | `occurrence`, `nis_suggestion`, `intro_event`, `easin` |
| `source_id` | bigint, nullable | Polymorphic FK to the source record |
| `subregion` | string(50), nullable | Geo context |
| `published_at` | datetime | When the alert becomes visible |
| `expires_at` | datetime, nullable | Auto-archival |
| `metadata` | json, nullable | Extra context (distance, surrounding records, etc.) |
| `created_by`, `updated_by` | FK→users | Userstamps via existing trait |

**New `AlertStatus` enum** — `app/Enums/AlertStatus.php` (draft)
```php
enum AlertStatus: string implements HasColor, HasIcon, HasLabel
{
    case ACTIVE = 'active';
    case ACKNOWLEDGED = 'acknowledged';
    case EXPIRED = 'expired';
}
```

**New `AlertEventType` enum** — `app/Enums/AlertEventType.php` (draft)
```php
enum AlertEventType: string implements HasColor, HasIcon, HasLabel
{
    case NEW_ARRIVAL = 'new_arrival';
    case RANGE_EXPANSION = 'range_expansion';
    case HIGH_IMPACT = 'high_impact';
    case RAPID_SPREAD = 'rapid_spread';
}
```

Boot logic in `booted()`:
- On `creating`: set `published_at = now()`
- On `creating`: auto-calculate `expires_at = now()->addDays(90)` for `warning`/`info`, `addDays(30)` for `critical`

### `AlertSubscription` model — `app/Models/AlertSubscription.php`

| Column | Type | Purpose |
|---|---|---|
| `id` | bigint AI | |
| `user_id` | FK→users | Subscriber |
| `subregion` | string(50), nullable | Filter by region |
| `taxon_id` | FK→taxas, nullable | Filter by specific species |
| `taxonomic_group` | string(100), nullable | Filter by phylum/class (e.g. "Mollusca") |
| `min_severity` | string(10) enum | Minimum alert severity to deliver |
| `channels` | json | `['mail', 'database']` or `['mail', 'whatsapp']` |
| `frequency` | string(20) enum | `immediate` / `daily` / `weekly` |
| `is_active` | boolean | Toggle on/off |
| `last_sent_at` | datetime, nullable | Throttle tracking |
| `created_at`, `updated_at` | timestamps | |

### Migration — `2026_XX_XX_XXXXXX_create_alerts_and_subscriptions_tables.php`

Spatial index on the `subregion` concept: use the existing `Subregion` enum that MAMIAS already defines. Each `Alert` can reference a subregion, subscriptions filter by it.

The existing `Occurrence` model already has `near()` scopes — the alert engine will query those.

---

## 2. Alert Engine — Service Layer

All under `app/Services/Ews/`:

### `EwsEvaluator` — `app/Services/Ews/EwsEvaluator.php`

Triggered when an `Occurrence` is approved (status changes to `approved`) or a `NisSuggestion` is approved. Evaluates four conditions in order:

**Detection 1 — New Arrival** (species never recorded in subregion)
```php
// Check if any existing IntroEventRecord has this taxon_id + subregion
$existing = SubregionRecord::where('intro_event_id', $taxonId)->where('subregion', $subregion)->exists();
// If not → NEW_ARRIVAL alert
```

**Detection 2 — Range Expansion** (occurrence far from nearest known occurrence)
```php
// Uses the existing spatial scope:
$nearest = Occurrence::where('id', '!=', $occurrenceId)
    ->whereHas('introEventRecord', fn($q) => $q->where('taxon_id', $taxonId))
    ->orderByDistance($lat, $lng)
    ->withDistanceFrom($lat, $lng)
    ->first();

if ($nearest && $nearest->distance_meters > 50000) { // >50km from known occurrence
    // → RANGE_EXPANSION alert with distance metadata
}
```

**Detection 3 — High Impact Species** (species on EU IAS Regulation / high-priority list)
```php
// Species already flagged in intro_event_records as high-priority NIS
$taxon->introEvents()->where('nis_status', NisStatus::NIS)->exists();
// Or check against a cached high-impact species list
```

**Detection 4 — Rapid Spread** (multiple occurrences of same species in short window)
```php
$recentCount = Occurrence::whereHas('introEventRecord', fn($q) => $q->where('taxon_id', $taxonId))
    ->where('observed_at', '>=', now()->subDays(30))
    ->count();
// If > threshold (e.g. 3 in 30 days) → RAPID_SPREAD alert
```

### `AlertGenerator` — `app/Services/Ews/AlertGenerator.php`

```php
class AlertGenerator
{
    public function generate(AlertEventType $type, array $context): ?Alert
    {
        // Deduplication: same species + subregion + event_type within N days
        $existing = Alert::where('taxon_id', $context['taxon_id'])
            ->where('event_type', $type)
            ->where('subregion', $context['subregion'])
            ->where('created_at', '>=', now()->subDays(7))
            ->exists();

        if ($existing) {
            return null; // Already alerted this week
        }

        return Alert::create([
            'taxon_id' => $context['taxon_id'],
            'scientific_name' => $context['scientific_name'],
            'event_type' => $type,
            'severity' => $this->calculateSeverity($type, $context),
            'title' => $this->buildTitle($type, $context),
            'description' => $this->buildDescription($type, $context),
            'source_type' => $context['source_type'],
            'source_id' => $context['source_id'],
            'subregion' => $context['subregion'],
            'metadata' => $context['metadata'] ?? [],
        ]);
    }

    private function calculateSeverity(AlertEventType $type, array $context): string
    {
        return match ($type) {
            AlertEventType::NEW_ARRIVAL => 'critical',
            AlertEventType::RANGE_EXPANSION => 'warning',
            AlertEventType::HIGH_IMPACT => 'critical',
            AlertEventType::RAPID_SPREAD => 'warning',
        };
    }
}
```

### `AlertDistributor` — `app/Services/Ews/AlertDistributor.php`

Matches a new `Alert` against all relevant `AlertSubscription` records and dispatches notification jobs:

```php
class AlertDistributor
{
    public function distribute(Alert $alert): void
    {
        $subscriptions = AlertSubscription::where('is_active', true)
            ->where(fn ($q) => $q
                ->whereNull('subregion')->orWhere('subregion', $alert->subregion)
                ->where(fn ($q) => $q
                    ->whereNull('min_severity')
                    ->orWhere('min_severity', '<=', $alert->severity->value) // severity ordinal
                )
            )
            ->with('user')
            ->get();

        foreach ($subscriptions->groupBy('frequency') as $frequency => $group) {
            if ($frequency === 'immediate') {
                foreach ($group as $subscription) {
                    SendAlertNotification::dispatch($alert, $subscription);
                }
            } else {
                // Batch for daily/weekly digest
                Cache::push("alert-digest-{$subscription->id}", $alert->id);
            }
        }
    }
}
```

### Trigger wiring — Observer on Occurrence/NisSuggestion

New file: `app/Observers/EwsObserver.php`

Following the `LiteratureObserver` pattern:
```php
class EwsObserver
{
    public function __construct(
        private EwsEvaluator $evaluator,
        private AlertGenerator $generator,
        private AlertDistributor $distributor,
    ) {}

    public function saved(Occurrence $occurrence): void
    {
        // Only evaluate when status changes TO approved
        if (! $occurrence->wasChanged('status') || $occurrence->status !== OccurrenceStatus::APPROVED) {
            return;
        }

        $alerts = $this->evaluator->evaluate($occurrence);

        foreach ($alerts as $alertData) {
            $alert = $this->generator->generate(...$alertData);
            if ($alert) {
                $this->distributor->distribute($alert);
                $this->dispatch('refresh-sidebar'); // Via RefreshSidebarPlugin
            }
        }
    }
}
```

Register in `boot()` of `AppServiceProvider` or via auto-discovery:
```php
Occurrence::observe(EwsObserver::class);
NisSuggestion::observe(EwsObserver::class);
```

---

## 3. Queue Jobs

### `SendAlertNotification` — `app/Jobs/SendAlertNotification.php`

Follows the `FetchEasinIdsJob` pattern exactly:
```php
class SendAlertNotification implements ShouldQueue
{
    use Queueable;

    public int $timeout = 30;
    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    public function __construct(
        private Alert $alert,
        private AlertSubscription $subscription,
    ) {}

    public function handle(): void
    {
        $channels = $this->subscription->channels;

        if (in_array('mail', $channels)) {
            $this->subscription->user->notify(new AlertNotification($this->alert));
        }

        if (in_array('database', $channels)) {
            Notification::make()
                ->title($this->alert->title)
                ->body($this->alert->description)
                ->icon($this->getAlertIcon($this->alert->severity))
                ->iconColor($this->getAlertColor($this->alert->severity))
                ->actions([
                    FilamentAction::make('view')
                        ->url(route('filament.mamias.resources.alerts.view', $this->alert)),
                ])
                ->sendToDatabase($this->subscription->user);
        }

        if (in_array('whatsapp', $channels) && $this->subscription->user->has_whatsapp) {
            app(WhatsAppService::class)->sendMessage(
                $this->subscription->user->phone,
                $this->formatWhatsAppMessage($this->alert)
            );
        }
    }
}
```

### `GenerateDailyDigest` — `app/Jobs/GenerateDailyDigest.php`

Cron-scheduled (in `routes/console.php`) to batch daily/weekly subscription alerts.

### `FetchEasinAlertsJob` — new job polling EASIN

Extension of existing `FetchEasinIdsJob` pattern. Periodically checks:
- EASIN API for new alerts
- GuardIAS indicator output (weekly occurrence cubes) at `guardias-eu.github.io/indicators`
- GBIF Alert instances via their public API

---

## 4. Notification Classes

### `AlertNotification` — `app/Notifications/AlertNotification.php`

Follows the `NewLiteratureReferenceNotification` pattern (supports mail + database):
```php
class AlertNotification extends Notification
{
    use Queueable;

    public function __construct(public Alert $alert) {}

    public function via(object $notifiable): array
    {
        // Return channels based on subscription
        return $notifiable->routeNotificationFor('mail') ? ['mail', 'database'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[MAMIAS EWS] {$this->alert->title}")
            ->greeting("Early Warning Alert")
            ->line($this->alert->description)
            ->action('View Alert', route('filament.mamias.resources.alerts.view', $this->alert))
            ->line('Manage your subscriptions in your profile settings.');
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->alert->title)
            ->icon($this->getAlertIcon())
            ->iconColor($this->getAlertColor())
            ->body(str($this->alert->description)->limit(200))
            ->actions([
                FilamentAction::make('view')
                    ->url(route('filament.mamias.resources.alerts.view', $this->alert)),
            ])
            ->getDatabaseMessage();
    }
}
```

---

## 5. Filament Resources (Panel)

### `AlertResource` — `app/Filament/Resources/Ews/AlertResource.php`

Structure mirrors `NisSuggestionResource`:

```
app/Filament/Resources/Ews/
├── AlertResource.php
├── Pages/
│   ├── ListAlerts.php
│   └── ViewAlert.php
├── Schemas/
│   └── AlertInfolist.php
├── Tables/
│   └── AlertsTable.php
└── Actions/
    └── AlertActions.php
```

**Table columns**: Scientific name, event type (badge), severity (colored badge), subregion, source type, published at.

**Filters**: By severity, event type, subregion, date range, source type.

**Infolist**: Section with species info, map entry (reuse `OccurrenceLocationsMapEntry` pattern), alert details, source record link.

**Actions**: Acknowledge, dismiss, change severity.

### `AlertSubscriptionResource` — `app/Filament/Resources/Ews/AlertSubscriptionResource.php`

User-facing. Table shows their active subscriptions with toggle.

### Dashboard Widget — `ActiveAlertsWidget`

Follows existing widget pattern (`app/Filament/Widgets/ActiveAlertsWidget.php`):
- Shows count of active alerts by severity
- Links to AlertResource list
- Optional map component using Filament Leaflet

---

## 6. GuardIAS & External Integration

### Existing EASIN Service — extend

`app/Services/EasinService.php` already caches EASIN IDs for 24h. Add:
```php
public function fetchRecentAlerts(): array
{
    // Query EASIN API for new alerts in Mediterranean
}
```

### New: `GiasService` — `app/Services/GiasService.php`

Consumer of the GuardIAS Intelligence Analyst System (once its API is available in beta, 2026). Will pull species intelligence data (invasiveness scores, trait predictions) to populate alert context.

### New: `GbifAlertService` — `app/Services/GbifAlertService.php`

Polls GBIF for new occurrences of MAMIAS species in the Mediterranean, using the GBIF Alert API v2 (stable API per the project). Feeds into the EwsEvaluator as secondary detection.

### Console route — `routes/console.php`

```php
Schedule::call(fn () => GbifAlertService::sync())->weekly();
Schedule::call(fn () => EasinService::fetchRecentAlerts())->daily();
Schedule::call(fn () => GenerateDailyDigest::dispatch())->dailyAt('08:00');
```

---

## 7. Public-Facing (Livewire)

### `PublicAlerts` — `app/Livewire/PublicAlerts.php`

New Livewire component for `GET /early-warning`:
- Follows `MySuggestions` pattern (InteractsWithTable, InteractsWithActions)
- Read-only table: scientific name, event type, severity, date
- Map component showing active alert locations
- Filter by subregion, severity, date range

Route in `routes/web.php`:
```php
Route::get('/early-warning', PublicAlerts::class)
    ->name('early-warning');
```

Breadcrumb: `Home > Early Warning`

---

## 8. API & Webhooks (Phase 5)

### New API route file — `routes/api.php`

```php
Route::prefix('api/ew')->group(function () {
    Route::get('/alerts', [AlertApiController::class, 'index']);
    Route::get('/alerts/{alert}', [AlertApiController::class, 'show']);
    Route::post('/alerts/easin', [EasinWebhookController::class, 'receive']);
    Route::post('/webhooks', [WebhookController::class, 'register'])
        ->middleware('auth:sanctum');
});
```

Auth via Laravel Sanctum (add `composer require laravel/sanctum` — check if already present).

---

## 9. File Tree Summary

```
app/
├── Enums/
│   ├── AlertSeverity.php          (NEW)
│   ├── AlertEventType.php         (NEW)
│   └── AlertFrequency.php         (NEW)
├── Models/
│   ├── Alert.php                  (NEW)
│   └── AlertSubscription.php      (NEW)
├── Services/
│   ├── Ews/
│   │   ├── EwsEvaluator.php       (NEW)
│   │   ├── AlertGenerator.php     (NEW)
│   │   └── AlertDistributor.php   (NEW)
│   ├── EasinService.php           (EXTEND)
│   ├── GiasService.php            (NEW - GuardIAS)
│   ├── GbifAlertService.php       (NEW)
│   └── WhatsAppService.php        (EXTEND - add sendMessage)
├── Jobs/
│   ├── SendAlertNotification.php  (NEW)
│   ├── GenerateDailyDigest.php    (NEW)
│   └── FetchEasinAlertsJob.php    (NEW)
├── Observers/
│   └── EwsObserver.php            (NEW)
├── Notifications/
│   └── AlertNotification.php      (NEW)
├── Filament/
│   └── Resources/
│       └── Ews/
│           ├── AlertResource.php
│           ├── AlertSubscriptionResource.php
│           ├── Pages/
│           ├── Schemas/
│           ├── Tables/
│           └── Actions/
├── Widgets/
│   └── ActiveAlertsWidget.php     (NEW)
├── Livewire/
│   └── PublicAlerts.php           (NEW)
├── Http/
│   └── Controllers/
│       └── Api/
│           ├── AlertApiController.php   (NEW)
│           └── EasinWebhookController.php (NEW)
database/
└── migrations/
    └── 2026_XX_XX_XXXXXX_create_alerts_and_subscriptions_tables.php (NEW)
routes/
├── api.php                        (NEW)
└── web.php                        (EXTEND)
```

---

## 10. Implementation Sequence

| Step | Files | Effort |
|---|---|---|
| 1 | Enums (`AlertSeverity`, `AlertEventType`) | ~15 min |
| 2 | Migration + `Alert` + `AlertSubscription` models | ~1 hr |
| 3 | `EwsEvaluator` + `AlertGenerator` + `AlertDistributor` | ~3 hr |
| 4 | `EwsObserver` + register in provider | ~30 min |
| 5 | `AlertNotification` (mail + database) | ~30 min |
| 6 | `SendAlertNotification` job | ~20 min |
| 7 | `AlertResource` + `AlertsTable` + `AlertInfolist` | ~2 hr |
| 8 | `AlertSubscriptionResource` | ~1 hr |
| 9 | `ActiveAlertsWidget` | ~30 min |
| 10 | `PublicAlerts` Livewire component + route | ~1.5 hr |
| 11 | Extend `WhatsAppService` with `sendMessage()` | ~30 min |
| 12 | Extend `EasinService` with alert fetching | ~30 min |
| 13 | `GenerateDailyDigest` job + schedule | ~30 min |
| 14 | API routes + controllers | ~1 hr |

**Total: ~13-15 hours** for a functional full-stack EWS.

---

Would you like me to dive deeper into any specific layer — the alert evaluation SQL, the Filament UI wireframes, or the GuardIAS webhook contract?

---

## User

dive deeper

---

## Assistant (Plan · DeepSeek V4 Flash Free · 329.8s)

_Thinking:_

The user said "dive deeper" - they want me to go into more technical detail on specific aspects of the EWS plan. Let me ask them which layer they want me to dive deeper into, since I covered many areas.

Actually, looking at the conversation flow, I think they want me to dive deeper into all the key areas. Let me focus on the most technically critical parts:

1. The alert evaluation SQL (the core EwsEvaluator logic)
2. The spatial queries using PostGIS
3. The Filament UI specifics (tables, infolists, widgets)
4. The GuardIAS/EASIN integration contracts

Let me provide deeper technical detail on each of these.

But wait - I'm in plan mode. I should ask the user which specific areas they want to dive deeper into, or I could just provide a comprehensive deep-dive across all layers.

Given the user just said "dive deeper" without specifying, I think they want more technical detail overall. Let me provide focused deep-dives on the most complex parts.

**Tool: question**

**Input:**
```json
{
  "questions": [
    {
      "question": "Which layer would you like me to dive deeper into first?",
      "header": "Focus Area",
      "options": [
        {
          "label": "Alert Engine SQL & Spatial Queries",
          "description": "The EwsEvaluator detection logic — raw PostGIS queries for new arrival, range expansion, and rapid spread detection"
        },
        {
          "label": "Filament UI Details",
          "description": "Complete AlertResource, widget, infolist, table column definitions, map integration"
        },
        {
          "label": "GuardIAS/EASIN/GBIF Integration",
          "description": "Webhook contracts, API payload formats, polling schedules, data flow between MAMIAS and external systems"
        },
        {
          "label": "Notification & Channel Delivery",
          "description": "Multi-channel delivery (mail, database, WhatsApp, SMS), digests, throttling, bounce handling"
        },
        {
          "label": "All of the above",
          "description": "Full deep-dive across all technical layers"
        }
      ]
    }
  ]
}
```

**Output:**
```
User has answered your questions: "Which layer would you like me to dive deeper into first?"="All of the above". You can now continue with the user's answers in mind.
```

---

## Assistant (Plan · DeepSeek V4 Flash Free · 85.5s)

_Thinking:_

OK, the user wants "All of the above" - a full deep-dive across all technical layers. Let me produce an extremely detailed technical specification. I should present it in a readable but thorough format.

Let me organize this into sections:

1. Alert Engine - SQL/PostGIS deep dive
2. Filament UI - complete component specifications
3. GuardIAS/EASIN/GBIF Integration
4. Notification & Channel Delivery
5. Observer/job wiring details

Let me also look at some additional details I need - like the existing Subregion enum to understand the spatial boundary system, and the existing observations to understand data density.

**Tool: task**

**Input:**
```json
{
  "description": "Deep-dive spatial & Subregion details",
  "subagent_type": "explore",
  "prompt": "Read these files completely and return their full contents:\n\n1. /home/toufa/dev/2026/apps/mamias/apps/app/Enums/Subregion.php\n2. /home/toufa/dev/2026/apps/mamias/apps/app/Models/SubregionRecord.php\n3. /home/toufa/dev/2026/apps/mamias/apps/app/Models/Traits/HasSpatialLocation.php\n4. /home/toufa/dev/2026/apps/mamias/apps/app/Enums/EstablishmentStatus.php\n5. /home/toufa/dev/2026/apps/mamias/apps/app/Enums/Habitat.php\n6. /home/toufa/dev/2026/apps/mamias/apps/config/magellan.php\n7. /home/toufa/dev/2026/apps/mamias/apps/app/Enums/DataQuality.php\n8. /home/toufa/dev/2026/apps/mamias/apps/app/Enums/CbdPathwayCategory.php\n9. /home/toufa/dev/2026/apps/mamias/apps/routes/console.php\n10. /home/toufa/dev/2026/apps/mamias/apps/composer.json (look for sanctum, spatie packages)\n11. /home/toufa/dev/2026/apps/mamias/apps/app/Enums/Worms_Status.php\n\nReturn ALL contents verbatim."
}
```

**Output:**
```
<task id="ses_1067f7b76ffeqt9PUkJ76jizA4" state="completed">
<task_result>
Here are the full contents of all 11 requested files:

---

## 1. `/home/toufa/dev/2026/apps/mamias/apps/app/Enums/Subregion.php`

```php
<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Subregion: string implements HasColor, HasIcon, HasLabel
{
    case WMED = 'WMED';
    case CMED = 'CMED';
    case ADRIA = 'ADRIA';
    case EMED = 'EMED';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::WMED => 'Western Mediterranean',
            self::CMED => 'Central Mediterranean',
            self::ADRIA => 'Adriatic Sea',
            self::EMED => 'Eastern Mediterranean',
        };
    }

    public function getColor(): string|array|null
    {
        return 'primary';
    }

    public function getIcon(): ?string
    {
        return 'tabler-map-pin';
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
```

---

## 2. `/home/toufa/dev/2026/apps/mamias/apps/app/Models/SubregionRecord.php`

```php
<?php

namespace App\Models;

use App\Enums\NisStatus;
use App\Enums\Subregion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mattiverse\Userstamps\Traits\Userstamps;

#[Fillable(['intro_event_id', 'subregion', 'nis_status', 'first_arrival_year', 'notes'])]
class SubregionRecord extends Model
{
    use HasFactory, Userstamps;

    public function introEvent(): BelongsTo
    {
        return $this->belongsTo(IntroEventRecord::class, 'intro_event_id');
    }

    protected function casts(): array
    {
        return [
            'subregion' => Subregion::class,
            'nis_status' => NisStatus::class,
        ];
    }
}
```

---

## 3. `/home/toufa/dev/2026/apps/mamias/apps/app/Models/Traits/HasSpatialLocation.php`

```php
<?php

namespace App\Models\Traits;

use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait HasSpatialLocation
{
    public function initializeHasSpatialLocation(): void
    {
        $this->casts['location_point'] = Point::class;
    }

    public function scopeNear(Builder $query, float $lat, float $lng, float $meters): Builder
    {
        return $query->whereRaw(
            'ST_DWithin(location_point::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
            [$lng, $lat, $meters]
        );
    }

    public function scopeWithinBoundingBox(Builder $query, float $south, float $west, float $north, float $east): Builder
    {
        return $query
            ->whereRaw(
                'location_point && ST_MakeEnvelope(?, ?, ?, ?, 4326)',
                [$west, $south, $east, $north]
            )
            ->whereRaw(
                'ST_Intersects(location_point, ST_MakeEnvelope(?, ?, ?, ?, 4326))',
                [$west, $south, $east, $north]
            );
    }

    public function scopeOrderByDistance(Builder $query, float $lat, float $lng): Builder
    {
        return $query->orderByRaw(
            'location_point <-> ST_SetSRID(ST_MakePoint(?, ?), 4326)',
            [$lng, $lat]
        );
    }

    public function scopeWithDistanceFrom(Builder $query, float $lat, float $lng): Builder
    {
        return $query->addSelect(DB::raw(
            "ST_Distance(location_point::geography, ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326)::geography) as distance_meters"
        ));
    }
}
```

---

## 4. `/home/toufa/dev/2026/apps/mamias/apps/app/Enums/EstablishmentStatus.php`

```php
<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum EstablishmentStatus: string implements HasColor, HasIcon, HasLabel
{
    case Casual = 'Casual';
    case Established = 'Established';
    case Unknown = 'Unknown';
    case Invasive = 'Invasive';
    case DataDeficient = 'Data Deficient';
    case Excluded = 'Excluded';
    case Questionable = 'Questionable';
    case Vagrant = 'Vagrant';
    case RangeExpansion = 'Range expansion';

    public function getLabel(): ?string
    {
        return $this->value;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Casual => 'warning',
            self::Established => 'success',
            self::Unknown => 'gray',
            self::Invasive => 'danger',
            self::DataDeficient => 'gray',
            self::Excluded => 'danger',
            self::Questionable => 'warning',
            self::Vagrant => 'info',
            self::RangeExpansion => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Casual => 'tabler-clock',
            self::Established => 'tabler-circle-check',
            self::Unknown => 'tabler-help',
            self::Invasive => 'tabler-alert-triangle',
            self::DataDeficient => 'tabler-database-off',
            self::Excluded => 'tabler-x',
            self::Questionable => 'tabler-help-circle',
            self::Vagrant => 'tabler-plane-arrival',
            self::RangeExpansion => 'tabler-arrows-maximize',
        };
    }
}
```

---

## 5. `/home/toufa/dev/2026/apps/mamias/apps/app/Enums/Habitat.php`

```php
<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Habitat: string implements HasColor, HasLabel
{
    case SEAGRASS_MEADOWS = 'seagrass_meadows';
    case ROCKS = 'rocks';
    case SAND = 'sand';
    case UNKNOWN = 'unknown';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SEAGRASS_MEADOWS => 'Seagrass meadows',
            self::ROCKS => 'Rocks',
            self::SAND => 'Sand',
            self::UNKNOWN => 'Unknown',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SEAGRASS_MEADOWS => 'success',
            self::ROCKS => 'gray',
            self::SAND => 'warning',
            self::UNKNOWN => 'info',
        };
    }
}
```

---

## 6. `/home/toufa/dev/2026/apps/mamias/apps/config/magellan.php`

```php
<?php

use Clickbar\Magellan\IO\Generator\Geojson\GeojsonGenerator;
use Clickbar\Magellan\IO\Generator\WKT\WKTGenerator;

return [

    /**
     * The database schema that should be used for prefixing / accessing PostGIS functions.
     * E.g. the ST_X() function will be prefixed with the schema name and become public.ST_X().
     * You will usually only need this in custom deployments where you have a custom schema for PostGIS.
     *
     * @see https://www.postgis.net/workshops/postgis-intro/schemas.html
     */
    'schema' => 'public',

    /**
     * The generator that should be used when converting a geometry to JSON.
     * This should be a sensitive default for most WGS use-cases, but remember that
     * the GeoJSON standard is only defined for SRID 4326 and will fail otherwise. You
     * may use another generator if you need to support other SRIDs e.g. the WKT generator.
     */
    'json_generator' => GeojsonGenerator::class,

    /**
     * The generator that should be used when converting a geometry for the database.
     * You may use this variable to use a custom generator if you need any specialized handling.
     * It is recommended to use the WKT generator as a default for a readable output when debugging and best
     * compatibility.
     *
     * NOTE: GeoJSON cannot be used as a generator for geography columns, since it does not support SRIDs.
     */
    'sql_generator' => WKTGenerator::class,

    /**
     * The generator that should be used when converting a geometry to a string.
     * This should be a sensitive default for all use-cases and will be used by the
     * __toString() method on the geometry objects.
     *
     * It may also be implicitly used, when passing Geometry objects to the DB as bindings in queries.
     * NOTE: The GeoJson generator will not work for geography columns, since they do not support SRIDs.
     */
    'string_generator' => WKTGenerator::class,

    /**
     * The SRIDs, that are used to determine whether a geometry class uses a geodetic projection when trying
     * to access coordinates using the geodetic named lat/lng/alt methods. Using a SRID not present in this list
     * will raise an error stating that the projection is not lng/lat and therefore shouldn't be accessed that way
     */
    'geodetic_srids' => [
        4326,
        4267,
        4269,
    ],

    /**
     * The default SRID that will be set in the Point Geometry Class when using the makeGeodetic factory function.
     */
    'geodetic_default_srid' => 4326,
];
```

---

## 7. `/home/toufa/dev/2026/apps/mamias/apps/app/Enums/DataQuality.php`

```php
<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DataQuality: string implements HasColor, HasIcon, HasLabel
{
    case NA = 'N/A';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public function getLabel(): ?string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NA => 'gray',
            self::High => 'success',
            self::Medium => 'warning',
            self::Low => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::NA => 'tabler-number-0-small',
            self::High => 'tabler-shield-check',
            self::Medium => 'tabler-shield-exclamation',
            self::Low => 'tabler-shield-off',
        };
    }
}
```

---

## 8. `/home/toufa/dev/2026/apps/mamias/apps/app/Enums/CbdPathwayCategory.php`

```php
<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CbdPathwayCategory: string implements HasLabel
{
    case ReleaseIntoNature = '1';
    case EscapeFromConfinement = '2';
    case TransportStowaway = '3';
    case TransportContaminant = '4';
    case Corridor = '5';
    case Unaided = '6';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ReleaseIntoNature => '1. Release into Nature',
            self::EscapeFromConfinement => '2. Escape from Confinement',
            self::TransportStowaway => '3. Transport - Stowaway',
            self::TransportContaminant => '4. Transport - Contaminant',
            self::Corridor => '5. Corridor',
            self::Unaided => '6. Unaided (Natural Dispersal)',
        };
    }
}
```

---

## 9. `/home/toufa/dev/2026/apps/mamias/apps/routes/console.php`

```php
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
```

---

## 10. `/home/toufa/dev/2026/apps/mamias/apps/composer.json`

```json
{
  "$schema": "https://getcomposer.org/schema.json" ,
  "name": "laravel/laravel" ,
  "type": "project" ,
  "description": "The skeleton application for the Laravel framework." ,
  "keywords": [
    "laravel" ,
    "framework"
  ] ,
  "license": "MIT" ,
  "require": {
    "php": "^8.3",
    "agencetwogether/filament-alert-box": "^1.1",
    "al-saloul/filament-image-gallery": "^3.0",
    "aureuserp/progress-bar": "@dev",
    "azgasim/filament-unsaved-changes-modal": "^1.0",
    "bezhansalleh/filament-shield": "^4.2",
    "binarybuilds/filament-command-runner": "^1.2",
    "clcbws/laravel-schema-sentinel": "^1.6",
    "clickbar/laravel-magellan": "*",
    "cms-multi/filament-clear-cache": "^3.0",
    "daljo25/filament-dependency-manager": "^5.0",
    "daljo25/filament-tabler-icons": "^3.41",
    "defstudio/filament-searchable-input": "^5.0",
    "devonab/filament-easy-footer": "^2.2",
    "devrabiul/laravel-cookie-consent": "^2.1",
    "diglactic/laravel-breadcrumbs": "^10.1",
    "diogogpinto/filament-auth-ui-enhancer": "^2.0",
    "dutchcodingcompany/filament-developer-logins": "^2.1",
    "eduardoribeirodev/filament-leaflet": "^5.0",
    "elemind/filament-echarts": "*",
    "filament/filament": "^5.0",
    "harvirsidhu/filament-action-overflow": "^1.1",
    "icetalker/filament-stepper": "^2.0",
    "jeffersongoncalves/filament-refresh-sidebar": "^2.0",
    "laravel/framework": "^13.0",
    "laravel/tinker": "^3.0",
    "livewire/livewire": "^4.3",
    "lubusin/laravel-decomposer": "^1.4",
    "mantraideas/laravel-env-doctor": "^1.0",
    "marjose123/filament-lockscreen": "*",
    "mckenziearts/laravel-notify": "^3.2",
    "nakanakaii/filament-countries": "^1.0",
    "novadaemon/filament-pretty-json": "^4.2",
    "phpoffice/phpspreadsheet": "^5.7",
    "promethys/revive": "*",
    "pxlrbt/filament-environment-indicator": "^3.5",
    "pxlrbt/filament-spotlight": "^2.1",
    "secondnetwork/blade-tabler-icons": "^3.41",
    "shuvroroy/filament-spatie-laravel-health": "^3.3",
    "spatie/laravel-honeypot": "^4.7",
    "wildside/userstamps": "^4.0",
    "yousefaman/filament-modal-repeater": "^1.1"
  } ,
  "require-dev": {
    "andreapollastri/checkpoint": "^1.0",
    "fakerphp/faker": "^1.23",
    "fruitcake/laravel-debugbar": "^4.2",
    "laramint/laravel-brain": "*",
    "laravel/boost": "^2.4",
    "laravel/pail": "^1.2.5",
    "laravel/pint": "^1.27",
    "mockery/mockery": "^1.6",
    "nunomaduro/collision": "^8.6",
    "pestphp/pest": "^4.6",
    "pestphp/pest-plugin-laravel": "^4.1",
    "pestphp/pest-plugin-livewire": "^4.1",
    "squizlabs/php_codesniffer": "^4.0"
  } ,
  "autoload": {
    "psr-4": {
      "App\\": "app/" ,
      "Database\\Factories\\": "database/factories/" ,
      "Database\\Seeders\\": "database/seeders/"
    }
  } ,
  "autoload-dev": {
    "psr-4": {
      "Tests\\": "tests/"
    }
  } ,
  "scripts": {
    "setup": [
      "composer install" ,
      "@php -r \"file_exists('.env') || copy('.env.example', '.env');\"" ,
      "@php artisan key:generate" ,
      "@php artisan migrate --force" ,
      "npm install --ignore-scripts" ,
      "npm run build"
    ] ,
    "dev": [
      "Composer\\Config::disableProcessTimeout" ,
      "npx concurrently -c \"#93c5fd,#c4b5fd,#fb7185,#fdba74\" \"php artisan serve\" \"php artisan queue:listen --tries=1 --timeout=0\" \"php artisan pail --timeout=0\" \"npm run dev\" --names=server,queue,logs,vite --kill-others"
    ] ,
    "test": [
      "@php artisan config:clear --ansi" ,
      "@php artisan test"
    ] ,
    "post-autoload-dump": [
      "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump" ,
      "@php artisan package:discover --ansi" ,
      "@php artisan filament:upgrade"
    ] ,
    "post-update-cmd": [
      "@php artisan vendor:publish --tag=laravel-assets --ansi --force" ,
      "@php artisan boost:update --ansi"
    ] ,
    "post-root-package-install": [
      "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""
    ] ,
    "post-create-project-cmd": [
      "@php artisan key:generate --ansi" ,
      "@php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\"" ,
      "@php artisan migrate --graceful --ansi"
    ] ,
    "pre-package-uninstall": [
      "Illuminate\\Foundation\\ComposerScripts::prePackageUninstall"
    ]
  } ,
  "extra": {
    "laravel": {
      "dont-discover": [
        "fruitcake/laravel-debugbar"
      ]
    }
  } ,
  "config": {
    "optimize-autoloader": true ,
    "preferred-install": "dist" ,
    "sort-packages": true ,
    "allow-plugins": {
      "pestphp/pest-plugin": true ,
      "php-http/discovery": true
    }
  } ,
  "minimum-stability": "stable" ,
  "prefer-stable": true
}
```

**Regarding Sanctum and Spatie packages:** Looking through `composer.json`:
- **Sanctum**: There is no `laravel/sanctum` package in `require` or `require-dev`.
- **Spatie packages**: The only Spatie package present is `spatie/laravel-honeypot` (^4.7) in `require`. There is also `bezhansalleh/filament-shield` (^4.2) which wraps Spatie permissions, but the base `spatie/laravel-permission` package is not explicitly listed -- it comes as a dependency of `filament-shield`. There is no `spatie/laravel-medialibrary`, `spatie/laravel-backup`, `spatie/laravel-translatable`, etc.

---

## 11. `/home/toufa/dev/2026/apps/mamias/apps/app/Enums/Worms_Status.php`

```php
<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Worms_Status: string implements HasColor, HasIcon, HasLabel
{
    case accepted = 'accepted';
    case unaccepted = 'unaccepted';
    case nomen_dubium = 'nomen dubium';
    case nomen_nudum = 'nomen nudum';
    case taxon_inquirendum = 'taxon inquirendum';
    case interim_unpublished = 'interim unpublished';
    case deleted = 'deleted';
    case uncertain = 'uncertain';
    case alternative_representation = 'alternative representation';
    case temporary_name = 'temporary name';
    case superseded_combination = 'superseded combination';
    case junior_homonym = 'junior homonym';
    case misapplication = 'misapplication';
    case taxonomic_discrepancy = 'taxonomic discrepancy';
    case unassessed = 'unassessed';
    case misspelling_incorrect_subsequent_spelling = 'misspelling - incorrect subsequent spelling';
    case misspelling_incorrect_original_spelling = 'misspelling - incorrect original spelling';
    case junior_subjective_synonym = 'junior subjective synonym';
    case junior_objective_synonym = 'junior objective synonym';
    case nomen_oblitum = 'nomen oblitum';
    case misspelling = 'misspelling';
    case unjustified_emendation = 'unjustified emendation';
    case incorrect_grammatical_agreement = 'incorrect grammatical agreement';
    case unavailable_name = 'unavailable name';
    case superseded_rank = 'superseded rank';
    case nomen_rejiciendum = 'nomen rejiciendum';
    case unreplaced_junior_homonym = 'unreplaced junior homonym';
    case incertae_sedis = 'incertae sedis';

    public function getLabel(): string
    {
        return match ($this) {
            self::accepted => 'Accepted',
            self::unaccepted => 'Unaccepted',
            self::nomen_dubium => 'Nomen dubium',
            self::nomen_nudum => 'Nomen nudum',
            self::taxon_inquirendum => 'Taxon inquirendum',
            self::interim_unpublished => 'Interim unpublished',
            self::deleted => 'Deleted',
            self::uncertain => 'Uncertain',
            self::alternative_representation => 'Alternative representation',
            self::temporary_name => 'Temporary name',
            self::superseded_combination => 'Superseded combination',
            self::junior_homonym => 'Junior homonym',
            self::misapplication => 'Misapplication',
            self::taxonomic_discrepancy => 'Taxonomic discrepancy',
            self::unassessed => 'Unassessed',
            self::misspelling_incorrect_subsequent_spelling => 'Misspelling - incorrect subsequent spelling',
            self::misspelling_incorrect_original_spelling => 'Misspelling - incorrect original spelling',
            self::junior_subjective_synonym => 'Junior subjective synonym',
            self::junior_objective_synonym => 'Junior objective synonym',
            self::nomen_oblitum => 'Nomen oblitum',
            self::misspelling => 'Misspelling',
            self::unjustified_emendation => 'Unjustified emendation',
            self::incorrect_grammatical_agreement => 'Incorrect grammatical agreement',
            self::unavailable_name => 'Unavailable name',
            self::superseded_rank => 'Superseded rank',
            self::nomen_rejiciendum => 'Nomen rejiciendum',
            self::unreplaced_junior_homonym => 'Unreplaced junior homonym',
            self::incertae_sedis => 'Incertae sedis',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::accepted => 'success',
            self::unaccepted, self::superseded_combination, self::junior_homonym, self::misapplication, self::misspelling_incorrect_subsequent_spelling, self::misspelling_incorrect_original_spelling, self::junior_subjective_synonym, self::junior_objective_synonym, self::nomen_oblitum, self::misspelling, self::unjustified_emendation, self::incorrect_grammatical_agreement, self::unavailable_name, self::superseded_rank, self::nomen_rejiciendum, self::unreplaced_junior_homonym => 'danger',
            self::nomen_dubium, self::nomen_nudum, self::taxon_inquirendum, self::taxonomic_discrepancy, self::unassessed, self::incertae_sedis => 'warning',
            default => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::accepted => 'tabler-circle-check',
            self::unaccepted, self::superseded_combination, self::junior_homonym, self::misapplication, self::misspelling_incorrect_subsequent_spelling, self::misspelling_incorrect_original_spelling, self::junior_subjective_synonym, self::junior_objective_synonym, self::nomen_oblitum, self::misspelling, self::unjustified_emendation, self::incorrect_grammatical_agreement, self::unavailable_name, self::superseded_rank, self::nomen_rejiciendum, self::unreplaced_junior_homonym => 'tabler-circle-x',
            self::nomen_dubium, self::nomen_nudum, self::taxon_inquirendum, self::taxonomic_discrepancy, self::unassessed, self::incertae_sedis => 'tabler-alert-circle',
            default => 'tabler-help-circle',
        };
    }
}
```

---

All 11 files have been read and returned verbatim above.
</task_result>
</task>
```

**Tool: task**

**Input:**
```json
{
  "description": "Read existing alert-related files",
  "subagent_type": "explore",
  "prompt": "Read these files completely and return their full contents:\n\n1. /home/toufa/dev/2026/apps/mamias/apps/app/Filament/Widgets/PendingOccurrencesWidget.php\n2. /home/toufa/dev/2026/apps/mamias/apps/app/Filament/Widgets/CatalogueStatsWidget.php\n3. /home/toufa/dev/2026/apps/mamias/apps/app/Filament/Pages/Dashboard.php\n4. /home/toufa/dev/2026/apps/mamias/apps/app/Providers/AppServiceProvider.php  \n5. /home/toufa/dev/2026/apps/mamias/apps/app/Http/Middleware/RedirectIfNotPanelUser.php\n6. /home/toufa/dev/2026/apps/mamias/apps/routes/web.php\n7. /home/toufa/dev/2026/apps/mamias/apps/app/Filament/Resources/IntroEventRecordResource.php\n8. /home/toufa/dev/2026/apps/mamias/apps/app/Filament/Resources/IntroEventRecord/Schemas/IntroEventRecordForm.php (or wherever the form is)\n9. /home/toufa/dev/2026/apps/mamias/apps/app/Filament/Resources/Occurrences/ - list this directory\n10. /home/toufa/dev/2026/apps/mamias/apps/resources/views/livewire/ - list this directory\n\nReturn ALL contents verbatim."
}
```

**Output:**
```
<task id="ses_1067f6ff0ffe8mpB39ZFbIaH8L" state="completed">
<task_result>
Here are the complete contents of all requested files and directories:

---

## 1. `/home/toufa/dev/2026/apps/mamias/apps/app/Filament/Widgets/PendingOccurrencesWidget.php`

```php
<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\IntroEventRecords\IntroEventRecordResource;
use App\Models\Occurrence;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendingOccurrencesWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $pendingCount = Occurrence::where('status', 'pending')->count();

        return [
            Stat::make('Pending Occurrences', $pendingCount)
                ->description('Awaiting moderation review')
                ->descriptionIcon('tabler-clock')
                ->color('warning')
                ->url(IntroEventRecordResource::getUrl('index')),
        ];
    }
}
```

---

## 2. `/home/toufa/dev/2026/apps/mamias/apps/app/Filament/Widgets/CatalogueStatsWidget.php`

```php
<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\Catalogue_Status;
use App\Models\Taxon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CatalogueStatsWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'MAMIAS Catalogue';

    protected function getStats(): array
    {
        $stats = $this->getCatalogueStatistics();

        return [
            $this->createTotalSpeciesStat($stats),
            $this->createAcceptedStat($stats),
            $this->createNotAcceptedStat($stats),
            $this->createNotCheckedStat($stats),
        ];
    }

    protected function getCatalogueStatistics(): array
    {
        $totalSpecies = Taxon::count();
        $checkedAccepted = Taxon::where('catalogue_status', Catalogue_Status::checked_accepted->value)->count();
        $checkedNotAccepted = Taxon::where('catalogue_status', Catalogue_Status::checked_not_accepted->value)->count();
        $notChecked = Taxon::where(function ($query) {
            $query
                ->whereNull('catalogue_status')
                ->orWhere('catalogue_status', Catalogue_Status::not_checked->value)
                ->orWhere('catalogue_status', Catalogue_Status::no_data_from_worms->value);
        })->count();

        return [
            'total' => $totalSpecies,
            'accepted' => $checkedAccepted,
            'not_accepted' => $checkedNotAccepted,
            'not_checked' => $notChecked,
            'accepted_percentage' => $this->calculatePercentage($checkedAccepted, $totalSpecies),
            'not_accepted_percentage' => $this->calculatePercentage($checkedNotAccepted, $totalSpecies),
            'not_checked_percentage' => $this->calculatePercentage($notChecked, $totalSpecies),
        ];
    }

    protected function calculatePercentage(int|float $part, int|float $total): float
    {
        return $total > 0 ? round(($part / $total) * 100, 1) : 0.0;
    }

    protected function createTotalSpeciesStat(array $stats): Stat
    {
        return Stat::make('Total Species in Catalogue', $stats['total'])
            ->description('All species records')
            ->descriptionIcon('tabler-list')
            ->chart($this->generateChartData($stats['total']))
            ->color('primary');
    }

    protected function createAcceptedStat(array $stats): Stat
    {
        return Stat::make('Checked & Accepted', $stats['accepted'])
            ->description("{$stats['accepted_percentage']}% of total")
            ->descriptionIcon('tabler-circle-check')
            ->chart($this->generateChartData($stats['accepted']))
            ->color('success');
    }

    protected function createNotAcceptedStat(array $stats): Stat
    {
        return Stat::make('Checked & Not Accepted', $stats['not_accepted'])
            ->description("{$stats['not_accepted_percentage']}% of total")
            ->descriptionIcon('tabler-circle-x')
            ->chart($this->generateChartData($stats['not_accepted']))
            ->color('danger');
    }

    protected function createNotCheckedStat(array $stats): Stat
    {
        return Stat::make('Not Checked Yet', $stats['not_checked'])
            ->description("{$stats['not_checked_percentage']}% of total")
            ->descriptionIcon('tabler-clock')
            ->chart($this->generateChartData($stats['not_checked']))
            ->color('warning');
    }

    protected function generateChartData(int $value): array
    {
        return [70, 50, 60, 80, 75, 90, max($value, 1)];
    }
}
```

---

## 3. `/home/toufa/dev/2026/apps/mamias/apps/app/Filament/Pages/Dashboard.php`

```php
<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\CatalogueEnvironmentChart;
use App\Filament\Widgets\CatalogueStatsWidget;
use App\Filament\Widgets\PendingOccurrencesTableWidget;
use App\Filament\Widgets\PendingOccurrencesWidget;
use App\Filament\Widgets\PhylumByKingdomChart;
use App\Filament\Widgets\SpeciesByKingdomChart;
use App\Filament\Widgets\SpeciesByPhylumChart;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('dashboard')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('MAMIAS Catalogue')
                            ->icon('tabler-fish')
                            ->schema([
                                Grid::make(4)->schema(
                                    $this->getWidgetsSchemaComponents([
                                        CatalogueStatsWidget::class,
                                    ])
                                ),
                                Grid::make(2)->schema([
                                    Livewire::make(SpeciesByKingdomChart::class),
                                    Livewire::make(SpeciesByPhylumChart::class),
                                ]),
                                Grid::make(2)->schema([
                                    Livewire::make(PhylumByKingdomChart::class),
                                    Livewire::make(CatalogueEnvironmentChart::class),
                                ]),
                            ]),
                        Tab::make('MAMIAS Data')
                            ->icon('tabler-database')
                            ->schema([
                                Grid::make(4)->schema(
                                    $this->getWidgetsSchemaComponents([
                                        PendingOccurrencesWidget::class,
                                    ])
                                ),
                                Livewire::make(PendingOccurrencesTableWidget::class),
                            ]),
                    ]),
            ]);
    }
}
```

---

## 4. `/home/toufa/dev/2026/apps/mamias/apps/app/Providers/AppServiceProvider.php`

```php
<?php

namespace App\Providers;

use App\Filament\Auth\Responses\EmailVerificationResponse;
use App\Filament\Auth\Responses\LoginResponse;
use App\Filament\Auth\Responses\RegistrationResponse;
use App\Listeners\TaxonImportCompletedListener;
use App\Livewire\ImportWizard;
use Filament\Actions\Imports\Events\ImportCompleted;
use Filament\Auth\Http\Responses\Contracts\EmailVerificationResponse as EmailVerificationResponseContract;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse as RegistrationResponseContract;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LoginResponseContract::class, LoginResponse::class);
        $this->app->bind(RegistrationResponseContract::class, RegistrationResponse::class);
        $this->app->bind(EmailVerificationResponseContract::class, EmailVerificationResponse::class);

        if ($this->app->isLocal() && class_exists(\Fruitcake\LaravelDebugbar\ServiceProvider::class)) {
            $this->app->register(\Fruitcake\LaravelDebugbar\ServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentColor::register([
            'primary' => [
                50 => '#f0f9fb',
                100 => '#d9f0f4',
                200 => '#b7e2ea',
                300 => '#85ccd9',
                400 => '#4cafbf',
                500 => '#00899d',
                600 => '#007a8c',
                700 => '#006b7a',
                800 => '#005f6b',
                900 => '#004e59',
                950 => '#00353d',
            ],
        ]);

        Livewire::component('filament-import-wizard', ImportWizard::class);

        Event::listen(ImportCompleted::class, TaxonImportCompletedListener::class);

        Health::checks([
            OptimizedAppCheck::new(),
            DebugModeCheck::new(),
            EnvironmentCheck::new(),
            DatabaseCheck::new(),
            RedisCheck::new(),
            UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage(80)
                ->failWhenUsedSpaceIsAbovePercentage(90),
            CacheCheck::new(),
            QueueCheck::new(),
        ]);
    }
}
```

---

## 5. `/home/toufa/dev/2026/apps/mamias/apps/app/Http/Middleware/RedirectIfNotPanelUser.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotPanelUser
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($request->routeIs([
            'filament.*.auth.*',
            'filament-developer-logins.login-as',
        ])) {
            return $next($request);
        }

        if ($user && ! ($user->hasRole('super_admin') || $user->hasRole('scientist'))) {
            return redirect('/');
        }

        return $next($request);
    }
}
```

---

## 6. `/home/toufa/dev/2026/apps/mamias/apps/routes/web.php`

```php
<?php

use App\Livewire\MyReferences;
use App\Livewire\MySpeciesReports;
use App\Livewire\MySuggestions;
use App\Livewire\PublicProfile;
use Illuminate\Support\Facades\Route;
use Lubusin\Decomposer\Controllers\DecomposerController;

Route::get('/login', function () {
    return redirect()->route('filament.mamias.auth.login');
})->name('login');
Route::get('/email-verification/prompt', function () {
    return redirect()->route('filament.mamias.auth.email-verification.prompt');
})->name('verification.notice');

Route::get('/', function () {
    return view('mamias.home');
})->name('home');

Route::get('/about', function () {
    return view('mamias.about');
})->name('about');

Route::get('/profile', PublicProfile::class)
    ->middleware(['auth', 'verified'])
    ->name('profile');

Route::get('/references', MyReferences::class)
    ->middleware(['auth', 'verified'])
    ->name('references');

Route::get('/my-species-reports', MySpeciesReports::class)
    ->middleware(['auth', 'verified'])
    ->name('my-species-reports');

Route::get('/my-suggestions', MySuggestions::class)
    ->middleware(['auth', 'verified'])
    ->name('suggestions');

Route::get('mamias/decompose', [DecomposerController::class, 'index'])
    ->middleware(['auth', 'role:super_admin'])
    ->name('decompose');
```

---

## 7. `/home/toufa/dev/2026/apps/mamias/apps/app/Filament/Resources/IntroEventRecords/IntroEventRecordResource.php`

**Note:** The actual path is `IntroEventRecords` (plural), not `IntroEventRecord` (singular).

```php
<?php

namespace App\Filament\Resources\IntroEventRecords;

use App\Filament\Resources\IntroEventRecords\Pages\CreateIntroEventRecord;
use App\Filament\Resources\IntroEventRecords\Pages\EditIntroEventRecord;
use App\Filament\Resources\IntroEventRecords\Pages\ListIntroEventRecords;
use App\Filament\Resources\IntroEventRecords\RelationManagers\OccurrencesRelationManager;
use App\Filament\Resources\IntroEventRecords\Schemas\IntroEventRecordForm;
use App\Filament\Resources\IntroEventRecords\Tables\IntroEventRecordsTable;
use App\Models\IntroEventRecord;
use BackedEnum;
use Daljo25\FilamentTablerIcons\Enums\TablerIcon;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class IntroEventRecordResource extends Resource
{
    protected static ?string $model = IntroEventRecord::class;

    protected static string|BackedEnum|null $navigationIcon = TablerIcon::CalendarEvent;

    protected static ?string $modelLabel = 'Intro Event';

    protected static ?int $navigationSort = 3;

    protected static ?string $pluralModelLabel = 'Intro Events';

    protected static ?string $navigationLabel = 'Introduction Events ';

    protected static string|null|\UnitEnum $navigationGroup = 'MAMIAS database';

    protected static ?string $recordTitleAttribute = 'NIS Data';

    public static function form(Schema $schema): Schema
    {
        return IntroEventRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntroEventRecordsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            OccurrencesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIntroEventRecords::route('/'),
            'create' => CreateIntroEventRecord::route('/create'),
            'edit' => EditIntroEventRecord::route('/{record}/edit'),
        ];
    }
}
```

---

## 8. `/home/toufa/dev/2026/apps/mamias/apps/app/Filament/Resources/IntroEventRecords/Schemas/IntroEventRecordForm.php`

**Note:** The actual path is `IntroEventRecords/Schemas/IntroEventRecordForm.php` (plural).

```php
<?php

namespace App\Filament\Resources\IntroEventRecords\Schemas;

use App\Enums\AcforScale;
use App\Enums\CbdPathwayCategory;
use App\Enums\CbdPathwaySubcategory;
use App\Enums\DataQuality;
use App\Enums\EstablishmentStatus;
use App\Enums\Habitat;
use App\Enums\NisStatus;
use App\Enums\PathwayType;
use App\Enums\Subregion;
use App\Filament\Forms\Components\CountrySelectWithMedPriority;
use App\Filament\Forms\MultipleMarkersMapPicker;
use EduardoRibeiroDev\FilamentLeaflet\Enums\TileLayer;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Icetalker\FilamentStepper\Forms\Components\Stepper;

class IntroEventRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Species Identification')
                    ->icon('tabler-fish')
                    ->description('Select the non-indigenous species and classify its introduction status.')
                    ->compact()
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 3, 'lg' => 5])->schema([
                            Select::make('taxon_id')
                                ->label('NIS Taxon')
                                ->relationship('taxon', 'scientificname')
                                ->getOptionLabelFromRecordUsing(fn ($record) => "<i>{$record->scientificname}</i>".($record->authority ? " ({$record->authority})" : ''))
                                ->allowHtml()
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(['default' => 1, 'md' => 3, 'lg' => 1]),
                            Select::make('nis_status')
                                ->options(NisStatus::class)
                                ->label('NIS Status')
                                ->native(false)
                                ->placeholder('Select status')
                                ->columnSpan(1),
                            Select::make('establishment_status')
                                ->options(EstablishmentStatus::class)
                                ->label('Establishment Status')
                                ->native(false)
                                ->placeholder('Select status')
                                ->columnSpan(1),
                            Stepper::make('first_introduction_year')
                                ->label('Year of 1st Introduction')
                                ->minValue(1800)
                                ->maxValue(now()->year)
                                ->step(1)
                                ->default(now()->year)
                                ->columnSpan(1),
                            CountrySelectWithMedPriority::make('first_country')
                                ->displayFlags(true)
                                ->imageFlags()
                                ->multiple()
                                ->label('1st Country of Introduction')
                                ->columnSpan(['default' => 1, 'md' => 3, 'lg' => 1]),
                        ]),
                    ]),

                Section::make('References & Notes')
                    ->icon('tabler-notes')
                    ->compact()
                    ->columnSpanFull()
                    ->schema([
                        Select::make('literature_id')
                            ->relationship('literature', 'short_ref')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->label('Citations / Literature')
                            ->placeholder('Search literature...')
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->placeholder('Additional observations or context...')
                            ->columnSpanFull(),
                    ]),

                Tabs::make('Details')
                    ->persistTabInQueryString()
                    ->contained(false)
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('EcAp Subregions')
                            ->icon('tabler-map')
                            ->schema([
                                Repeater::make('subregionRecords')
                                    ->hiddenLabel()
                                    ->table([
                                        TableColumn::make('EcAp Sub-region'),
                                        TableColumn::make('Establishment Success'),
                                        TableColumn::make('Year of 1st Introduction'),
                                    ])
                                    ->addActionLabel('Add Subregion Record')
                                    ->compact()
                                    ->minItems(0)
                                    ->maxItems(4)
                                    ->relationship()
                                    ->schema([
                                        Select::make('subregion')
                                            ->label('EcAp Sub-region')
                                            ->placeholder('Select subregion')
                                            ->searchable()
                                            ->preload()
                                            ->options(Subregion::class)
                                            ->columnSpan(2),
                                        Select::make('nis_status')
                                            ->label('Establishment Success')
                                            ->options(EstablishmentStatus::class)
                                            ->columnSpan(2),
                                        Stepper::make('first_arrival_year')
                                            ->label('Year of 1st Introduction')
                                            ->minValue(1800)
                                            ->maxValue(now()->year)
                                            ->step(1)
                                            ->default(now()->year)
                                            ->columnSpan(1)
                                            ->extraAttributes(['class' => 'max-w-28']),
                                    ])
                                    ->columns(5),
                            ]),
                        Tab::make('Pathways')
                            ->icon('tabler-route')
                            ->schema([
                                Repeater::make('pathwayRecords')
                                    ->hiddenLabel()
                                    ->table([
                                        TableColumn::make('Pathway Type'),
                                        TableColumn::make('CBD Category'),
                                        TableColumn::make('Subcategory'),
                                        TableColumn::make('Uncertainty'),
                                    ])
                                    ->addActionLabel('Add Pathway')
                                    ->compact()
                                    ->minItems(0)
                                    ->maxItems(4)
                                    ->relationship()
                                    ->schema([
                                        Select::make('pathway_type')
                                            ->label('Pathway Type')
                                            ->options(PathwayType::class)
                                            ->placeholder('Select type'),
                                        Select::make('category')
                                            ->label('CBD Category')
                                            ->options(CbdPathwayCategory::class)
                                            ->placeholder('Select category')
                                            ->live()
                                            ->afterStateUpdated(fn ($set) => $set('subcategory', null)),
                                        Select::make('subcategory')
                                            ->label('Subcategory')
                                            ->placeholder('Select subcategory')
                                            ->options(function ($get) {
                                                $category = $get('category');

                                                if (! $category) {
                                                    return [];
                                                }

                                                $categoryValue = $category instanceof CbdPathwayCategory ? $category->value : $category;

                                                return collect(CbdPathwaySubcategory::cases())
                                                    ->filter(fn (CbdPathwaySubcategory $case) => str_starts_with($case->value, (string) $categoryValue.'.'))
                                                    ->mapWithKeys(fn (CbdPathwaySubcategory $case) => [$case->value => $case->getLabel()]);
                                            }),
                                        Select::make('uncertainty')
                                            ->label('Uncertainty')
                                            ->options(DataQuality::class)
                                            ->placeholder('Select level'),
                                    ]),
                            ]),
                        Tab::make('Occurrences')
                            ->icon('tabler-map-pin')
                            ->schema(static::occurrenceSchema()),
                    ]),
            ]);
    }

    /**
     * @return array<Component>
     */
    public static function occurrenceSchema(): array
    {
        return [
            Repeater::make('occurrences')
                ->hiddenLabel()
                ->addActionLabel('Add Occurrence')
                ->compact()
                ->minItems(0)
                ->maxItems(4)
                ->relationship()
                ->schema([
                    Hidden::make('user_id')
                        ->default(fn (): int => auth()->id()),
                    Grid::make(['default' => 1, 'md' => 2, 'lg' => 4])->schema([
                        Stepper::make('depth')
                            ->label('Depth (m)')
                            ->minValue(0)
                            ->maxValue(11000)
                            ->step(1),
                        Select::make('acfor_scale')
                            ->label('Abundance (ACFOR)')
                            ->options(AcforScale::class)
                            ->native(false)
                            ->placeholder('Select ACFOR scale'),
                        Select::make('habitats')
                            ->label('Habitats')
                            ->multiple()
                            ->options(Habitat::class)
                            ->native(false)
                            ->placeholder('Select habitats'),
                        DateTimePicker::make('observed_at')
                            ->label('Date & Time of Observation')
                            ->required()
                            ->default(now())
                            ->seconds(false)
                            ->displayFormat('Y-m-d H:i'),
                    ])->columnSpanFull(),
                    MultipleMarkersMapPicker::make('location')
                        ->hiddenLabel()
                        ->height(250)
                        ->center([36, 14])
                        ->zoom(5)
                        ->tileLayersUrl(TileLayer::OpenStreetMap)
                        ->pickMarker(fn (Marker $marker) => $marker->red())
                        ->extraAttributes(['x-on:x-modal-opened.window' => 'setTimeout(() => mapCore?.map?.invalidateSize(), 50); setTimeout(() => mapCore?.map?.invalidateSize(), 300);'])
                        ->columnSpanFull(),
                    FileUpload::make('photo_paths')
                        ->label('Photos')
                        ->panelLayout('grid')
                        ->loadingIndicatorPosition('left')
                        ->panelAspectRatio('8:1')
                        ->removeUploadedFileButtonPosition('right')
                        ->uploadButtonPosition('left')
                        ->uploadProgressIndicatorPosition('left')
                        ->multiple()
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->disk('public')
                        ->directory('occurrences/photos')
                        ->visibility('public')
                        ->maxSize(5120)
                        ->imagePreviewHeight('40')
                        ->columnSpanFull(),
                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ];
    }
}
```

---

## 9. `/home/toufa/dev/2026/apps/mamias/apps/app/Filament/Resources/Occurrences/` -- Directory listing

```
Actions/
Schemas/
Tables/
```

(3 subdirectories)

---

## 10. `/home/toufa/dev/2026/apps/mamias/apps/resources/views/livewire/` -- Directory listing

```
add-literature.blade.php
my-references.blade.php
my-species-reports.blade.php
my-suggestions.blade.php
public-profile.blade.php
```

(5 Blade view files)

---

All requested files and directories have been returned verbatim. Note the corrected paths for items 7 and 8 -- the resource namespace is `IntroEventRecords` (plural), not `IntroEventRecord` (singular), and the `IntroEventRecordResource.php` file is located under `.../Resources/IntroEventRecords/...`.
</task_result>
</task>
```

**Tool: task**

**Input:**
```json
{
  "description": "Read views and tailwind files",
  "subagent_type": "explore",
  "prompt": "Read these files completely and return their full contents:\n\n1. /home/toufa/dev/2026/apps/mamias/apps/resources/views/livewire/my-suggestions.blade.php\n2. /home/toufa/dev/2026/apps/mamias/apps/resources/views/app.blade.php \n3. /home/toufa/dev/2026/apps/mamias/apps/resources/views/components/layouts/app.blade.php\n4. /home/toufa/dev/2026/apps/mamias/apps/resources/views/mamias/home.blade.php\n5. /home/toufa/dev/2026/apps/mamias/apps/app/Livewire/MySpeciesReports.php\n6. /home/toufa/dev/2026/apps/mamias/apps/tailwind.config.js or tailwind.config.ts or app/css (check tailwind v4 setup)\n7. /home/toufa/dev/2026/apps/mamias/apps/resources/css/filament/mamias/theme.css\n8. /home/toufa/dev/2026/apps/mamias/apps/vite.config.js\n\nReturn ALL contents verbatim."
}
```

**Output:**
```
<task id="ses_1067f66fbffeu9ay0x1esfSrpy" state="completed">
<task_result>
Here are the full contents of all requested files:

---

## 1. `/home/toufa/dev/2026/apps/mamias/apps/resources/views/livewire/my-suggestions.blade.php`

```blade
<div>
    @section('title', 'My Species Suggestions')

    @section('breadcrumbs')
        {{ Breadcrumbs::render('suggestions') }}
    @endsection

    <div class="space-y-8">
        <style>
            .stats-grid { display: grid; grid-template-columns: repeat(1, 1fr); gap: 1rem; }
            @media (width >= 48rem) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
            @media (width >= 64rem) { .stats-grid { grid-template-columns: repeat(4, 1fr); } }
        </style>
        <div class="stats-grid">
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total</p>
                <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $stats['total'] }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm font-medium text-amber-600 dark:text-amber-400">Pending</p>
                <p class="mt-1 text-2xl font-semibold text-amber-600 dark:text-amber-400">{{ $stats['pending'] }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Approved</p>
                <p class="mt-1 text-2xl font-semibold text-emerald-600 dark:text-emerald-400">{{ $stats['approved'] }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm font-medium text-red-600 dark:text-red-400">Rejected</p>
                <p class="mt-1 text-2xl font-semibold text-red-600 dark:text-red-400">{{ $stats['rejected'] }}</p>
            </div>
        </div>

        {{ $this->table }}
    </div>
</div>
```

---

## 2. `/home/toufa/dev/2026/apps/mamias/apps/resources/views/app.blade.php`

```blade
<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr"
      lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <base href="../../">
    <title>
        @hasSection('title')
            MAMIAS :: @yield('title') | Since 2012
        @elseif(isset($pageTitle))
            MAMIAS :: {{ $pageTitle }} | Since 2012
        @else
            MAMIAS | Since 2012
        @endif
    </title>
    <meta charset="utf-8"/>
    <meta content="follow, index" name="robots"/>
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport"/>
    <meta content="" property="og:description"/>
    <meta content="assets/media/app/og-image.png" property="og:image"/>
    <link href="{{ asset('img/apple-touch-icon.png') }}" rel="apple-touch-icon" sizes="180x180"/>
    <link href="{{ asset('img/favicon-32x32.png') }}" rel="icon" sizes="32x32" type="image/png"/>
    <link href="{{ asset('img/favicon-16x16.png') }}" rel="icon" sizes="16x16" type="image/png"/>
    <link href="{{ asset('img/favicon.ico') }}" rel="shortcut icon"/>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet"/>
    @vite(['resources/css/app.css'])
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet"/>


    {!! \Filament\Support\Facades\FilamentAsset::getTheme('app', 'filament/filament')->getHtml() !!}
    @filamentStyles
    @livewireStyles
    @stack('styles')
    @stack('head')
    <!-- Add Laravel Notify CSS -->
    @notifyCss
    <style>
        [x-cloak] {
            display: none !important;
        }
        .notify .border-green-500 {
            border-color: #00899d !important;
        }
        .notify .text-green-400 {
            color: #00899d !important;
        }
    </style>
    <style>
        @media (width >= 64rem) {
            #navbar { display: flex !important; }
        }
        @media (width < 64rem) {
            #navbar { display: none; }
            #navbar.open { display: flex; }
        }

        .mobile-notice { display: flex; }
        .desktop-content { display: none; }

        @media (width >= 48rem) {
            .mobile-notice { display: none; }
            .desktop-content { display: block; }
        }
    </style>

    {!! CookieConsent::styles() !!}
</head>
<body
    class="light antialiased flex flex-col min-h-screen text-base text-foreground bg-background [--header-height:78px]">
<!-- Theme Mode -->
<script>
    const defaultThemeMode = 'light'; // light|dark|system
    let themeMode;

    if (document.documentElement) {
        if (localStorage.getItem('kt-theme')) {
            themeMode = localStorage.getItem('kt-theme');
        } else if (document.documentElement.hasAttribute('data-kt-theme-mode')) {
            themeMode = document.documentElement.getAttribute('data-kt-theme-mode');
        } else {
            themeMode = defaultThemeMode;
        }

        if (themeMode === 'system') {
            themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        document.documentElement.classList.add(themeMode);
    }
</script>
<!-- End of Theme Mode -->

<!-- Main -->
<div class="flex grow flex-col in-data-kt-[sticky-header=on]:pt-(--header-height)">
    <!-- Header -->
    <header class="flex items-center transition-[height] shrink-0 bg-background h-(--header-height)"
            data-kt-sticky="true"
            data-kt-sticky-class="transition-[height] fixed z-10 top-0 left-0 right-0 shadow-xs backdrop-blur-md bg-background/70 border border-border"
            data-kt-sticky-name="header" data-kt-sticky-offset="100px" id="header">
        <!-- Container -->
        <div class="kt-container-fixed flex lg:justify-between items-center gap-2.5">
            <!-- Logo -->
            <div class="flex items-center gap-1 lg:w-[400px] grow lg:grow-0">
                <button class="kt-btn kt-btn-icon kt-btn-ghost -ms-2.5 lg:hidden" data-kt-drawer-toggle="#navbar">
                    <i class="ki-filled ki-menu"></i>
                </button>
                <div class="flex items-center gap-2">
                    <a class="flex items-center shrink-0" href="{{ route('home') }}">
                        <img class="dark:hidden w-mamias shrink-0" src="{{ asset('images/Logoweb.png') }}"/>
                        <img class="hidden dark:inline-block w-mamias shrink-0"
                             src="{{ asset('images/mamias_b.png') }}"/>
                    </a>
                </div>
                <!-- Navs -->
                <div class="hidden lg:flex items-center">
                    <div class="border-e border-border h-5 mx-4"></div>
                    <h3 class="text-mono text-lg font-medium hidden md:block">MAMIAS</h3>
                </div>
                <!-- End of Navs -->
            </div>
            <!-- End of Logo -->

            <!-- Topbar -->
            @include('partials.usermenu')
            <!-- End of Topbar -->
        </div>
        <!-- End of Container -->
    </header>
    <!-- End of Header -->

    <!-- Navbar -->
    @include('partials.navbar')
    <!-- End of Navbar -->

    <!-- Wrapper Container -->
    <div class="container-fixed w-full flex grow px-0">
        <!-- Content -->
        <x-notify::notify />
        <main class="flex flex-col grow" id="content" role="content">
            <!-- Toolbar -->
            <div class="mb-5 lg:mb-7.5">
                <div class="kt-container-fixed flex items-center justify-between flex-wrap gap-5">
                    <div class="flex flex-col justify-center items-start flex-wrap gap-1 lg:gap-2">
                        <h1 class="font-medium text-lg text-mono">@hasSection('title')
                                @yield('title')
                            @elseif(isset($pageTitle))
                                {{ $pageTitle }}
                            @endif</h1>
                        <div class="flex items-center gap-1 text-sm font-normal">
                            @hasSection('breadcrumbs')
                                @yield('breadcrumbs')
                            @else
                                {{ Breadcrumbs::render('home') }}
                            @endif
                        </div>
                    </div>
                    {{--                     <div class="flex items-center flex-wrap gap-1.5 lg:gap-3.5"> --}}
                    {{--                         <a class="kt-btn kt-btn-sm kt-btn-outline" href="#"> --}}
                    {{--                             <i class="ki-filled ki-exit-down"></i> --}}
                    {{--                             Export --}}
                    {{--                         </a> --}}
                    {{--                     </div> --}}
                </div>
            </div>
            <!-- End of Toolbar -->

            <!-- Mobile notice -->
            <div class="mobile-notice kt-container-fixed grow">
                <div class="flex flex-col items-center justify-center text-center px-6" style="min-height: 60vh;">
                    <svg class="w-16 h-16 text-primary mb-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                    <h2 class="text-xl font-semibold text-mono mb-2">Optimized for Larger Screens</h2>
                    <p class="text-base text-muted-foreground max-w-md">This website is best viewed on a tablet or desktop computer. Please switch to a larger screen for the full experience.</p>
                </div>
            </div>

            <!-- Content -->
            <div class="desktop-content kt-container-fixed grow">
                <div class="grid gap-5 lg:gap-7.5">
                    @yield('content')
                </div>
            </div>
            <!-- End of Content -->

            <!-- Footer -->
            <footer class="footer">
                <div class="kt-container-fixed">
                    <div class="flex flex-col items-center gap-3 py-5 md:flex-row md:justify-between">
                        <div class="flex order-2 gap-2 text-sm font-normal md:order-1">
                            <span class="text-muted-foreground">{{ now()->format('Y') }}©</span>
                            <a class="text-secondary-foreground hover:text-primary"
                               href="https://spa-rac.org">SPA/RAC.</a>
                        </div>
                        <nav class="flex order-1 flex-wrap justify-center gap-x-4 gap-y-1 text-sm font-normal text-secondary-foreground md:order-2">
                            <a class="hover:text-primary" href="#">Legal notice</a>
                            <a class="hover:text-primary" href="#">Terme of Use</a>
                            <a class="hover:text-primary" href="#">Cookies policy</a>
                            <a class="hover:text-primary" onclick="showHideToggleCookiePreferencesModal()">Change Cookie
                                Preferences</a>
                            <a class="hover:text-primary" href="{{ url('sitemap.xml') }}">SiteMap</a>
                        </nav>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->
        </main>
        <!-- End of Content -->
    </div>
    <!-- End of Wrapper Container -->
</div>
<!-- End of Main -->

<!-- Scripts -->
@filamentScripts
@livewireScripts
@notifyJs
{!! CookieConsent::scripts() !!}
@stack('scripts')
<script>
    document.addEventListener('x-modal-opened', () => {
        setTimeout(() => {
            document.querySelectorAll('[x-data^="leafletMapField"]').forEach((el) => {
                const data = window.Alpine?.$data(el);
                if (data?.mapCore?.map) {
                    data.mapCore.map.invalidateSize();
                }
            });
        }, 100);
    });
</script>
<script src="{{ asset('assets/js/core.bundle.js') }}"></script>
<script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}"></script>
<script src="{{ asset('assets/js/widgets/general.js') }}"></script>
<script>
    document.addEventListener('livewire:navigated', () => {
        if (window.KTMenu && typeof KTMenu.init === 'function') {
            KTMenu.init();
        }
        if (window.KTDropdown && typeof KTDropdown.reinit === 'function') {
            KTDropdown.reinit();
        } else if (window.KTDropdown && typeof KTDropdown.init === 'function') {
            KTDropdown.init();
        }
        if (window.KTDrawer && typeof KTDrawer.reinit === 'function') {
            KTDrawer.reinit();
        } else if (window.KTDrawer && typeof KTDrawer.init === 'function') {
            KTDrawer.init();
        }
    });
</script>
<!-- End of Scripts -->
</body>
</html>
```

---

## 3. `/home/toufa/dev/2026/apps/mamias/apps/resources/views/components/layouts/app.blade.php`

**File not found.** This path does not exist.

---

## 4. `/home/toufa/dev/2026/apps/mamias/apps/resources/views/mamias/home.blade.php`

```blade
@extends('app')

@section('title', 'Home')


@section('content')
    {{-- Carousel --}}
    <div class="mamias-carousel-wrap pt-0 pb-4">

        <div class="mamias-carousel overflow-hidden" id="mamiasCarousel">
            <div class="mamias-carousel-track" id="mamiasTrack">
                <div class="mamias-slide" style="background: linear-gradient(135deg, #003d61 0%, #005f98 100%);">
                    <div class="mamias-slide-inner">
                        <i class="ki-filled ki-picture text-5xl opacity-30 text-white mb-3"></i>
                        <span class="text-white/60 text-sm font-medium tracking-widest uppercase">Slide 1 — Mediterranean</span>
                    </div>
                </div>
                <div class="mamias-slide" style="background: linear-gradient(135deg, #005f98 0%, #018d9a 100%);">
                    <div class="mamias-slide-inner">
                        <i class="ki-filled ki-picture text-5xl opacity-30 text-white mb-3"></i>
                        <span class="text-white/60 text-sm font-medium tracking-widest uppercase">Slide 2 — Marine Biodiversity</span>
                    </div>
                </div>
                <div class="mamias-slide" style="background: linear-gradient(135deg, #018d9a 0%, #4cafbf 100%);">
                    <div class="mamias-slide-inner">
                        <i class="ki-filled ki-picture text-5xl opacity-30 text-white mb-3"></i>
                        <span class="text-white/60 text-sm font-medium tracking-widest uppercase">Slide 3 — Invasive Species</span>
                    </div>
                </div>
                <div class="mamias-slide" style="background: linear-gradient(135deg, #4cafbf 0%, #003d61 100%);">
                    <div class="mamias-slide-inner">
                        <i class="ki-filled ki-picture text-5xl opacity-30 text-white mb-3"></i>
                        <span class="text-white/60 text-sm font-medium tracking-widest uppercase">Slide 4 — SPA/RAC</span>
                    </div>
                </div>
            </div>

            <button class="mamias-carousel-btn mamias-carousel-prev" id="mamiasCarouselPrev" aria-label="Previous">
                <i class="ki-filled ki-left"></i>
            </button>
            <button class="mamias-carousel-btn mamias-carousel-next" id="mamiasCarouselNext" aria-label="Next">
                <i class="ki-filled ki-right"></i>
            </button>

            <div class="mamias-carousel-dots" id="mamiasCarouselDots">
                <button class="mamias-dot active" data-slide="0" aria-label="Slide 1"></button>
                <button class="mamias-dot" data-slide="1" aria-label="Slide 2"></button>
                <button class="mamias-dot" data-slide="2" aria-label="Slide 3"></button>
                <button class="mamias-dot" data-slide="3" aria-label="Slide 4"></button>
            </div>
        </div>
        </div>
    </div>

    {{-- Key Features --}}
    <section id="features" class="py-20 bg-white">
        <div class="kt-container-fixed">
            {{-- Section header --}}
            <div class="flex flex-col items-center text-center gap-3 mb-14">
                <a href="#features" class="text-sm font-medium text-primary hover:text-primary/80 border-b border-primary pb-0.5">Key Features</a>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900">MAMIAS Key Features</h2>
                <p class="text-base text-gray-500 max-w-2xl">
                    Our platform provides all the tools you need to monitor, report, and analyse Non-Indigenous Species data across the Mediterranean.
                </p>
            </div>

            {{-- Cards grid --}}
            <div class="features-grid">

                {{-- Card 1 — Lightning Workflows --}}
                <div class="group relative overflow-hidden rounded-none border border-gray-200 bg-white p-8 transition-all duration-300 hover:shadow-lg hover:border-[#4cafbf]">
                    <div class="flex items-start justify-between mb-6">
                        <div class="size-12 rounded-full flex items-center justify-center bg-blue-50">
                            <i class="ki-filled ki-flash text-xl text-blue-500"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-gray-900">10x faster</div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-gray-400">Speed Increase</div>
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Lightning Workflows</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Supercharge your daily operations with automation that not only saves time, but intelligently adapts to your evolving business routines.</p>
                    <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-[#018d9a] via-[#4cafbf] to-[#005f98] scale-x-0 group-hover:scale-x-100 transition-transform duration-500 origin-left"></div>
                </div>

                {{-- Card 2 — Adaptive Safeguards --}}
                <div class="group relative overflow-hidden rounded-none border border-gray-200 bg-white p-8 transition-all duration-300 hover:shadow-lg hover:border-[#4cafbf]">
                    <div class="flex items-start justify-between mb-6">
                        <div class="size-12 rounded-full flex items-center justify-center bg-red-50">
                            <i class="ki-filled ki-shield-tick text-xl text-red-400"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-gray-900">99.9%</div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-gray-400">Uptime</div>
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Adaptive Safeguards</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Protect your data and streamline processes with real-time AI security, adapting instantly to threats and keeping your operations resilient and confidential.</p>
                    <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-[#018d9a] via-[#4cafbf] to-[#005f98] scale-x-0 group-hover:scale-x-100 transition-transform duration-500 origin-left"></div>
                </div>

                {{-- Card 3 — Smart Team Sync (featured/active) --}}
                <div class="group relative overflow-hidden rounded-none border border-gray-200 bg-white p-8 transition-all duration-300 hover:shadow-lg hover:border-[#4cafbf]">
                    <div class="flex items-start justify-between mb-6">
                        <div class="size-12 rounded-full flex items-center justify-center bg-[#4cafbf]/10">
                            <i class="ki-filled ki-people text-xl text-[#018d9a]"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-gray-900">10k+</div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-gray-400">Active Users</div>
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Smart Team Sync</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Let AI handle the chaos of calendars and meetings — Smart Team Sync coordinates, schedules, and adapts to your team's needs, so you can focus on what matters most.</p>
                    <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-[#018d9a] via-[#4cafbf] to-[#005f98] scale-x-0 group-hover:scale-x-100 transition-transform duration-500 origin-left"></div>
                </div>

                {{-- Card 4 — Predictive Insights --}}
                <div class="group relative overflow-hidden rounded-none border border-gray-200 bg-white p-8 transition-all duration-300 hover:shadow-lg hover:border-[#4cafbf]">
                    <div class="flex items-start justify-between mb-6">
                        <div class="size-12 rounded-full flex items-center justify-center bg-amber-50">
                            <i class="ki-filled ki-graph-up text-xl text-amber-500"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-gray-900">25%</div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-gray-400">Growth Boost</div>
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Predictive Insights</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Reveal hidden trends and forecast outcomes with analytics that learn from your unique data, giving you a competitive edge and actionable clarity.</p>
                    <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-[#018d9a] via-[#4cafbf] to-[#005f98] scale-x-0 group-hover:scale-x-100 transition-transform duration-500 origin-left"></div>
                </div>

            </div>
        </div>
    </section>

    {{-- CTA Section --}}
    <section class="py-20 bg-gray-50">
        <div class="kt-container-fixed">
            <div class="flex flex-col lg:flex-row items-center gap-12 lg:gap-16">
                {{-- Text content --}}
                <div class="flex-1 text-center lg:text-left">
                    <span class="inline-block text-sm font-medium text-[#018d9a] bg-[#018d9a]/10 rounded-full px-4 py-1.5 mb-4">Get Started Today</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Add observation of Marine Non-Indigenous Species</h2>
                    <p class="text-base text-gray-500 leading-relaxed mb-8 max-w-lg mx-auto lg:mx-0">
                        Join hundreds of researchers and institutions using MAMIAS to monitor, report, and analyse Non-Indigenous Species data — powered by science, built for collaboration.
                    </p>
                    <div class="flex flex-col sm:flex-row items-center gap-3 justify-center lg:justify-start">
                        @auth
                            @if(auth()->user()->hasAnyRole(['super_admin', 'scientist', 'admin']))
                                <a href="{{ route('filament.mamias.pages.dashboard') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-white font-semibold text-sm transition-all duration-300 hover:shadow-lg" style="background: linear-gradient(135deg, #018d9a, #005f98);">
                                    <i class="ki-filled ki-element-11 text-base"></i>
                                    Go to Admin Area
                                </a>
                            @else
                                <a href="{{ route('filament.mamias.pages.dashboard') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-white font-semibold text-sm transition-all duration-300 hover:shadow-lg" style="background: linear-gradient(135deg, #018d9a, #005f98);">
                                    <i class="ki-filled ki-element-11 text-base"></i>
                                    Make a species report
                                </a>
                            @endif
                        @else
                            <a href="{{ route('filament.mamias.auth.register') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-white font-semibold text-sm transition-all duration-300 hover:shadow-lg" style="background: linear-gradient(135deg, #018d9a, #005f98);">
                                <i class="ki-filled ki-user-plus text-base"></i>
                                Create Free Account
                            </a>
                            <a href="{{ route('filament.mamias.auth.login') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg border border-gray-300 text-gray-700 font-semibold text-sm transition-all duration-300 hover:border-[#4cafbf] hover:text-[#018d9a] bg-white">
                                <i class="ki-filled ki-entrance-left text-base"></i>
                                Sign In
                            </a>
                        @endauth
                    </div>
                </div>

                {{-- App screenshot --}}
                <div class="flex-1 w-full max-w-xl">
                    <div class="rounded-xl shadow-2xl border border-gray-200 overflow-hidden bg-white">
                        {{-- Browser chrome --}}
                        <div class="flex items-center gap-2 px-4 py-3 bg-gray-100 border-b border-gray-200">
                            <div class="flex gap-1.5">
                                <span class="block size-3 rounded-full bg-red-400"></span>
                                <span class="block size-3 rounded-full bg-yellow-400"></span>
                                <span class="block size-3 rounded-full bg-green-400"></span>
                            </div>
                            <div class="flex-1 mx-2">
                                <div class="bg-white rounded-md px-3 py-1 text-xs text-gray-400 border border-gray-200 text-center truncate">mamias.org/mamias</div>
                            </div>
                        </div>
                        {{-- Screenshot placeholder --}}
                        <div class="relative bg-gradient-to-br from-[#003d61] via-[#005f98] to-[#018d9a] aspect-video flex items-center justify-center">
                            <div class="text-center">
                                <img src="{{ asset('images/Logoweb.png') }}" alt="MAMIAS Platform" class="max-h-16 mx-auto mb-4 opacity-90">
                                <div class="flex items-center justify-center gap-6 mt-4">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-white">1 200+</div>
                                        <div class="text-xs text-white/60 uppercase tracking-wide">Species</div>
                                    </div>
                                    <div class="w-px h-8 bg-white/20"></div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-white">22</div>
                                        <div class="text-xs text-white/60 uppercase tracking-wide">Countries</div>
                                    </div>
                                    <div class="w-px h-8 bg-white/20"></div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-white">300+</div>
                                        <div class="text-xs text-white/60 uppercase tracking-wide">Researchers</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
@endsection

@push('styles')
<style>
.features-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

@media (min-width: 48rem) {
    .features-grid {
        grid-template-columns: 1fr 1fr;
    }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    const track = document.getElementById('mamiasTrack');
    const dots = document.querySelectorAll('#mamiasCarouselDots .mamias-dot');
    if (!track) return;
    let current = 0;
    const total = track.children.length;
    let timer;

    function goTo(index) {
        current = (index + total) % total;
        track.style.transform = `translateX(-${current * 100}%)`;
        dots.forEach((d, i) => d.classList.toggle('active', i === current));
    }

    function next() { goTo(current + 1); }
    function prev() { goTo(current - 1); }

    document.getElementById('mamiasCarouselNext')?.addEventListener('click', () => { clearInterval(timer); next(); resetTimer(); });
    document.getElementById('mamiasCarouselPrev')?.addEventListener('click', () => { clearInterval(timer); prev(); resetTimer(); });
    dots.forEach(d => d.addEventListener('click', () => { clearInterval(timer); goTo(+d.dataset.slide); resetTimer(); }));

    function resetTimer() { timer = setInterval(next, 5000); }
    resetTimer();
})();
</script>
@endpush
```

---

## 5. `/home/toufa/dev/2026/apps/mamias/apps/app/Livewire/MySpeciesReports.php`

```php
<?php

namespace App\Livewire;

use App\Enums\OccurrenceStatus;
use App\Filament\Resources\Occurrences\Schemas\OccurrenceForm;
use App\Filament\Resources\Occurrences\Schemas\OccurrenceInfolist;
use App\Filament\Resources\Occurrences\Tables\OccurrencesTable;
use App\Models\Occurrence;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\ViewAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class MySpeciesReports extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Occurrence::where('user_id', auth()->id())->with('introEventRecord.taxon'))
            ->columns([
                OccurrencesTable::getSpeciesColumn(),
                OccurrencesTable::getStatusColumn(),
                OccurrencesTable::getMapColumn(),
                OccurrencesTable::getDepthColumn(),
                TextColumn::make('observed_at')->label('Observed')->dateTime()->sortable(),
                TextColumn::make('created_at')->label('Submitted')->dateTime()->sortable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modalHeading('Occurrence Details')
                    ->modalWidth(Width::SevenExtraLarge)
                    ->schema(OccurrenceInfolist::getComponents()),
                $this->editAction(),
            ])
            ->headerActions([$this->createAction()])
            ->defaultSort('created_at', 'desc');
    }

    public function createAction(): Action
    {
        return Action::make('create')
            ->label('Report New Occurrence')
            ->icon('tabler-binoculars')
            ->button()
            ->color('primary')
            ->size('lg')
            ->modalHeading('Report a New Species Occurrence')
            ->modalWidth(Width::SevenExtraLarge)
            ->schema(OccurrenceForm::getComponents())
            ->action(function (array $data): void {
                Occurrence::create([
                    ...$data,
                    'user_id' => auth()->id(),
                    'status' => OccurrenceStatus::PENDING,
                ]);

                Notification::make()
                    ->title('Occurrence reported')
                    ->body('Thank you! Your occurrence report will be reviewed by our team.')
                    ->success()
                    ->send();
            });
    }

    public function editAction(): Action
    {
        return Action::make('edit')
            ->label('Edit')
            ->icon('tabler-pencil')
            ->color('gray')
            ->visible(fn (Occurrence $record): bool => $record->status === OccurrenceStatus::PENDING)
            ->modalHeading('Edit Occurrence')
            ->modalWidth(Width::SevenExtraLarge)
            ->schema(OccurrenceForm::getComponents())
            ->fillForm(fn (Occurrence $record): array => $record->toArray())
            ->action(function (Occurrence $record, array $data): void {
                $record->update($data);

                Notification::make()
                    ->title('Occurrence updated')
                    ->success()
                    ->send();
            });
    }

    public function getStats(): array
    {
        $row = Occurrence::where('user_id', auth()->id())
            ->selectRaw('count(*) as total, count(*) filter (where status = ?) as pending, count(*) filter (where status = ?) as approved, count(*) filter (where status = ?) as rejected', [
                OccurrenceStatus::PENDING->value,
                OccurrenceStatus::APPROVED->value,
                OccurrenceStatus::REJECTED->value,
            ])
            ->first();

        return [
            'total' => $row->total,
            'pending' => $row->pending,
            'approved' => $row->approved,
            'rejected' => $row->rejected,
        ];
    }

    public function render(): View
    {
        return view('livewire.my-species-reports', [
            'stats' => $this->getStats(),
        ])->extends('app')->section('content');
    }
}
```

---

## 6. Tailwind Configuration

**No `tailwind.config.*` file exists.** This is a **Tailwind CSS v4** setup. Configuration is done via CSS.

**Entry point:** `/home/toufa/dev/2026/apps/mamias/apps/resources/css/app.css`

```css
@import 'tailwindcss';

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';
@source '../**/*.blade.php';
@source '../**/*.js';

@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji',
    'Segoe UI Symbol', 'Noto Color Emoji';
}
```

---

## 7. `/home/toufa/dev/2026/apps/mamias/apps/resources/css/filament/mamias/theme.css`

```css
@import '../../../../vendor/filament/filament/resources/css/theme.css';

@source '../../../../app/Filament/**/*';
@source '../../../../resources/views/filament/**/*';
@source '../../../../vendor/devonab/filament-easy-footer/resources/views/**/*';
@source '../../../../vendor/diogogpinto/filament-auth-ui-enhancer/resources/**/*.blade.php';
@source '../../../../vendor/defstudio/filament-searchable-input/resources/**/*.blade.php';
@source '../../../../vendor/agencetwogether/filament-alert-box/resources/views/**/*';
@source '../../../../vendor/wire-elements/**/*.blade.php';

/* Fix Leaflet 1.9 gray tile issue — mix-blend-mode makes tiles invisible on many browsers */
.leaflet-container img.leaflet-tile {
    mix-blend-mode: normal !important;
}

.dark {
    --info-500: #3b82f6;
    --primary-500: #00a8bd; /* Teal plus lumineux en dark mode */
    --primary-600: #00899d;
}

:root {
    --mamias-border-dark: theme(colors.slate.700);
    --mamias-border-light: theme(colors.slate.300);
    --mamias-primary-300: #5eeaff;
    --mamias-primary-700: #006b7a;
    --mamias-primary-900: #004d59;
    --mamias-primary-950: #00353d;
}


/* ── No rounded corners ───────────────────────────────────────── */
.fi-card,
.fi-section,
.fi-section-content,
.fi-section-header,
.fi-badge,
.fi-btn,
.fi-btn-color-primary,
.fi-btn-color-gray,
.fi-input,
.fi-input-wrp,
.fi-select-input,
.fi-textarea,
.fi-dropdown-panel,
.fi-modal-window,
.fi-ta-table,
.fi-ta-row,
.fi-ta-cell,
.fi-pagination-item,
.fi-tabs,
.fi-tabs-item,
.fi-fo-field-wrp,
.fi-wi-stats-overview-stat {
    border-radius: 0 !important;
}

/* ── No borders ───────────────────────────────────────── */
.fi-card,
.fi-section,
.fi-section-content,
.fi-section-header,
.fi-badge,
.fi-btn,
.fi-btn-color-primary,
.fi-btn-color-gray,
.fi-input,
.fi-input-wrp,
.fi-select-input,
.fi-textarea,
.fi-dropdown-panel,
.fi-modal-window,
.fi-ta-table,
.fi-ta-row,
.fi-ta-cell,
.fi-pagination-item,
.fi-tabs,
.fi-tabs-item,
.fi-fo-field-wrp,
.fi-wi-stats-overview-stat {
    border: none !important;
}

.fi-ta-ctn {
    @apply bg-white dark:bg-gray-800 border border-primary-700 dark:border-gray-700 rounded-none;
}

.fi-ta-ctn .py-4 {
    padding-bottom: 0.75rem !important;
    padding-top: 0.75rem !important;
}

.fi-avatar:not(.fi-circular) {
    @apply rounded-none;
}


.fi-ta-table {
    border-collapse: collapse;
}

.fi-ta-cell {
    border-bottom: none !important;
}

.fi-ta-table > tbody {
    @apply divide-none
}


.fi-ta-table > tbody {
    @apply divide-none
}

.fi-ta-text {
    @apply py-0.5
}

.fi-ta-table > .whitespace-nowrap {
    @apply py-0.5
}

.fi-ta-cell > div:has(.flex) {
    @apply py-0.5
}

/* ── Stepper ───────────────────────────────────────────── */
.it-stepper-btn-decrement,
.it-stepper-btn-increment {
    @apply text-white;
    background-color: var(--primary-500) !important;
}

.it-stepper-btn-decrement:hover,
.it-stepper-btn-increment:hover {
    background-color: var(--primary-600) !important;
}

.it-stepper-btn-decrement:disabled,
.it-stepper-btn-increment:disabled {
    background-color: var(--primary-300, #5eeaff) !important;
    opacity: 0.6;
}
```

---

## 8. `/home/toufa/dev/2026/apps/mamias/apps/vite.config.js`

```js
import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/mamias/theme.css'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
```

---

**Summary:** All 7 existing files have been returned verbatim above. Item #3 (`resources/views/components/layouts/app.blade.php`) does not exist. Item #6 (Tailwind config) uses Tailwind v4's CSS-based configuration with `@import 'tailwindcss'` in `resources/css/app.css` -- there is no `tailwind.config.*` file.
</task_result>
</task>
```

---

## Assistant (Plan · DeepSeek V4 Flash Free · 103.0s)

_Thinking:_

Now I have extremely detailed knowledge. Let me construct the comprehensive deep-dive across all layers. I need to organize this into a well-structured document that covers:

1. Alert Engine SQL & Spatial Queries (the most critical part)
2. Filament UI Details
3. GuardIAS/EASIN/GBIF Integration
4. Notification & Channel Delivery
5. Observer/job wiring details

Let me write this up now, making sure every code example follows the existing patterns exactly.

Now I have every detail needed. Here is the full deep-dive across all technical layers.

---

## 1. ALERT ENGINE — SQL & Spatial Detection Logic

### 1.1 The `EwsEvaluator` — Full Implementation

This is the core. It evaluates each approved `Occurrence` against four detection criteria. All queries use PostGIS on the existing `location_point` geometry column.

```php
// app/Services/Ews/EwsEvaluator.php

namespace App\Services\Ews;

use App\Enums\AlertEventType;
use App\Enums\Subregion;
use App\Models\NisSuggestion;
use App\Models\Occurrence;
use App\Models\SubregionRecord;
use App\Models\Taxon;
use Illuminate\Support\Facades\DB;

class EwsEvaluator
{
    // Subregion bounding boxes (simplified WGS84 polygons)
    private const SUBREGION_BOUNDS = [
        'WMED' => ['south' => 35.0, 'west' => -6.0, 'north' => 44.0, 'east' => 8.0],
        'CMED' => ['south' => 35.0, 'west' => 8.0, 'north' => 42.0, 'east' => 20.0],
        'ADRIA' => ['south' => 39.0, 'west' => 12.0, 'north' => 46.0, 'east' => 20.0],
        'EMED' => ['south' => 31.0, 'west' => 22.0, 'north' => 42.0, 'east' => 36.0],
    ];

    /**
     * @return array<int, array> Array of alert context arrays
     */
    public function evaluate(Occurrence $occurrence): array
    {
        $alerts = [];

        $taxon = $occurrence->introEventRecord?->taxon;
        if (! $taxon) {
            return $alerts;
        }

        $coords = $occurrence->location;
        $first = is_array($coords) ? ($coords[0] ?? null) : $coords;
        if (! $first || ! isset($first['lat'], $first['lng'])) {
            return $alerts;
        }

        $lat = (float) $first['lat'];
        $lng = (float) $first['lng'];
        $subregion = $this->resolveSubregion($lat, $lng);

        // Detection 1: New Arrival
        if ($alert = $this->detectNewArrival($taxon, $subregion, $occurrence, $lat, $lng)) {
            $alerts[] = $alert;
        }

        // Detection 2: Range Expansion
        if ($alert = $this->detectRangeExpansion($taxon, $occurrence, $lat, $lng)) {
            $alerts[] = $alert;
        }

        // Detection 3: High Impact Species
        if ($alert = $this->detectHighImpact($taxon, $occurrence, $subregion)) {
            $alerts[] = $alert;
        }

        // Detection 4: Rapid Spread
        if ($alert = $this->detectRapidSpread($taxon, $occurrence, $subregion)) {
            $alerts[] = $alert;
        }

        return $alerts;
    }

    private function detectNewArrival(
        Taxon $taxon,
        ?string $subregion,
        Occurrence $occurrence,
        float $lat,
        float $lng
    ): ?array {
        if (! $subregion) {
            return null;
        }

        // Query: is this species already recorded in this subregion?
        $existing = SubregionRecord::whereHas('introEvent', function ($q) use ($taxon) {
                $q->where('taxon_id', $taxon->id);
            })
            ->where('subregion', $subregion)
            ->exists();

        if ($existing) {
            return null; // Already known in this subregion
        }

        // Also check occurrences directly (in case no SubregionRecord exists)
        $existingOccurrence = Occurrence::whereHas('introEventRecord', function ($q) use ($taxon) {
                $q->where('taxon_id', $taxon->id);
            })
            ->where('id', '!=', $occurrence->id)
            ->where('status', 'approved')
            ->withinBoundingBox(
                self::SUBREGION_BOUNDS[$subregion]['south'],
                self::SUBREGION_BOUNDS[$subregion]['west'],
                self::SUBREGION_BOUNDS[$subregion]['north'],
                self::SUBREGION_BOUNDS[$subregion]['east'],
            )
            ->exists();

        if ($existingOccurrence) {
            return null;
        }

        return [
            'type' => AlertEventType::NEW_ARRIVAL,
            'context' => [
                'taxon_id' => $taxon->id,
                'scientific_name' => $taxon->scientificname,
                'source_type' => 'occurrence',
                'source_id' => $occurrence->id,
                'subregion' => $subregion,
                'metadata' => [
                    'lat' => $lat,
                    'lng' => $lng,
                    'occurrence_url' => url("/mamias/intro-event-records/{$occurrence->intro_event_record_id}/edit"),
                ],
            ],
        ];
    }

    private function detectRangeExpansion(
        Taxon $taxon,
        Occurrence $occurrence,
        float $lat,
        float $lng
    ): ?array {
        // Find nearest existing approved occurrence of this species
        $nearest = Occurrence::whereHas('introEventRecord', function ($q) use ($taxon) {
                $q->where('taxon_id', $taxon->id);
            })
            ->where('id', '!=', $occurrence->id)
            ->where('status', 'approved')
            ->orderByDistance($lat, $lng)                          // PostGIS <-> operator
            ->withDistanceFrom($lat, $lng)                         // ST_Distance in meters
            ->first();

        if (! $nearest || ! isset($nearest->distance_meters)) {
            return null;
        }

        // Threshold: >50km from nearest known occurrence
        $thresholdKm = 50;

        if ($nearest->distance_meters < ($thresholdKm * 1000)) {
            return null;
        }

        return [
            'type' => AlertEventType::RANGE_EXPANSION,
            'context' => [
                'taxon_id' => $taxon->id,
                'scientific_name' => $taxon->scientificname,
                'source_type' => 'occurrence',
                'source_id' => $occurrence->id,
                'subregion' => $this->resolveSubregion($lat, $lng),
                'metadata' => [
                    'lat' => $lat,
                    'lng' => $lng,
                    'distance_from_nearest_km' => round($nearest->distance_meters / 1000, 1),
                    'nearest_occurrence_id' => $nearest->id,
                ],
            ],
        ];
    }

    private function detectHighImpact(
        Taxon $taxon,
        Occurrence $occurrence,
        ?string $subregion
    ): ?array {
        // Species with Invasive establishment status are high impact
        $hasHighImpact = $taxon->introEvents()
            ->where('establishment_status', 'Invasive')
            ->exists();

        if (! $hasHighImpact) {
            return null;
        }

        // Only alert if this is a NEW subregion for this high-impact species
        if ($subregion) {
            $alreadyAlerted = Alert::where('taxon_id', $taxon->id)
                ->where('event_type', AlertEventType::HIGH_IMPACT)
                ->where('subregion', $subregion)
                ->exists();

            if ($alreadyAlerted) {
                return null;
            }
        }

        return [
            'type' => AlertEventType::HIGH_IMPACT,
            'context' => [
                'taxon_id' => $taxon->id,
                'scientific_name' => $taxon->scientificname,
                'source_type' => 'occurrence',
                'source_id' => $occurrence->id,
                'subregion' => $subregion,
                'metadata' => [
                    'establishment_status' => 'Invasive',
                ],
            ],
        ];
    }

    private function detectRapidSpread(
        Taxon $taxon,
        Occurrence $occurrence,
        ?string $subregion
    ): ?array {
        // Count occurrences in last 30 days for this species
        $recentCount = Occurrence::whereHas('introEventRecord', function ($q) use ($taxon) {
                $q->where('taxon_id', $taxon->id);
            })
            ->where('status', 'approved')
            ->where('observed_at', '>=', now()->subDays(30))
            ->count();

        if ($recentCount < 3) {
            return null; // Threshold: 3+ occurrences in 30 days
        }

        $alreadyAlerted = Alert::where('taxon_id', $taxon->id)
            ->where('event_type', AlertEventType::RAPID_SPREAD)
            ->where('created_at', '>=', now()->subDays(7))
            ->exists();

        if ($alreadyAlerted) {
            return null;
        }

        return [
            'type' => AlertEventType::RAPID_SPREAD,
            'context' => [
                'taxon_id' => $taxon->id,
                'scientific_name' => $taxon->scientificname,
                'source_type' => 'occurrence',
                'source_id' => $occurrence->id,
                'subregion' => $subregion,
                'metadata' => [
                    'occurrences_in_30_days' => $recentCount,
                ],
            ],
        ];
    }

    private function resolveSubregion(float $lat, float $lng): ?string
    {
        foreach (self::SUBREGION_BOUNDS as $name => $bounds) {
            if ($lat >= $bounds['south'] && $lat <= $bounds['north']
                && $lng >= $bounds['west'] && $lng <= $bounds['east']) {
                return $name;
            }
        }

        return null;
    }
}
```

### 1.2 The `AlertGenerator` — Deduplication & Creation

```php
// app/Services/Ews/AlertGenerator.php

namespace App\Services\Ews;

use App\Enums\AlertEventType;
use App\Models\Alert;

class AlertGenerator
{
    private const DEDUP_WINDOW_DAYS = [
        AlertEventType::NEW_ARRIVAL => 30,       // 30 days for new arrivals
        AlertEventType::RANGE_EXPANSION => 14,    // 2 weeks for range expansion
        AlertEventType::HIGH_IMPACT => 60,        // 60 days for high impact
        AlertEventType::RAPID_SPREAD => 7,        // 1 week for rapid spread
    ];

    public function generate(AlertEventType $type, array $context): ?Alert
    {
        $dedupDays = self::DEDUP_WINDOW_DAYS[$type->value] ?? 7;

        // Deduplication: same species + subregion + event_type within window
        $existing = Alert::where('taxon_id', $context['taxon_id'])
            ->where('subregion', $context['subregion'])
            ->where('event_type', $type)
            ->where('created_at', '>=', now()->subDays($dedupDays))
            ->exists();

        if ($existing) {
            return null;
        }

        // Deduplication for non-subregion alerts
        if (! $context['subregion']) {
            $existing = Alert::where('taxon_id', $context['taxon_id'])
                ->where('event_type', $type)
                ->whereNull('subregion')
                ->where('created_at', '>=', now()->subDays(7))
                ->exists();

            if ($existing) {
                return null;
            }
        }

        return Alert::create([
            'taxon_id' => $context['taxon_id'],
            'scientific_name' => $context['scientific_name'],
            'event_type' => $type,
            'severity' => $this->calculateSeverity($type, $context),
            'title' => $this->buildTitle($type, $context),
            'description' => $this->buildDescription($type, $context),
            'source_type' => $context['source_type'],
            'source_id' => $context['source_id'],
            'subregion' => $context['subregion'],
            'metadata' => $context['metadata'] ?? [],
        ]);
    }

    private function calculateSeverity(AlertEventType $type, array $context): string
    {
        return match ($type) {
            AlertEventType::NEW_ARRIVAL => 'critical',
            AlertEventType::HIGH_IMPACT => 'critical',
            AlertEventType::RANGE_EXPANSION => 'warning',
            AlertEventType::RAPID_SPREAD => 'warning',
        };
    }

    private function buildTitle(AlertEventType $type, array $context): string
    {
        $name = $context['scientific_name'] ?? 'Unknown species';

        return match ($type) {
            AlertEventType::NEW_ARRIVAL => "New arrival: {$name} detected in {$context['subregion']}",
            AlertEventType::RANGE_EXPANSION => "Range expansion: {$name} found {$context['metadata']['distance_from_nearest_km']}km from known range",
            AlertEventType::HIGH_IMPACT => "High-impact species alert: {$name} detected",
            AlertEventType::RAPID_SPREAD => "Rapid spread detected: {$name} — {$context['metadata']['occurrences_in_30_days']} reports in 30 days",
        };
    }

    private function buildDescription(AlertEventType $type, array $context): string
    {
        return match ($type) {
            AlertEventType::NEW_ARRIVAL => sprintf(
                "**%s** has been reported for the first time in **%s**. This species was not previously recorded in this subregion. Immediate monitoring and assessment are recommended.\n\n[View occurrence](%s)",
                $context['scientific_name'],
                $context['subregion'],
                $context['metadata']['occurrence_url'] ?? '#'
            ),
            AlertEventType::RANGE_EXPANSION => sprintf(
                "A specimen of **%s** was observed **%s km** from the nearest known occurrence. This significant range expansion suggests the species is actively spreading.\n\n- Distance from nearest occurrence: **%s km**",
                $context['scientific_name'],
                $context['metadata']['distance_from_nearest_km'],
                $context['metadata']['distance_from_nearest_km'],
            ),
            AlertEventType::HIGH_IMPACT => sprintf(
                "**%s** — classified as **Invasive** — has been detected. This species is known to cause significant ecological or economic impact. Prioritize monitoring and control measures.",
                $context['scientific_name'],
            ),
            AlertEventType::RAPID_SPREAD => sprintf(
                "**%s** has been reported **%s times** in the last 30 days, indicating a rapid spread event. This may signal an ongoing invasion front.",
                $context['scientific_name'],
                $context['metadata']['occurrences_in_30_days'],
            ),
        };
    }
}
```

### 1.3 Key SQL Queries Reference

All detection uses existing PostGIS scopes from `HasSpatialLocation`:

| Query | SQL Produced | Used For |
|---|---|---|
| `->near($lat, $lng, $meters)` | `ST_DWithin(location_point::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)` | General proximity search |
| `->orderByDistance($lat, $lng)` | `location_point <-> ST_SetSRID(ST_MakePoint(?, ?), 4326)` | Nearest neighbor (uses GiST index) |
| `->withDistanceFrom($lat, $lng)` | `ST_Distance(location_point::geography, ...) as distance_meters` | Exact distance in meters |
| `->withinBoundingBox(...)` | `location_point && ST_MakeEnvelope(...)` AND `ST_Intersects(...)` | Subregion containment check |

No migration changes needed for spatial queries — the `occurrences` table already has a GiST index on `location_point`.

---

## 2. FILAMENT UI — Complete Component Specifications

### 2.1 Alert Enum — `app/Enums/AlertEventType.php`

Follows the exact `EstablishmentStatus` pattern:
```php
enum AlertEventType: string implements HasColor, HasIcon, HasLabel
{
    case NEW_ARRIVAL = 'new_arrival';
    case RANGE_EXPANSION = 'range_expansion';
    case HIGH_IMPACT = 'high_impact';
    case RAPID_SPREAD = 'rapid_spread';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NEW_ARRIVAL => 'New Arrival',
            self::RANGE_EXPANSION => 'Range Expansion',
            self::HIGH_IMPACT => 'High Impact Species',
            self::RAPID_SPREAD => 'Rapid Spread',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NEW_ARRIVAL => 'danger',
            self::RANGE_EXPANSION => 'warning',
            self::HIGH_IMPACT => 'danger',
            self::RAPID_SPREAD => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::NEW_ARRIVAL => 'tabler-alert-triangle',
            self::RANGE_EXPANSION => 'tabler-arrows-maximize',
            self::HIGH_IMPACT => 'tabler-flame',
            self::RAPID_SPREAD => 'tabler-trending-up',
        };
    }
}
```

**`AlertSeverity` enum:**
```php
enum AlertSeverity: string implements HasColor, HasIcon, HasLabel
{
    case INFO = 'info';
    case WARNING = 'warning';
    case CRITICAL = 'critical';

    public function getLabel(): ?string { return ucfirst($this->value); }
    public function getColor(): string|array|null {
        return match ($this) {
            self::INFO => 'info',
            self::WARNING => 'warning',
            self::CRITICAL => 'danger',
        };
    }
    public function getIcon(): ?string {
        return match ($this) {
            self::INFO => 'tabler-info-circle',
            self::WARNING => 'tabler-alert-triangle',
            self::CRITICAL => 'tabler-bell-ringing',
        };
    }
}
```

### 2.2 Alert Model — `app/Models/Alert.php`

```php
#[Fillable([
    'taxon_id', 'scientific_name', 'event_type', 'severity',
    'title', 'description', 'source_type', 'source_id',
    'subregion', 'published_at', 'expires_at', 'metadata',
    'created_by', 'updated_by',
])]
class Alert extends Model
{
    use HasFactory, Userstamps;

    protected function casts(): array
    {
        return [
            'event_type' => AlertEventType::class,
            'severity' => AlertSeverity::class,
            'metadata' => 'array',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Alert $alert): void {
            $alert->published_at ??= now();
            $alert->expires_at ??= match ($alert->severity) {
                AlertSeverity::CRITICAL => now()->addDays(30),
                default => now()->addDays(90),
            };
        });
    }

    public function taxon(): BelongsTo
    {
        return $this->belongsTo(Taxon::class);
    }

    // Polymorphic source
    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
```

### 2.3 AlertResource Structure

```
app/Filament/Resources/Ews/
├── AlertResource.php
├── Pages/
│   ├── ListAlerts.php
│   └── ViewAlert.php
├── Schemas/
│   └── AlertInfolist.php
├── Tables/
│   └── AlertsTable.php
└── Actions/
    └── AlertActions.php
```

### 2.4 AlertResource

```php
class AlertResource extends Resource
{
    protected static ?string $model = Alert::class;
    protected static string|BackedEnum|null $navigationIcon = 'tabler-bell-ringing';
    protected static ?string $modelLabel = 'Early Warning Alert';
    protected static ?string $pluralModelLabel = 'Early Warning Alerts';
    protected static ?int $navigationSort = 5;
    protected static string|null|\UnitEnum $navigationGroup = 'MAMIAS database';

    public static function table(Table $table): Table
    {
        return AlertsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AlertInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAlerts::route('/'),
            'view' => ViewAlert::route('/{record}'),
        ];
    }
}
```

### 2.5 AlertsTable — Column Definitions

```php
class AlertsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('taxon'))
            ->defaultSort('published_at', 'desc')
            ->striped()
            ->columns([
                TextColumn::make('ID')->rowIndex()->label('#'),
                TextColumn::make('scientific_name')
                    ->label('Species')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => "<i>{$state}</i>")
                    ->html(),
                TextColumn::make('event_type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('severity')
                    ->label('Severity')
                    ->badge()
                    ->sortable(),
                TextColumn::make('subregion')
                    ->label('Subregion')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('severity')
                    ->options(AlertSeverity::class),
                SelectFilter::make('event_type')
                    ->options(AlertEventType::class),
                SelectFilter::make('subregion')
                    ->options(Subregion::class),
                Filter::make('published_at')
                    ->form([
                        DatePicker::make('published_from'),
                        DatePicker::make('published_until'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['published_from'], fn ($q, $d) => $q->whereDate('published_at', '>=', $d))
                        ->when($data['published_until'], fn ($q, $d) => $q->whereDate('published_at', '<=', $d))
                    ),
            ])
            ->recordActions([
                ViewAction::make()->modalWidth(Width::SevenExtraLarge),
            ]);
    }
}
```

### 2.6 AlertInfolist

```php
class AlertInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'md' => 2])
            ->components([
                Section::make('Alert Details')
                    ->icon('tabler-bell-ringing')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('scientific_name')
                                ->label('Species')
                                ->html()
                                ->columnSpan(2),
                            TextEntry::make('event_type')
                                ->badge()
                                ->columnSpan(1),
                            TextEntry::make('severity')
                                ->badge()
                                ->columnSpan(1),
                            TextEntry::make('title')
                                ->label('Title')
                                ->size(TextSize::Large)
                                ->weight(FontWeight::Bold)
                                ->columnSpanFull(),
                            TextEntry::make('description')
                                ->label('Description')
                                ->markdown()
                                ->columnSpanFull(),
                        ]),
                    ]),
                Section::make('Source')
                    ->icon('tabler-source-code')
                    ->schema([
                        TextEntry::make('source_type')->label('Source Type'),
                        TextEntry::make('subregion')->label('Subregion')->badge(),
                    ]),
                Section::make('Timeline')
                    ->icon('tabler-clock')
                    ->schema([
                        TextEntry::make('published_at')->label('Published')->dateTime(),
                        TextEntry::make('expires_at')->label('Expires')->dateTime(),
                    ]),
                // Map entry for spatial context
                Section::make('Location')
                    ->icon('tabler-map-pin')
                    ->schema([
                        MapEntry::make('metadata')
                            ->label('Alert Location')
                            ->hiddenLabel(),
                    ])
                    ->visible(fn (Alert $record): bool => isset($record->metadata['lat'])),
            ]);
    }
}
```

### 2.7 Dashboard Widget — `ActiveAlertsWidget`

Follows `PendingOccurrencesWidget` pattern:
```php
class ActiveAlertsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $critical = Alert::where('severity', AlertSeverity::CRITICAL)
            ->where('published_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count();

        $warning = Alert::where('severity', AlertSeverity::WARNING)
            ->where('published_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count();

        $total = $critical + $warning;

        return [
            Stat::make('Active Alerts', $total)
                ->description("{$critical} critical, {$warning} warning")
                ->descriptionIcon('tabler-bell-ringing')
                ->color($critical > 0 ? 'danger' : 'warning')
                ->url(AlertResource::getUrl('index')),
            Stat::make('Critical', $critical)
                ->description('Immediate attention required')
                ->descriptionIcon('tabler-alert-triangle')
                ->color('danger')
                ->url(AlertResource::getUrl('index', ['tableFilters[severity][value]' => 'critical'])),
        ];
    }
}
```

Registered in `Dashboard.php` by adding a new Tab:
```php
Tab::make('Early Warning')
    ->icon('tabler-bell-ringing')
    ->schema([
        Grid::make(4)->schema(
            $this->getWidgetsSchemaComponents([ActiveAlertsWidget::class])
        ),
        Livewire::make(ActiveAlertsMapWidget::class), // Optional map widget
    ]),
```

### 2.8 Public-Facing Livewire — `PublicAlerts`

Follows the `MySuggestions` pattern exactly. Shares the same `AssetServiceProvider` layout.

```php
class PublicAlerts extends Component implements HasTable
{
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Alert::where('published_at', '<=', now())
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
            )
            ->columns([
                TextColumn::make('scientific_name')
                    ->label('Species')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => "<i>{$state}</i>")
                    ->html(),
                TextColumn::make('event_type')->badge(),
                TextColumn::make('severity')->badge(),
                TextColumn::make('subregion')->badge(),
                TextColumn::make('published_at')->dateTime()->label('Date'),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                SelectFilter::make('severity')->options(AlertSeverity::class),
                SelectFilter::make('subregion')->options(Subregion::class),
            ]);
    }

    public function render(): View
    {
        return view('livewire.public-alerts')
            ->extends('app')
            ->section('content');
    }
}
```

Route in `web.php`:
```php
Route::get('/early-warning', PublicAlerts::class)->name('early-warning');
```

---

## 3. GUARDIAS / EASIN / GBIF INTEGRATION

### 3.1 GBIF Alert API Consumption

The GBIF Alert project exposes a **stable API v2** with:
- `GET /api/v2/observations/?species={name}&area={wkt}` — get recent observations
- `GET /api/v2/alerts/` — get configured alerts
- OGC WFS service at `/api/wfs/observations/`

MAMIAS already has all species (`Taxon`) mapped to GBIF (via WoRMS). New service:

```php
class GbifAlertService
{
    private string $baseUrl = 'https://alert.riparias.be/api/v2';

    public function fetchNewMedOccurrences(): Collection
    {
        // Use occurrence cubes API or direct observation query
        $response = Http::timeout(30)->get("{$this->baseUrl}/observations/", [
            'area' => 'POLYGON((-6 35, 36 35, 36 46, -6 46, -6 35))', // Mediterranean bounding box
            'limit' => 500,
            'created_after' => now()->subWeek()->toIso8601String(),
        ]);

        if (! $response->successful()) {
            return collect();
        }

        return collect($response->json()['results'] ?? [])
            ->filter(fn ($obs) => Taxon::where('scientificname', $obs['species'])->exists());
    }
}
```

### 3.2 GBIF Occurrence Cubes (TrIAS/GuardIAS workflow)

GuardIAS publishes weekly indicator outputs at `guardias-eu.github.io/indicators/`. These use the **species occurrence cube** model:
- Data aggregated as `#occurrences × grid_cell × year × species`
- Each species gets an **emerging score** per year
- Marine species scored by Large Marine Ecosystem (LME)

MAMIAS can consume this JSON output:
```php
class GuardiasIndicatorService
{
    public function fetchEmergingSpecies(): array
    {
        $response = Http::timeout(30)
            ->get('https://guardias-eu.github.io/indicators/data/emerging_species.json');

        if (! $response->successful()) {
            return [];
        }

        return collect($response->json())
            ->filter(fn ($s) => $s['lme'] === 'Mediterranean Sea')
            ->sortByDesc('emerging_score')
            ->values()
            ->all();
    }
}
```

### 3.3 EASIN Webhook Receiver — Inbound

GuardIAS and EASIN are designing a joint webhook protocol. MAMIAS listens on:

```php
// routes/api.php
Route::post('/api/ew/easin-alert', [EasinWebhookController::class, 'receive'])
    ->name('api.ew.easin-alert');
```

Payload format (draft based on EASIN API patterns):
```json
{
    "alert_id": "EASIN-2026-0042",
    "scientific_name": "Rugulopteryx okamurae",
    "easin_id": "1164",
    "event_type": "new_arrival",
    "severity": "critical",
    "country": "Greece",
    "location": {"lat": 36.5, "lng": 25.5},
    "source": "easin",
    "description": "First record of R. okamurae in the Aegean Sea",
    "reference_url": "https://easin.jrc.ec.europa.eu/alerts/42"
}
```

```php
class EasinWebhookController extends Controller
{
    public function receive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'alert_id' => 'required|string',
            'scientific_name' => 'required|string',
            'event_type' => 'required|in:new_arrival,range_expansion,high_impact',
            'severity' => 'required|in:info,warning,critical',
            'location.lat' => 'required|numeric',
            'location.lng' => 'required|numeric',
        ]);

        $taxon = Taxon::where('scientificname', $validated['scientific_name'])->first();

        $alert = Alert::create([
            'taxon_id' => $taxon?->id,
            'scientific_name' => $validated['scientific_name'],
            'event_type' => AlertEventType::tryFrom($validated['event_type']) ?? AlertEventType::NEW_ARRIVAL,
            'severity' => AlertSeverity::tryFrom($validated['severity']) ?? AlertSeverity::WARNING,
            'source_type' => 'easin',
            'source_id' => $validated['alert_id'],
            'subregion' => $this->resolveSubregion($validated['location']['lat'], $validated['location']['lng']),
            'metadata' => [
                'lat' => $validated['location']['lat'],
                'lng' => $validated['location']['lng'],
                'easin_url' => $validated['reference_url'] ?? null,
            ],
        ]);


        if ($alert) {
            app(AlertDistributor::class)->distribute($alert);
        }

        return response()->json(['status' => 'accepted'], 202);
    }
}
```

### 3.4 EASIN Outbound — MAMIAS → EASIN

The existing `EasinService::fetchEasinId()` is read-only. New method for pushing MAMIAS data:

```php
// Extend EasinService
public function pushOccurrence(Occurrence $occurrence): bool
{
    $taxon = $occurrence->introEventRecord?->taxon;
    if (! $taxon?->Easin_id) {
        return false;
    }

    $response = Http::timeout(15)
        ->post('https://easin.jrc.ec.europa.eu/api/observations', [
            'easin_id' => $taxon->Easin_id,
            'scientific_name' => $taxon->scientificname,
            'lat' => $occurrence->location[0]['lat'],
            'lng' => $occurrence->location[0]['lng'],
            'observed_at' => $occurrence->observed_at->toIso8601String(),
            'source' => 'MAMIAS',
            'occurrence_id' => url("/mamias/intro-event-records/{$occurrence->intro_event_record_id}/edit"),
        ]);

    return $response->successful();
}
```

### 3.5 Scheduled Polling — `routes/console.php`

```php
use Illuminate\Support\Facades\Schedule;

// Daily: fetch EASIN alerts
Schedule::call(function () {
    app(EasinService::class)->fetchRecentAlerts();
})->dailyAt('06:00');

// Weekly: sync GBIF Alert observations for Mediterranean
Schedule::call(function () {
    $observations = app(GbifAlertService::class)->fetchNewMedOccurrences();
    // Log matches for admin review
})->weeklyOn(1, '06:00'); // Monday

// Weekly: check GuardIAS emerging species indicators
Schedule::call(function () {
    $emerging = app(GuardiasIndicatorService::class)->fetchEmergingSpecies();
    // Create info-level alerts for high-scoring species
})->weeklyOn(1, '07:00');

// Daily at 08:00: digest generation for daily subscribers
Schedule::job(new GenerateDailyDigest)->dailyAt('08:00');

// Weekly on Monday: digest generation for weekly subscribers
Schedule::job(new GenerateWeeklyDigest)->weeklyOn(1, '09:00');
```

### 3.6 G.I.A.S. Integration (Future)

GuardIAS Intelligence Analyst System (beta 2026) will expose an API for:
- Species trait data (biological traits, environmental tolerances)
- Invasiveness predictions
- Distribution maps from harmonized data (EASIN + AquaNIS + WRiMS + OBIS)

When available, `GiasService` will be:
```php
class GiasService
{
    private string $baseUrl = 'https://gias.guardias.eu/api/v1';

    public function getSpeciesIntelligence(int $aphiaId): array
    {
        return Cache::remember("gias_species_{$aphiaId}", 86400, function () use ($aphiaId) {
            $response = Http::timeout(15)
                ->get("{$this->baseUrl}/species/{$aphiaId}");

            return $response->successful() ? $response->json() : [];
        });
    }

    public function getInvasivenessScore(int $aphiaId): ?float
    {
        $data = $this->getSpeciesIntelligence($aphiaId);

        return $data['invasiveness_score'] ?? null;
    }
}
```

---

## 4. NOTIFICATION & CHANNEL DELIVERY

### 4.1 Multi-Channel Notification — `AlertNotification`

```php
class AlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Alert $alert,
        public array $channels = ['mail', 'database'],
    ) {}

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $severityColor = match ($this->alert->severity->value) {
            'critical' => '#dc2626',
            'warning' => '#f59e0b',
            default => '#3b82f6',
        };

        return (new MailMessage)
            ->subject("[MAMIAS EWS] {$this->alert->title}")
            ->greeting("Early Warning Alert — {$this->alert->severity->getLabel()}")
            ->line(new HtmlString(
                "<div style='border-left:4px solid {$severityColor}; padding-left:12px;'>"
                . markdown($this->alert->description)
                . '</div>'
            ))
            ->action('View Alert', url("/mamias/ew/alerts/{$this->alert->id}"))
            ->line('You received this because of your EWS subscription settings.')
            ->line('[Manage subscriptions](' . url('/profile') . ')');
    }

    public function toDatabase(object $notifiable): array
    {
        $icon = match ($this->alert->severity->value) {
            'critical' => 'tabler-bell-ringing',
            'warning' => 'tabler-alert-triangle',
            default => 'tabler-info-circle',
        };

        $color = match ($this->alert->severity->value) {
            'critical' => 'danger',
            'warning' => 'warning',
            default => 'info',
        };

        return FilamentNotification::make()
            ->title($this->alert->title)
            ->icon($icon)
            ->iconColor($color)
            ->body(str($this->alert->description)->limit(200))
            ->actions([
                FilamentAction::make('view')
                    ->button()
                    ->url(AlertResource::getUrl('view', ['record' => $this->alert])),
                FilamentAction::make('dismiss')
                    ->color('gray')
                    ->emit('dismissAlert', $this->alert->id),
            ])
            ->getDatabaseMessage();
    }

    // WhatsApp channel — new channel class
    public function toWhatsApp(object $notifiable): string
    {
        return "🔴 *MAMIAS EWS* 🔴\n\n"
            . "{$this->alert->title}\n\n"
            . "Severity: {$this->alert->severity->getLabel()}\n"
            . "Species: {$this->alert->scientific_name}\n"
            . "Region: {$this->alert->subregion}\n\n"
            . "View: " . url("/mamias/ew/alerts/{$this->alert->id}");
    }
}
```

### 4.2 WhatsApp Channel — New `WhatsAppChannel`

```php
// app/Notifications/Channels/WhatsAppChannel.php

class WhatsAppChannel
{
    public function __construct(private WhatsAppService $whatsapp) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof User || ! $notifiable->has_whatsapp || ! $notifiable->phone) {
            return;
        }

        $message = $notification->toWhatsApp($notifiable);

        $this->whatsapp->sendMessage($notifiable->phone, $message);
    }
}
```

Extend `WhatsAppService` with `sendMessage()`:
```php
public function sendMessage(string $phone, string $message): bool
{
    $phone = $this->normalizeToE164($phone);
    if (! $phone) {
        return false;
    }

    $response = Http::timeout(10)->post(
        "https://api.greenapi.com/waInstance{$this->instanceId}/sendMessage/{$this->token}",
        ['chatId' => "{$phone}@c.us", 'message' => $message]
    );

    return $response->successful();
}
```

Register channel in `config/services.php` and in `AppServiceProvider`:
```php
// Notifications will use 'whatsapp' as a via() channel
// Laravel resolves WhatsAppChannel automatically via DI
```

### 4.3 `SendAlertNotification` Job

```php
class SendAlertNotification implements ShouldQueue
{
    use Queueable;

    public int $timeout = 30;
    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    public function __construct(
        private Alert $alert,
        private AlertSubscription $subscription,
    ) {}

    public function handle(): void
    {
        $user = $this->subscription->user;

        $channels = $this->subscription->channels;

        // Send via each configured channel
        $notification = new AlertNotification($this->alert, $channels);

        if (! empty($channels)) {
            $user->notify($notification);
        }

        // Update last_sent_at for throttle tracking
        $this->subscription->update(['last_sent_at' => now()]);
    }
}
```

### 4.4 Digest Generation

```php
class GenerateDailyDigest implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $subscriptions = AlertSubscription::where('is_active', true)
            ->where('frequency', 'daily')
            ->with('user')
            ->get();

        foreach ($subscriptions as $subscription) {
            // Get alerts since last digest (or last 24h)
            $since = $subscription->last_sent_at ?? now()->subDay();

            $alerts = Alert::where('published_at', '>=', $since)
                ->when($subscription->subregion, fn ($q) => $q->where('subregion', $subscription->subregion))
                ->when($subscription->min_severity, fn ($q) => $q->where('severity', '>=', $subscription->min_severity))
                ->get();

            if ($alerts->isEmpty()) {
                continue;
            }

            Notification::send($subscription->user, new DailyDigestNotification($alerts));

            $subscription->update(['last_sent_at' => now()]);
        }
    }
}
```

### 4.5 Delivery Tracking

The existing `notifications` table already tracks `read_at`. Extend the database notification with a `dismissed_at` column via a new migration if needed. Or use the Alert model's `expires_at` and `AlertStatus` for lifecycle management.

---

## 5. OBSERVER & JOB WIRING

### 5.1 `EwsObserver` — Full Implementation

Following the `LiteratureObserver` pattern exactly (registered in model `booted()` or via auto-discovery):

```php
// app/Observers/EwsObserver.php

class EwsObserver
{
    public function __construct(
        private EwsEvaluator $evaluator,
        private AlertGenerator $generator,
        private AlertDistributor $distributor,
    ) {}

    public function saved(Occurrence $occurrence): void
    {
        // Only evaluate when status changes TO approved
        if (! $occurrence->wasChanged('status') || $occurrence->status !== OccurrenceStatus::APPROVED) {
            return;
        }

        $results = $this->evaluator->evaluate($occurrence);

        foreach ($results as $result) {
            $alert = $this->generator->generate($result['type'], $result['context']);

            if ($alert) {
                $this->distributor->distribute($alert);

                // Refresh sidebar badge
                $this->dispatch('refresh-sidebar');
            }
        }
    }

    public function saved(NisSuggestion $suggestion): void
    {
        // Also evaluate approved NisSuggestions (citizen science reports)
        if (! $suggestion->wasChanged('status') || $suggestion->status !== LiteratureStatus::APPROVED) {
            return;
        }

        // Create a pseudo-occurrence context for evaluation
        $coords = $suggestion->location;
        $first = is_array($coords) ? ($coords[0] ?? null) : $coords;
        if (! $first || ! isset($first['lat'], $first['lng'])) {
            return;
        }

        // Simplified evaluation for NisSuggestions (new arrival + high impact)
        $taxon = $suggestion->taxon;
        if (! $taxon) {
            return;
        }

        $subregion = $this->resolveSubregion((float) $first['lat'], (float) $first['lng']);
        if (! $subregion) {
            return;
        }

        // New arrival from citizen science?
        $existing = SubregionRecord::whereHas('introEvent', fn ($q) => $q->where('taxon_id', $taxon->id))
            ->where('subregion', $subregion)
            ->exists();

        if (! $existing) {
            $alert = $this->generator->generate(AlertEventType::NEW_ARRIVAL, [
                'taxon_id' => $taxon->id,
                'scientific_name' => $taxon->scientificname,
                'source_type' => 'nis_suggestion',
                'source_id' => $suggestion->id,
                'subregion' => $subregion,
                'metadata' => [
                    'lat' => (float) $first['lat'],
                    'lng' => (float) $first['lng'],
                    'suggestion_url' => route('filament.mamias.resources.nis-suggestions.view', $suggestion),
                ],
            ]);

            if ($alert) {
                $this->distributor->distribute($alert);
            }
        }
    }

    private function resolveSubregion(float $lat, float $lng): ?string
    {
        // Same logic as EwsEvaluator
        foreach (EwsEvaluator::SUBREGION_BOUNDS as $name => $bounds) {
            if ($lat >= $bounds['south'] && $lat <= $bounds['north']
                && $lng >= $bounds['west'] && $lng <= $bounds['east']) {
                return $name;
            }
        }

        return null;
    }
}
```

### 5.2 Registration in `AppServiceProvider`

```php
// app/Providers/AppServiceProvider.php

public function boot(): void
{
    Occurrence::observe(EwsObserver::class);
    NisSuggestion::observe(EwsObserver::class);
}
```

Alternatively, auto-discovery — no explicit registration needed because Laravel scans `app/Observers/` when the model has `protected $observables` or uses the `#[ObservedBy]` attribute.

For clarity, explicit registration in `AppServiceProvider::boot()` is preferred (matching existing `Event::listen` pattern already used there).

### 5.3 AlertDistributor — Dispatch Logic

```php
class AlertDistributor
{
    public function distribute(Alert $alert): void
    {
        $subscriptions = AlertSubscription::where('is_active', true)->get();

        foreach ($subscriptions as $subscription) {
            // Filter by subregion
            if ($subscription->subregion && $alert->subregion !== $subscription->subregion) {
                continue;
            }

            // Filter by severity threshold
            if ($subscription->min_severity) {
                $severityOrder = ['info' => 0, 'warning' => 1, 'critical' => 2];
                $alertLevel = $severityOrder[$alert->severity->value] ?? 0;
                $subLevel = $severityOrder[$subscription->min_severity] ?? 0;
                if ($alertLevel < $subLevel) {
                    continue;
                }
            }

            // Filter by taxonomic group
            if ($subscription->taxonomic_group && $alert->taxon) {
                $speciesPhylum = $alert->taxon->phylum;
                if ($speciesPhylum !== $subscription->taxonomic_group) {
                    continue;
                }
            }

            // Immediate delivery
            if ($subscription->frequency === 'immediate') {
                SendAlertNotification::dispatch($alert, $subscription);
            }

            // Daily/weekly: cached for batch processing
            // Handled by GenerateDailyDigest / GenerateWeeklyDigest
        }
    }
}
```

---

## 6. MIGRATION — `create_alerts_and_subscriptions_tables`

```php
// database/migrations/2026_06_24_000001_create_alerts_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taxon_id')->nullable()->constrained('taxas')->nullOnDelete();
            $table->string('scientific_name', 255);
            $table->string('event_type', 30);       // AlertEventType enum
            $table->string('severity', 10);          // AlertSeverity enum
            $table->string('title', 255);
            $table->text('description');
            $table->string('source_type', 50)->nullable(); // occurrence, nis_suggestion, intro_event, easin
            $table->string('source_id', 100)->nullable();  // Polymorphic FK (string for EASIN IDs)
            $table->string('subregion', 50)->nullable();   // Subregion enum value
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Indexes for alert queries
            $table->index('event_type');
            $table->index('severity');
            $table->index(['subregion', 'event_type']);
            $table->index('created_by');
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('alert_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subregion', 50)->nullable();
            $table->foreignId('taxon_id')->nullable()->constrained('taxas')->nullOnDelete();
            $table->string('taxonomic_group', 100)->nullable(); // phylum name
            $table->string('min_severity', 10)->nullable();     // AlertSeverity enum
            $table->json('channels')->default('["mail","database"]');
            $table->string('frequency', 20)->default('immediate'); // immediate, daily, weekly
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['subregion', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_subscriptions');
        Schema::dropIfExists('alerts');
    }
};
```

---

## SUMMARY — File Implementation Order

| Step | Files | Pattern Used |
|---|---|---|
| 1 | `AlertSeverity`, `AlertEventType` enums | `EstablishmentStatus` pattern |
| 2 | Migration + `Alert`, `AlertSubscription` models | `Occurrence` pattern |
| 3 | `EwsEvaluator` + `AlertGenerator` + `AlertDistributor` | Service DI from `WormsService` pattern |
| 4 | `EwsObserver` | `LiteratureObserver` pattern |
| 5 | `AlertNotification` + `WhatsAppChannel` | `NewLiteratureReferenceNotification` pattern |
| 6 | `SendAlertNotification` + `GenerateDailyDigest` | `FetchEasinIdsJob` pattern |
| 7 | `AlertResource` + `AlertsTable` + `AlertInfolist` | `NisSuggestionResource` pattern |
| 8 | `AlertSubscriptionResource` | `UserResource` pattern |
| 9 | `ActiveAlertsWidget` | `PendingOccurrencesWidget` / `CatalogueStatsWidget` pattern |
| 10 | `PublicAlerts` Livewire | `MySuggestions` pattern |
| 11 | `GbifAlertService` + `GuardiasIndicatorService` | `EasinService` pattern |
| 12 | `EasinWebhookController` + API routes | New controller |
| 13 | Console schedule | Extend `routes/console.php` |

Would you like me to refine any specific component further, or proceed with an implementation approach for a given phase?

---

