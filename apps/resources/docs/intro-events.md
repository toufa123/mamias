An introduction event is the first record of a non-indigenous species in the
Mediterranean: the year, the country, the species' NIS and establishment
status, and where and how it arrived (EcAp sub-regions and CBD pathways). Each
species in the [MAMIAS catalogue](/mamias/taxons/taxa) has at most one event.

[TOC]

## The events list

![The Intro Events list with its status tabs](/images/docs/intro-events/01-list.png)

The list opens on **All**. The next five tabs split the events by NIS status:
**NIS**, **Cryptogenic**, **Questionable**, **Range Expansion** and **Data
Deficient** (see [NIS status and the validated baseline](#guide-nis-status-and-the-validated-baseline)).
The others are work queues:

- **Needs review.** An import or a data correction left something for a person
  to check (see [Review flagged events](#guide-review-flagged-events)).
- **Pathway check.** EASIN lists different pathways from MAMIAS (see
  [Check pathways against EASIN](#guide-check-pathways-against-easin)).
- **Pathway checked.** Checks already settled, with the decision taken.
- **Trashed.** Deleted events, which can be restored.

**Search** finds a species by name. **Export** downloads the current tab as
Excel, CSV or PDF, with filters, search and sort applied. The **☰** icon at the
end of each row holds every action on that event.

Under the species name, *Recorded as …* means the event was published under a
name the catalogue has since moved away from. *Species deleted from catalogue*
means the species is in the catalogue's trash while its event is still here.

The funnel icon opens the filters:

![Filters: year range, country, establishment, sub-region and pathways](/images/docs/intro-events/02-filters.png)

- **1st Year of Introduction.** Drag the two handles to set a year range.
- **1st Country of Introduction**, **Establishment Status**, **EcAp Subregion.**
- **Present in Country.** Every country the species has been recorded in, not
  only the first one.
- **CBD Pathway Category**, **Pathway Subcategory**, **Pathway Type**,
  **Uncertainty.** Picking a category narrows the subcategories to that
  category's.

Set the filters, then click **Apply filters**. **Reset** clears them.

## View an event

Click **View** in the row's **☰** menu.

![An event's view window, on its Sub-regions tab](/images/docs/intro-events/03-view.png)

The top shows the first Mediterranean record (year and country), the NIS and
establishment statuses, and the linked reference. A warning appears when the
event is flagged for review or has a pathway check waiting.

Four tabs, each with its count:

- **Sub-regions.** NIS status, establishment and first record in each EcAp
  sub-region. A sub-region's NIS status can differ from the event's: a species
  validated in one sub-region may be questionable or debatable in another.
- **Countries.** Every Mediterranean country the species has been recorded in,
  each with its establishment, first record and reference.
- **Pathways.** CBD category and subcategory, type and uncertainty.
- **Notes & audit.** Notes, and when the record was created and last changed.

## NIS status and the validated baseline

The figures on the dashboards follow the validated baseline of Galanidi et al.
(2023, *Diversity* 15: 962): they count only events whose NIS status is
**NIS**, plus events whose status is not set yet. Events with any other status
stay in the catalogue but are left out of the figures:

- **Cryptogenic.** Origin unknown.
- **Questionable.** A questionable record: insufficient information (no
  voucher or description) or uncertain identification, including records only
  on a vessel or from shells only. This is a NIS status, not an establishment
  status: a *que* found in a file's establishment column is stored here, and
  the establishment is left empty.
- **Range Expansion.** Arrived by natural spread, not introduced.
- **Data Deficient.** Experts disagree on the status (*debatable*), a likely
  alien polychaete or foraminiferan, or a record from one location awaiting
  identification.

**1st Country of Introduction** and **Year of 1st Introduction** describe a
single event: where the species was first recorded anywhere in the
Mediterranean. The **Countries** tab is a different thing: the species' presence
in each country, the national inventories of the baseline. The first country is
normally the one with the earliest year in that list.

Every status counts the first record, whatever the establishment: a casual
first record is still the first record.

## Add or edit an event

1. Click **New Intro Event**.
2. Pick the **NIS Scientific Name**. Only species without an event are offered;
   the badge beside the label says how many are left.
3. Set the **NIS Status**, **Establishment Status**, **Year of 1st
   Introduction** and **1st Country of Introduction** (several countries are
   allowed when the species arrived in more than one at once).
4. Under **References & Notes**, link the **Citations / Literature** and add notes.
5. Fill the three tabs below, then click **Create**.

![New event: picking a species without an event](/images/docs/intro-events/04-create.png)

**EcAp Subregions** takes one row per sub-region (up to four): the sub-region,
its NIS status there, its establishment success and its year of first
introduction. **Add Subregion Record** adds a row; the bin removes one.

![Sub-regions tab of the edit page](/images/docs/intro-events/05-edit-subregions.png)

**Countries** takes one row per country the species has been recorded in,
including the first one: the country, establishment, year of first record and
reference. A country can appear only once.

**Pathways** takes up to four pathways: type, CBD category, subcategory and
uncertainty. **Pathway Type** and **CBD Category** are required. The
**Subcategory** is optional, since a pathway is often known only at category
level; it stays greyed out until a category is picked, then lists only that
category's subcategories.

![Pathways tab of the edit page](/images/docs/intro-events/06-edit-pathways.png)

To change an event, click **Edit** in its **☰** menu, then **Save changes**.
Saving takes the event off the *Needs review* tab.

## Review flagged events

An import keeps a row even when some of its values cannot be read, such as a
year written *1934-38* or an unknown status. The row lands on **Needs review**,
and **Review Reason** shows each value that was not understood, as it appeared
in the file.

![Needs review tab with the reason column](/images/docs/intro-events/07-needs-review.png)

1. Click **Edit** in the row's **☰** menu.
2. Enter the right values. The original text stays in the **Notes**.
3. Click **Save changes**. The event leaves the tab.

A data correction can also flag an event, with a line in the **Notes** starting
*Needs review (source) —* that **Review Reason** shows. For example, an event
recorded as NIS whose species the baseline lists only as *Removed* in its
sub-regions.

## Check pathways against EASIN

**Pathway check** holds the events whose pathways differ from EASIN's.
**Pathway Check (EASIN)** lists what only MAMIAS and only EASIN give, and
**EASIN Decision** proposes what to do. MAMIAS is the validated Mediterranean
inventory and EASIN covers the whole EU, so EASIN confirms or suggests; it never
overwrites a pathway on its own.

![Pathway check tab with the EASIN decision](/images/docs/intro-events/08-pathway-check.png)

- **Green:** the two agree once EU-wide context is taken into account.
- **Orange:** a proposal. *add-corridor* adds Corridor 5.1 for a Levantine
  first record; *adopt-easin* takes EASIN's pathway when MAMIAS has none.
- **Red:** *conflict* (no category in common) or *no-easin* (EASIN could not
  be read). An expert decides.

![Row menu with the EASIN actions](/images/docs/intro-events/09-pathway-menu.png)

- **Apply EASIN decision.** Shown for green and orange decisions. Confirming
  applies the proposal, if any, writes the decision into the notes and settles
  the check. When EASIN's pathway has no exact MAMIAS equivalent, nothing
  changes and a message asks you to edit the event instead.

  ![Apply EASIN decision confirmation](/images/docs/intro-events/10-easin-confirm.png)

- **Keep MAMIAS pathways.** Keeps the pathways as recorded, notes the decision
  and settles the check. Use it for conflicts once you have checked the source.
- **Edit.** To set the pathways by hand. Empty the **Pathway check (EASIN)**
  box once they are right, then save.

A settled check leaves **Pathway check** for **Pathway checked**, newest first.
There, **Decision** gives what was decided and why, **Original Check (EASIN)**
the difference that was settled, and **Checked** the date. Checks settled before
this tab existed show their decision but not the original text.

## Delete and restore

**Delete** in the **☰** menu moves an event to **Trashed**, where **Restore**
brings it back. **Force delete** removes it for good, with its sub-region and
pathway rows. It stays greyed out while the event has occurrences: delete or
reassign those first. Ticking several rows gives the same actions in bulk.

## Import events from a file

**Import Intro Events** loads a MAMIAS baseline file. Import the species into
the catalogue first (**Import NIS Taxa** on the catalogue list): a row whose
species is not in the catalogue is rejected.

1. Prepare a CSV or Excel file, one species per row. The window offers example
   CSV and XLSX files with every column.
2. Click **Import Intro Events** and drop the file in the box.

   ![Import Intro Events window](/images/docs/intro-events/11-import-modal.png)

3. Under **Columns**, check that each field points at the right column. Only
   **Scientific Name** is required.

   ![Column mapping after upload](/images/docs/intro-events/12-import-mapping.png)

4. Click **Import**. It runs in the background, 100 rows at a time.

The columns:

- **Scientific Name**, **First Introduction Year**, **Country**, **NIS
  Status**, **Establishment Status**, **Notes**. Several countries are
  separated by a comma or a slash (*Lebanon/Syria*).
- **Establishment Status** and **First Arrival Year** for each sub-region:
  WMED, CMED, Adriatic and EMED.
- One column per CBD pathway. Any value other than empty or *0* marks the
  pathway.

A species that already has an event is updated in place, even in the trash,
never duplicated. Sub-regions and pathways from the file are added to the
event's; the ones already there are kept. Names are matched to the catalogue,
then through WoRMS for synonyms and misspellings; the file's spelling is kept in
the notes.

When the import ends, a notification gives the rows imported and failed. A row
fails when its species is not in the catalogue. **Download information about
the failed rows** lists them with the reason. Rows with values that could not
be read are imported and flagged **Needs review**.

### The RAC/SPA Mediterranean baseline workbook

The 2023 baseline workbook (sheets PAN MEDITERRANEAN, WMED, CMED, ADRIA, EMED)
follows each validated list with held-out blocks. They are read as follows,
never as validated NIS:

| Block | Stored as |
|---|---|
| PAN: *Debatable (diverging expert opinions)*, likely alien polychaeta and foraminifera, records from one location | **Data Deficient**; the experts' comment goes into the notes |
| PAN: *Excluded from the regional list* (only on a vector, shells only) | **Questionable** |
| Sub-region: *Questionable records* | sub-region status **Questionable** |
| Sub-region: *Status unresolved*, polychaeta lists | sub-region status **Data Deficient** |
| Sub-region: *Cryptogenic* | sub-region status **Cryptogenic** |
| *Removed*, *To be removed*, *Shells only*, rows marked *REMOVE* or *NAT* | not imported |

A species on a validated list keeps that entry even when a held-out block also
names it. Each annex event's notes say which block it came from, with the
reference.
