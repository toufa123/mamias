The MAMIAS catalogue is the list of Non-Indigenous Species recorded in the
Mediterranean, each one checked against WoRMS. Every species goes through the
same cycle: added (by hand or by import), checked against WoRMS, and kept up to
date when WoRMS changes its accepted name.

[TOC]

## The catalogue list

![The NIS Taxon list with its status tabs](/images/docs/taxa/01-list.png)

The list opens on **All**. **Checked & accepted** holds the species ready to
cite; the other tabs are work queues:

- **Checked & not accepted.** WoRMS knows the name but accepts another one.
- **Not checked Yet.** WoRMS check still pending. New imports land here.
- **No data from WORMS.** WoRMS returned nothing for the name.
- **Checked & accepted (GBIF).** Not in WoRMS, confirmed through GBIF or entered by hand.
- **Duplicates.** The same scientific name appears more than once.
- **Name to update.** WoRMS now accepts a different name (see [Review names WoRMS has changed](#guide-review-names-worms-has-changed)).
- **First records to reconcile.** More than one first-record event, usually after a merge.
- **Trashed.** Deleted species, which can be restored.

The funnel icon filters by scientific name, kingdom, phylum, rank and
environment. **Export** downloads the current tab, filters included. The
**☰** icon at the end of each row holds every action on that species; clicking
the row itself opens the species (see [View a species](#guide-view-a-species)).

When WoRMS changes accepted names, a banner says how many species are affected.
**Review them** opens the *Name to update* tab.

![Banner announcing new accepted names in WoRMS](/images/docs/taxa/02-banner.png)

## Save a view of the list

Set the list up the way you use it (filters, search, sort, visible columns),
then open **Saved views** in the table toolbar, type a name and click **Save
current view**. Clicking the view later brings that set-up back. Your views
are yours alone; other users never see them.

- Changing the list while a view is open does not change the view. A dot on
  **Saved views** marks unsaved changes; **Update this view** writes them in.
- Drag a view to reorder it, use the cog to rename it and the bin to delete it.
- A view does not remember the tab. Open the tab first (e.g. *Name to update*),
  then the view.

## View a species

Click a row, or **View** in its **☰** menu.

![A species' view window, on its Synonyms tab](/images/docs/taxa/08-view.png)

- The top line is the classification, kingdom to genus.
- **Aphia ID** opens the species in WoRMS and **EASIN ID** its EASIN factsheet;
  **Copy LSID** copies the full LSID. Then come the rank, environment, WoRMS and
  catalogue statuses, and when WoRMS was last checked.
- An orange warning appears only when WoRMS accepts another name, with the
  reason (see [Review names WoRMS has changed](#guide-review-names-worms-has-changed)).

Three tabs, each with its count:

- **Synonyms.** Every name WoRMS lists for the species, with authority, status
  and reason; click a name to open it in WoRMS. Long lists scroll inside the tab.
- **References.** The approved literature: original description, first records
  and supporting references. **Download BibTeX** exports the list.
- **Notes & audit.** Notes, and who created and last changed the record.

The window keeps the same size whichever tab is open.

## Add or edit a species

1. Click **New NIS Taxon**.
2. Start typing the **Scientific Name** and pick the WoRMS suggestion, e.g.
   *Pterois miles (Bennett, 1828)*. Authority, Aphia ID, LSID and the full
   classification fill in.
3. Set the **Environment** and check the **EASIN ID** (**↻** fetches it again, **↗** opens its EASIN factsheet).
4. Click **Create**.

![Create NIS Taxon with a WoRMS suggestion](/images/docs/taxa/03-create-search.png)

If the name already exists, even in the trash, saving stops and offers
**Delete this duplicate** or **Open the existing record**.

A species in *No data from WORMS* shows three extra buttons on its edit page:

![Edit page actions for a species with no WoRMS data](/images/docs/taxa/04-no-data-actions.png)

- **Try WoRMS Match** runs a fuzzy search, useful for spelling variants.
- **Try GBIF Match** looks the name up in GBIF and fills the classification.
- **Enter Manually** unlocks every field; the status becomes *Checked & accepted (GBIF)*.

## When someone else is editing

Opening the edit page of a species locks it for everyone else while you have
it open. Anyone who opens it meanwhile sees a banner naming who is editing,
and every field greyed out: they can read but not save. The lock lifts as soon
as you save or leave the page, and on its own about 10 minutes after the page
was closed without leaving it properly (a crashed browser, a dropped
connection).

A super_admin can click **Unlock page** in the banner to take over a record
someone left open. Whatever that person had not saved is lost, so check with
them first. **System → Resource Lock Manager** lists every current lock.

## Review names WoRMS has changed

![Name to update tab with confidence scores](/images/docs/taxa/10-name-to-update.png)

Each species shows the new accepted name and a confidence score:

- **Safe to move** (90% and up): a superseded combination or misspelling.
- **Check the source** (70–89%): likely right, read the evidence first.
- **Expert review** (below 70%): usually a subjective synonym.

![Row menu with the review actions](/images/docs/taxa/11-row-menu.png)

- **Move to accepted name.** Introduction events follow and keep the name they
  were recorded under. Below *Safe to move*, a note on what you checked is required.

  ![Move dialog requiring a note](/images/docs/taxa/12-move-dialog.png)

- **Keep current name.** This WoRMS name is not proposed again. A reason is required.

  ![Keep current name dialog](/images/docs/taxa/14-keep.png)

- **Send for expert review.** Pick a scientist and, optionally, ask a question.
  The question is posted in the species' [Discussion](#guide-discuss-a-species).

  ![Ask a scientist dialog](/images/docs/taxa/15-expert-review.png)

- **Undo name move.** Reverses the latest move: the species gets its previous
  name and classification back, or, after a merge, the records return to the
  species they came from. Shown only while there is a move to undo.

To move many at once, tick the rows and use the bulk action **Move to accepted
names**. Only *Safe to move* species are moved; the rest are skipped.

## Discuss a species

**Discussion** in the **☰** menu (or at the top of the edit page) holds the
conversation about a species, with who follows it in the same window.

![Discussion window: participants above the messages](/images/docs/taxa/16-discussion.png)

1. **Participants** at the top are notified of every new message. Add or remove
   people there; the change is saved at once. Anyone who writes joins automatically.
2. Click in the box below, write, then click **Comment** to post.
3. The messages follow, newest first.

## Import species from a file

**Import NIS Taxa** adds many species at once. Species already in the catalogue
are skipped, never overwritten, so repeating an import is safe.

1. Prepare a CSV, TXT, Excel (`.xlsx`, `.xls`, `.xlsm`) or `.ods` file. Only the
   first sheet is read. The first row is the header; call the column
   `Scientific Name`. One name per row, without authority. Other columns are
   ignored. The window offers example CSV and XLSX files.
2. Click **Import NIS Taxa** and drop the file in the box.

   ![Import NIS Taxa window](/images/docs/taxa/05-import-modal.png)

3. Under **Columns**, check that *Scientific Name* points at the right column.

   ![Column mapping after upload](/images/docs/taxa/06-import-mapping.png)

4. Click **Import**. It runs in the background, 100 rows at a time.

Each row is skipped if its name is already in the catalogue (trash included),
or if WoRMS resolves it to an accepted name or Aphia ID already catalogued.

![Import complete summary](/images/docs/taxa/07-import-complete.png)

- **Rows imported:** added with status *Not checked Yet*.
- **Already in database:** skipped as duplicates.
- **Rows failed:** rejected, e.g. an empty or over-long name.

**Download not imported species** (Excel or CSV) lists every skipped or failed
row with the reason. Fix it and import it again.

Then open **Not checked Yet**, tick the new species and run the bulk action
**Fetch from WoRMS**.

First-record baselines are imported separately with **Import Intro Events** on
the Introduction Events list. Import the species here first.
