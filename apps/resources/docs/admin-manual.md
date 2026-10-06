MAMIAS is run from one admin panel at **/mamias**. Two roles can enter it:
**super_admin** runs the whole platform, **scientist** curates the data. Everyone
else (role **user**) works only on the public site.

[TOC]

## Roles

| Area | super_admin | scientist | user |
| --- | --- | --- | --- |
| Admin panel at /mamias | Yes | Yes | No, sent to the public site |
| Review references, occurrences, NIS suggestions | Yes | Yes | No |
| Edit the catalogue: taxa, introduction events | Yes | Yes, as their permissions allow | No |
| Add occurrences directly (approved at once) | Yes | Yes | No, reports go to review |
| CMS pages and dashboards | Yes | As permissions allow | No |
| Users and roles | Yes | No | No |
| System tools: SEO files, health, backups, logs, jobs, mailbox | Yes | No | No |
| Submit references, reports, suggestions | Yes | Yes | Yes, from the public site |

**How access is decided.** A super_admin passes every check automatically. A
scientist is limited by the permissions attached to the scientist role (Filament
Shield). New accounts get the **user** role on registration; only a super_admin
can raise someone to scientist or super_admin.

**After sign-in**, super_admins and scientists land in the panel; users land on
the public home page.

## Signing in and finding your way

Sign in at **/mamias/login** with your email and password, then tick the human
check (CAPTCHA). The panel locks itself after 30 minutes without activity; type
your password to unlock it.

![The panel, on the dashboard](/images/docs/manuals/admin-01-panel.png)

The left menu has these groups:

| Group | What it holds |
| --- | --- |
| Dashboard | Catalogue figures and charts, in tabs |
| MAMIAS database | Literatures, MAMIAS Catalogue (taxa), Introduction Events, Occurrences, Species Suggestions |
| Content management | Pages (the public site's CMS pages and dashboards) |
| Use management | Users, Roles |
| System | Super_admin tools (see [System tools](#guide-system-tools-superadmin-only)) |
| Help | This manual, and the User manual of the public site |

**Badges** next to a menu item count what waits for you, such as references or
occurrences pending review.

**Notifications that stay.** While work is waiting, a notification stays in the
top corner of every page until you close it with ×: *Pending Occurrences*, *New
comments on references*, *New accepted names in WoRMS*. Each links straight to
the filtered list. A closed one returns when its count changes.

![Notifications waiting in the top corner](/images/docs/manuals/admin-02-notifications.png)

**Guide buttons.** Literatures, the Catalogue, Introduction Events and
Occurrences each have a **Guide** button at the top right: an illustrated,
step-by-step help for that screen. Use it before this manual for screen detail.

**Shortcuts.** Press Ctrl+K (Cmd+K on a Mac) to search the whole panel. **Public
site** in the top bar opens the public website. Tables remember your filters;
**Saved views** stores a set-up under a name.

## Review queues

Three kinds of public submission wait for a moderator: bibliographic references,
species occurrences and NIS suggestions. All three follow the same rule: nothing
a contributor sends is public until a super_admin or scientist approves it, and
every decision emails the contributor.

| Queue | Menu item | Approve does | Reject needs | After a rejection the contributor can |
| --- | --- | --- | --- | --- |
| References | Literatures, *Pending Review* tab | Makes it citable in the database | A reason | Discuss it only; rejection is final |
| Occurrences | Occurrences | Publishes the sighting on the species page | A reason | Revise and resubmit the same report |
| NIS suggestions | Species Suggestions | Creates the taxon in the catalogue (draft) | A reason | Resubmit a copy with more evidence |

A submission goes from *Pending Review* to a moderator's decision. Approved, it
is made public and the contributor is emailed. Rejected, the reason is emailed;
a rejected reference stops there, while a rejected occurrence or suggestion can
come back to the queue once the contributor revises it.

### References

1. Open **Literatures** on the **Pending Review** tab (you land there while
   anything is pending).
2. **View** to read it, or **Edit** to open its page.
3. **Approve** (comment optional) or **Reject** (reason required). **Approve &
   next** and **Reject & next** move straight to the next pending reference.

![Approving a reference](/images/docs/literatures/03-approve.png)

Rejection is final, so fix wrong metadata yourself (**Edit**, correct, then
**Approve** and say what you changed) and keep **Reject** for out-of-scope or
unusable sources. Tick several rows and use **Bulk actions** to decide many at
once. **Merge into…** folds a duplicate into the reference you keep; it cannot be
undone.

### Occurrences

1. Open **Occurrences**. The map above the list shows every listed report: green
   approved, gray pending, red rejected. Click a pin, or **View** in a row's ☰
   menu, to open the report.
2. Check the point is in the sea and plausible, the date makes sense, the photos
   show the species named.
3. **Approve** (note optional) or **Reject** (reason required, written for the
   contributor).

![The occurrences map and list](/images/docs/occurrences/01-list.png)

A report marked *Resubmitted* (hover its status) was rejected once and revised;
the earlier reason is shown so you can check it was addressed. Occurrences cannot
be edited in the panel: send corrections back as a rejection with a note. **New
occurrence** adds a report yourself; it is approved at once.

### NIS suggestions

1. Open **Species Suggestions** and **View** the suggestion: name and Aphia ID
   from WoRMS, photos, location, linked references.
2. **Approve** asks for the scientific name, authority, catalogue status and
   notes, then **Create Taxon & Approve** adds the species to the catalogue
   (unless it is already there).
3. **Reject** needs a reason. The contributor may resubmit a copy with more
   evidence.

![A suggestion waiting for review](/images/docs/manuals/admin-03-suggestion-review.png)

The **Discussion** at the bottom of a suggestion is visible to the contributor,
but nobody is emailed when someone writes there: check it when you open a
suggestion.

## The catalogue

The catalogue has two linked lists: **MAMIAS Catalogue** holds each
non-indigenous species (taxon), checked against WoRMS, and **Introduction
Events** holds each species' first record in the Mediterranean. Every species
has at most one event, and every occurrence belongs to one. Each screen's
**Guide** button has the step-by-step detail.

### Species (MAMIAS Catalogue)

The tabs are work queues. Keep these empty:

| Tab | What to do |
| --- | --- |
| Not checked Yet | Wait for the WoRMS check (new imports land here) |
| Checked & not accepted | WoRMS accepts another name: review it |
| No data from WORMS | Check the spelling; confirm through GBIF or enter by hand |
| Duplicates | Merge the copies |
| Name to update | WoRMS changed the accepted name: move, keep or send for expert review |
| First records to reconcile | Keep one first-record event after a merge |

On **Name to update**, a confidence score guides the decision: **Safe to move**
(90% and up) can be moved in bulk; below that, read the evidence and write a note.
**Send for expert review** posts your question to a scientist in the species'
Discussion. **Undo name move** reverses the latest move.

![Names WoRMS has changed, with confidence scores](/images/docs/taxa/10-name-to-update.png)

**Import NIS Taxa** adds many species from a CSV or Excel file with a
*Scientific Name* column. Names already catalogued are skipped, so repeating an
import is safe. Imports run in the background (see [Routines and
troubleshooting](#guide-routines-and-troubleshooting)).

### Introduction events

An event records the year and country of the first Mediterranean record, the NIS
and establishment status, the EcAp sub-regions and the CBD pathways. Dashboard
figures follow the validated baseline (Galanidi et al., 2023): they count events
whose NIS status is **NIS** or not yet set; Cryptogenic, Questionable, Range
Expansion and Data Deficient stay in the catalogue but out of the figures.

Two tabs need regular attention:

- **Needs review**: an import or data correction left a value it could not read.
  **Edit**, enter the right value, save.
- **Pathway check**: MAMIAS and EASIN disagree on the pathway. Green and orange
  rows can take **Apply EASIN decision**; red conflicts need an expert, then
  **Keep MAMIAS pathways** or **Edit**. EASIN only confirms or suggests; it never
  overwrites a pathway on its own.

![Pathways that differ from EASIN](/images/docs/intro-events/08-pathway-check.png)

**Delete** moves an event to **Trashed**, where it can be restored. **Force
delete** is blocked while the event still has occurrences.

## Public site content

The public site's pages are built in the panel under **Content management →
Pages** (the Layup page builder). Today there are seven: Home, About MAMIAS, the
Mediterranean and by-country dashboards, and the three legal pages (Terms of use,
Cookies policy, Legal notice).

![The CMS pages](/images/docs/manuals/admin-04-pages.png)

- **Address.** A page's slug is its address: *about* is served at /about. The
  page whose slug is *home* is the home page at /.
- **Status.** *Draft* is hidden; *Published* is live; a page published with a
  future date waits as *Scheduled* until then.
- **Description.** Fill in each page's meta description: search engines show it,
  and it becomes the page's line in llms.txt.
- **Dashboards.** The two dashboard pages draw their charts from the catalogue
  live; their figures follow the validated baseline (see [The
  catalogue](#guide-the-catalogue)).

The data explorer (/pages/data), the species pages (/pages/data/…) and the User
manual (/pages/manual) are not CMS pages: they are built by the application and
need no editing here.

### SEO files (super_admin)

**System → SEO files** writes the files search engines and AI agents read:

![The SEO files page](/images/docs/manuals/admin-05-seo-files.png)

| File | Holds | When to regenerate |
| --- | --- | --- |
| sitemap.xml | Every published page, the data explorer and every species page | After publishing pages or adding many species |
| llms.txt / llms-full.txt | A short index and the full text of the site, for AI agents | Same |
| robots.txt | The rules for crawlers | Edit by hand; **Reset to template** adds the sitemap address |

Click **Generate** on a file to rebuild it. Nightly regeneration is switched off
until the server runs a scheduler, so generate by hand for now. Be careful with
robots.txt: a single *Disallow: /* removes the whole site from search engines.
**Sitemap URLs** adds addresses no source lists; an address the site already
serves is refused.

## Users and roles (super_admin only)

Accounts are managed under **Use management → Users**; what each role may do is
set under **Use management → Roles**.

**Promote a contributor to scientist.** Open the user, **Edit**, add *scientist*
under **Roles**, save. They see the panel at their next page load. Remove the
role the same way.

![Editing a user, with the Roles field](/images/docs/manuals/admin-06-user-edit.png)

**Create an account** (for a colleague who will not self-register): **New
user**, fill in name, email, a password, the roles, and set **Email Verified At**
so they can sign in at once.

**Unblock a sign-up.** Self-registered users must confirm their email before
they can contribute. If the email never arrived, set **Email Verified At** on
their account.

The profile also holds the user's country, phone (with a WhatsApp check) and
professional focus: taxonomic areas, EcAp sub-regions and countries. A user's
page lists the occurrences they reported.

**Roles.** Each role is a set of permissions per screen and action (view,
create, edit, delete, and so on). Change the *scientist* role here to widen or
narrow what all scientists can do. The *super_admin* role needs no permissions:
it passes every check. Give super_admin only to the few people who run the
platform.

![The roles](/images/docs/manuals/admin-07-roles.png)

**Who signed in.** **System → Authentication log** lists every sign-in with its
time, address and browser; use it to check a suspicious account.

## System tools (super_admin only)

The **System** menu group is visible to super_admins only. Most of it is for
diagnosis; three tools change the live site and deserve care: **Backups**
(restore), **Command runner** and **SEO files** (robots.txt).

| Tool | Use it to |
| --- | --- |
| Health | See at a glance whether the database, cache and debug settings are healthy |
| Backups | Take a database backup, download it, or restore one |
| Application logs | Read the server's error log when something fails |
| Exceptions | Browse recorded errors with their stack trace |
| Jobs monitor | Follow background jobs (imports, WoRMS checks, emails) and see failures |
| Activity log | See who changed which record, when, and what changed |
| Authentication log | See every sign-in, with time, address and browser |
| Resource locks | Free a record left locked by someone who closed their browser mid-edit |
| Command runner | Run maintenance commands from the browser |
| SEO files | Regenerate sitemap.xml and llms.txt, edit robots.txt |
| File manager | Browse and manage uploaded files |
| Database schema, Architecture, Composer and npm dependencies | Technical reference for developers |
| Mailbox (/mamias/mailbox) | On the development server only: read every email the app sent, instead of delivering it |

**Record locks.** When two people open the same reference, species or event, the
second sees it read-only with the name of the first. A super_admin can
force-unlock it from the banner, or from **Resource locks**.

**Clear cache** (top bar) refreshes the panel when a screen looks out of date
after an update.

## Routines and troubleshooting

A few minutes a day keeps contributors answered and the catalogue clean.

**Every working day (scientists and super_admins)**

- Clear the pending notifications: references, occurrences, suggestions.
- Answer new comments on references (the *New comments on references*
  notification).
- Glance at suggestions' Discussions: nobody is emailed about them.

**Every week**

- Catalogue: work the *Name to update* and *Checked & not accepted* tabs.
- Introduction events: work *Needs review* and *Pathway check*.

**Every month (super_admin)**

- Health is green; no failed jobs in the Jobs monitor.
- Download a backup and keep it off the server.
- Regenerate the SEO files if pages or many species were added.
- Review who holds super_admin and scientist.

### Automatic tasks

Two tasks are meant to run on their own: enriching references (weekly) and
checking catalogue names against WoRMS (monthly, on the 1st at 03:00). **They
run only if the server runs the Laravel scheduler, and today it does not**: ask
the technical administrator to add one, or the checks never happen. Imports,
WoRMS checks and emails also need the background worker (the *queue* container)
to be running; if an import stays at 0%, check it in the Jobs monitor.

### When something goes wrong

| Symptom | Likely cause | What to do |
| --- | --- | --- |
| A screen shows old content after an update | Cached panel | **Clear cache** in the top bar |
| A button does nothing, or a window does not open | A server error | Note the time, check **Exceptions** and **Application logs**, report it |
| A record opens read-only with someone's name | It is locked by an open edit | Wait, or force-unlock (super_admin) |
| Contributors say they got no email | Mail delivery or the background worker | Check **Jobs monitor** for failed jobs |
| An import stays at 0% | Background worker stopped | Ask the technical administrator to restart the queue |
| A user cannot sign in after registering | Email not confirmed | Set **Email Verified At** on their account |
| A map is grey | The UNEP/MAP basemap server is unreachable | Wait and reload; nothing to fix in MAMIAS |
