The MAMIAS catalogue is the list of Non-Indigenous Species recorded in the
Mediterranean, each one checked against WoRMS. Every species goes through the
same cycle: added (by hand or by import), checked against WoRMS, and kept up to
date when WoRMS changes its accepted name.

[TOC]

## The catalogue list

![The NIS Taxon list with its status tabs](/images/docs/taxa/01-list.png)

The list opens on **Checked & accepted**, the species ready to cite. The other
tabs are work queues:

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
**☰** icon at the end of each row holds every action on that species.

When WoRMS changes accepted names, a banner says how many species are affected.
**Review them** opens the *Name to update* tab.

![Banner announcing new accepted names in WoRMS](/images/docs/taxa/02-banner.png)

## Add or edit a species

1. Click **New NIS Taxon**.
2. Start typing the **Scientific Name** and pick the WoRMS suggestion, e.g.
   *Pterois miles (Bennett, 1828)*. Authority, Aphia ID, LSID and the full
   classification fill in.
3. Set the **Environment** and check the **EASIN ID** (**↻** fetches it again).
4. Click **Create**.

![Create NIS Taxon with a WoRMS suggestion](/images/docs/taxa/03-create-search.png)

If the name already exists, even in the trash, saving stops and offers
**Delete this duplicate** or **Open the existing record**.

A species in *No data from WORMS* shows three extra buttons on its edit page:

![Edit page actions for a species with no WoRMS data](/images/docs/taxa/04-no-data-actions.png)

- **Try WoRMS Match** runs a fuzzy search, useful for spelling variants.
- **Try GBIF Match** looks the name up in GBIF and fills the classification.
- **Enter Manually** unlocks every field; the status becomes *Checked & accepted (GBIF)*.

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

  ![Ask a scientist dialog](/images/docs/taxa/15-expert-review.png)

To move many at once, tick the rows and use the bulk action **Move to accepted
names**. Only *Safe to move* species are moved; the rest are skipped.

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
