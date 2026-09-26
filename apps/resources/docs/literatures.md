Every bibliographic reference in MAMIAS goes through review before it can be
cited. This guide covers the whole cycle, from adding a reference to merging a
duplicate.

[TOC]

## The Literatures list

![The Literatures list on the Pending Review tab](/images/docs/literatures/01-pending-list.png)


- **Tabs.** *Pending Review* is the review queue, and its badge shows how many
  references are waiting. While anything is pending, reviewers land on this tab.
  *All*, *Approved* and *Rejected* hold the rest.
- **Used in.** How many records cite the reference. Hover the badge to see
  introduction events, species suggestions and original descriptions separately.
- **DOI.** Opens the publication. A red DOI marked *Retracted* means Crossref
  lists a retraction notice for it. *Suggested: …* means a DOI was found for a
  reference that had none (see [DOI suggestions and retractions](#guide-doi-suggestions-and-retractions)).
- **Discussion.** How many messages the reference's discussion holds. Click
  the badge to open it. It turns amber while the submitter is waiting for a
  reply (see [Discuss with the submitter](#guide-discuss-with-the-submitter)).
- **Row menu.** The **⋮** button at the end of each row holds every action on
  that reference.

![The row menu of a pending reference](/images/docs/literatures/02-row-menu.png)

- **Filters.** *Type*, *Year*, *Submitted by*, *Comments awaiting a reply*,
  *Retracted*, *DOI suggestion to review* and *Not cited by any record*.

## Add a reference

1. Click **New Literature** at the top of the list.
2. Paste the **DOI**. A link, `doi:10.…` or the bare DOI all work. Leave the
   field and the other fields fill in from Crossref. Use the **↻** button to
   fetch again.
3. Read the note under the DOI and the full reference:
   - *Already in MAMIAS as mamias000123* means the reference exists. Don't
     create it again; use the existing one.
   - *Looks like mamias000123* means a very similar reference exists. Click
     **Open** to compare before saving.

![A DOI that is already in MAMIAS, flagged under the field](/images/docs/literatures/08-duplicate-doi.png)

4. Check **Short reference** (e.g. *Zenetos et al., 2010*), **Resource type**
   and **Year**, and attach the **PDF** if you have one.
5. Click **Create**. The reference gets its code (*mamias000124*) and joins the
   *Pending Review* queue like any other submission.

No DOI? Leave the field empty and fill the reference in by hand.

## Review pending references

1. Open the **Pending Review** tab.
2. Open the row menu and choose **View** to read the full reference, or
   **Edit** to open the reference page.
3. Decide:
   - **Approve**: the comment is optional and is sent to the submitter.
   - **Reject**: a reason is required, because it is what the submitter
     receives.

![The Reject dialog: the reason is required](/images/docs/literatures/04-reject.png)

4. To go through the queue quickly, use **Approve & next** or
   **Reject & next** in the dialog. The dialog then opens straight on the next
   pending reference, oldest first, and closes when the queue is empty.

![The Approve dialog with Approve & next](/images/docs/literatures/03-approve.png)


The submitter is notified by email and in the app either way. A decision can
be reversed later with **Re-approve** or **Re-reject**.

## Fix a reference instead of rejecting it

**Rejection is final.** Contributors cannot resubmit a rejected reference. So
when the only problem is the metadata (wrong year, missing DOI, a typo in the
authors):

1. Choose **Edit** in the row menu.
2. Correct the fields and save.
3. Click **Approve**, and say what you changed in the comment, e.g. *Year
   corrected to 2019*.

Reserve **Reject** for references that are out of scope (not about
non-indigenous species or the Mediterranean) or not a usable source.

## Review several references at once

1. Tick the checkbox of each reference, or the header checkbox for the whole
   page.
2. Open **Bulk actions** and choose **Approve selected** or **Reject selected**.

![Two references selected, with the Bulk actions menu open](/images/docs/literatures/05-bulk.png)

3. Write one comment for all the submitters. It is required when rejecting.

References already in that state are skipped, and each submitter is notified
individually.

## Merge a duplicate

When the same publication exists twice, keep one and merge the other into it.
Everything that cited the duplicate moves over.

1. Open the duplicate's row menu, or its edit page, and choose **Merge into…**.
2. Pick the reference to keep. Near-duplicates are listed first; type to search
   by code, short reference or DOI.

![The Merge dialog, with the near-duplicate listed first](/images/docs/literatures/06-merge.png)

3. Click **Merge**.

Introduction events, species suggestions and original descriptions now cite the
reference you kept, and the duplicate is deleted. The merge is recorded in the
kept reference's activity log. **It cannot be undone.**

## Delete a reference

Only references that no record cites can be deleted. Deleting a cited reference
would also delete its introduction events.

- On the edit page, **Delete** only appears when *Used in* is 0. Otherwise use
  **Merge into…**.
- A bulk **Delete** removes the uncited references among the selection and
  lists the codes it kept.

## DOI suggestions and retractions

Every week, the `literature:enrich` job checks the references against Crossref:

- **Retracted** references are flagged in red. Filter them with *Retracted*
  and decide whether they should still be cited.
- For a reference without a DOI it may propose one (*Suggested: …* under the
  DOI). Open the suggestion to check it's the same publication, then choose
  **Accept DOI** or **Dismiss DOI** in the row menu. A dismissed suggestion is
  not proposed again.

![A suggested DOI in the DOI column, with Accept DOI and Dismiss DOI in the row menu](/images/docs/literatures/07-doi-suggestion.png)


## Discuss with the submitter

Click the **Discussion** badge in the list, or **Discussion** on the edit page,
to exchange messages about a reference. Once the reference is reviewed, the
submitter's messages go to the reviewer who decided it.

When a submitter writes and no reviewer has answered yet, the badge turns amber
and a banner at the top of every panel page lists those references. Each link
opens the reference's edit page. Replying clears it.

![The banner listing references with comments awaiting a reply](/images/docs/literatures/13-comments-alert.png)

## Cite and export

- **Cite (BibTeX)** in the row menu shows a BibTeX entry. Click it to copy.

![The BibTeX entry, one field per line](/images/docs/literatures/09-bibtex.png)

- **Export** above the table downloads the current list, with your filters,
  search and sort, as CSV, XLSX or PDF.

## What contributors see

Contributors submit and follow their references on the public site, under
**My Bibliographic References**:

- **Add New Reference** asks for the DOI first, then shows the details to check
  or complete. An existing DOI is flagged before they submit.

![The public submission form, DOI step](/images/docs/literatures/11-public-wizard.png)

- The table shows each reference's code, type, year and status. Clicking a
  reviewed status shows your comment, signed with your role, not your name.

![The reason a contributor sees, signed with the reviewer's role](/images/docs/literatures/12-public-reason.png)

- The **Discussion** badge counts the messages on each reference, and turns
  amber when you have replied since their last message.
- Banners list their rejected references, and the references with a reply
  from you waiting for them.

![My Bibliographic References, with its two banners](/images/docs/literatures/10-public-list.png)

