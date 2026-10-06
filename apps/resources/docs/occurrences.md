An occurrence is one sighting of a non-indigenous species: where it was seen
(a point on the map), when, and what was found there (depth, density, extent,
habitats, photos). Contributors report them from the public **My Species
Reports** page; each report waits here until a reviewer approves or rejects it.
Staff can also add occurrences directly. Only species that already have an
[introduction event](/mamias/intro-event-records) can be reported.

[TOC]

## The occurrences list

![The occurrences map above the list](/images/docs/occurrences/01-list.png)

**Occurrences** sits in the **MAMIAS database** menu, right after Introduction
Events. Its badge counts the reports waiting for review.

The page opens with the **occurrences map** above the list. Click its title bar
to fold it away and give the list the room; it stays folded, or open, the next
time you come back. The map shows one pin for every row the list currently
shows, so filters and search change the pins too. Pins are coloured by status,
here and on every other occurrence map (the small one in each row, and the one
in a report's details):

- **Green.** Approved.
- **Gray.** Pending review.
- **Red.** Rejected.

Click a pin to open that occurrence's details, where you can approve or reject
it. The map has a full-screen button and goes down to about 75 m per pixel.

Each row of the list shows the species, the **Status** badge, the depth, the
abundance (density), when it was **Observed**, who **Reported By** and when it
was **Submitted**. The **☰** icon at the end of each row holds **View**,
**Approve**, **Reject** and **Delete**. Newest submissions come first. The
columns icon next to the filters shows or hides columns, among them a small map
of the **Location** and the **Habitats** (hidden at first).

Hover a status badge to read the reviewer's note:

![A resubmitted report's note, on hover](/images/docs/occurrences/02-resubmitted.png)


- on a **Rejected** occurrence, the reason it was rejected;
- on a **Pending Review** occurrence that carries a note, *Resubmitted*: the
  contributor revised a rejected report and sent it back. The note is the
  earlier rejection reason, so you can check it was addressed.

While any report is waiting, a **Pending Occurrences** notification stays in
the top corner of every panel page. Its **Review them now** link opens this list
filtered to them. Close it with **×**: it comes back when the number of
waiting reports changes.

The funnel icon opens the filters:

![Filters: status and observation dates](/images/docs/occurrences/05-filters.png)

- **Status.** Pending Review, Approved or Rejected.
- **Observed from / Observed until.** A range of observation dates. Either end
  can be left empty.

## Review a report

Open the occurrence with **View** in the row's **☰** menu (or click its pin on
the map).

![A pending report's details window](/images/docs/occurrences/03-view.png)

The details window has four parts:

- **Species Information.** Scientific name and authority, the species' first
  introduction year, when it was observed, depth, abundance (density), extent
  and whether it was estimated or measured, habitats, the coordinates (click to
  copy them) and the notes.
- **Location.** The observation point on the map.
- **Photos.** The contributor's pictures. The part is left out when there are
  none.
- **Review.** Status, who reported it, when it was submitted and, once
  approved or rejected, when it was reviewed, with the moderation notes.

Check the point is in the sea and plausible for the species, the date makes
sense, and the photos (if any) show the species named. Then use **Approve** or
**Reject** at the bottom of the window, or the same entries in the row's **☰**
menu.

- **Approve** takes an optional note.
- **Reject** needs a reason. Write it for the contributor: it is shown to them
  on their report, and they can revise and resubmit.

![The reject window](/images/docs/occurrences/04-reject.png)

Either way the contributor gets a notification, the map recolours the pin, and
the decision is written to the activity log. A decision can be changed later:
an approved occurrence can still be rejected, and a rejected one approved.

## Add an occurrence

Click **New occurrence** above the list. Admins and scientists can do this.

An occurrence you add is **approved straight away**: it does not go to the
review queue, and you are recorded as the one who reported it.

![The new occurrence window](/images/docs/occurrences/06-create.png)

The form:

- **Country.** Optional. Narrows the species search to species recorded in that
  country; it is not saved with the occurrence.
- **Species** (required). Type at least three letters. Only species with an
  introduction event are listed. If the species is missing, create its
  introduction event first.
- **Depth (m).** 0 to 11 000.
- **Abundance (density).** The ACFOR scale: how tightly packed the species is
  where it was found. The choices depend on the species' kingdom.
- **Habitats.** Pick one or more.
- **Date & Time of Observation** (required). Defaults to now.
- **Extent (area or count)**, with its **Extent Unit** (Coverage (m²) or
  Number of individuals) and **Estimated or Measured**. Extent is how much there is in
  total, unlike density. The unit and method are required once an extent is
  entered.
- **Location** tab. Click the map to place the observation point; click again
  to move it.
- **Photos** tab. JPEG, PNG or WebP, up to 5 MB each, several at once.
- **Notes** tab. Anything else worth keeping.

Click **Create** to save, or **Create & create another** to keep the window
open for the next one.

## Delete occurrences

**Delete** in the row's **☰** menu removes that occurrence; it only appears on occurrences of
species whose introduction event you are allowed to edit. To remove several,
tick their rows and use **Delete** in the bulk actions. Deleting cannot be
undone; to take a report out of the published data while keeping it, reject it
instead.

Occurrences cannot be edited here. If a report needs correcting, reject it with
a note saying what to change: the contributor revises it and it comes back to
the queue.
