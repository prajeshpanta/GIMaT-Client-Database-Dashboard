# GIMaT Client Database Dashboard (WordPress Plugin)

A free, dependency-free WordPress plugin that replicates the core features of
the standalone Next.js/Supabase training-records dashboard, entirely inside
wp-admin.

## Install

1. Zip the `gimat-client-dashboard` folder (or use the zip as-is).
2. WordPress Admin → Plugins → Add New → Upload Plugin → choose the zip → Install → Activate.
3. On activation the plugin creates two tables (`{prefix}gimat_events`,
   `{prefix}gimat_records`), grants a new `manage_gimat_dashboard`
   capability to the Administrator and Editor roles, and registers a new,
   restricted **Client** role (`gimat_client`) that can only see this
   dashboard.

A new **GIMaT Dashboard** menu appears in wp-admin with Dashboard, Events,
Add Record and All Records pages.

## What's new in 1.1.1

- **Total column fix** — the "Total" badge on All Records and the "Total
  Participants" dashboard stat now add up all five headcount fields (Male,
  Female, Dalit, Janajati, Other), not just Male + Female. Both use one
  shared `Gimat_Records::row_total()` helper so the two numbers can never
  drift out of sync again.

## What's new in 1.1.0

- **Sidebar order** — "Add Record" now sits above the records list, and the
  "Records" menu item is labeled **All Records**.
- **Edit events** — the Events table has an Edit (pencil) button next to
  Delete; it opens an inline rename form (with duplicate-name and length
  validation, same as Add Event) instead of only allowing delete.
- **Who's logged in, with logout** — the header on every dashboard page now
  shows the current user's name and role (**Admin** for administrators,
  **Client** for the new restricted role, **Editor** otherwise) plus a
  one-click logout button.
- **A real "Client" role** — assign the new **Client** role to a training
  coordinator and they will see *only* the GIMaT Dashboard menu; every other
  wp-admin screen (Posts, Plugins, Users, Settings, etc.) is hidden for that
  role. Administrators and Editors are unaffected. This is what keeps the
  dashboard "for other users are not able to access" — access is gated both
  by the `manage_gimat_dashboard` capability (page level) and, for Client
  accounts, by hiding the rest of wp-admin (menu level).
- **CSV import hardening** — uploads are now checked for file extension
  (`.csv`), a 5 MB size cap, and a real upload-error code before the file is
  parsed.
- **CSV export hardening** — cells that start with `=`, `+`, `-`, `@`, or a
  tab/carriage-return are prefixed so spreadsheet apps never execute them as
  a formula (CSV/"formula injection" protection).
- **Clean uninstall** — deleting the plugin (not just deactivating it)
  removes the capability, the Client role, and plugin options. Data tables
  are kept by default so a client's training history is never destroyed by
  accident; define `GIMAT_DASH_DELETE_DATA_ON_UNINSTALL` as `true` in
  `wp-config.php` first if you also want the tables dropped.

## Feature parity with the original app

| Original (Next.js/Supabase) | This plugin |
|---|---|
| `events` table, CRUD | Custom table + admin CRUD, same cascade-delete of related records |
| `training_records` table, CRUD | Custom table + admin CRUD (event, sub-event, org, duration, dates, location, facilitator, M/F counts, Dalit/Janajati/Other counts, JSON custom fields) |
| Filters (event, date range, facilitator) + pagination | Same filters, `GET`-based, server-side pagination via `paginate_links()` |
| Dynamic custom fields on each record | Same, stored as JSON, editable as label/value rows |
| Excel/CSV import with preview + row-level errors/warnings | **CSV** import (upload → immediate import); rows with no matching event or invalid date are skipped and listed; other blank fields import as-is. *Note: native `.xlsx` isn't parsed — ask users to save/export their sheet as CSV first, or a PHP library like PhpSpreadsheet could be added later via Composer for direct `.xlsx` support.* |
| CSV export of filtered records | Same, including dynamic custom-field columns |
| Dashboard stats (events, trainings, participants, facilitators) | Same four stat cards + recent records list |

## Security notes

- **Database access**: every query goes through `$wpdb`, and every value
  that isn't a hard-coded, whitelisted string is passed through
  `$wpdb->prepare()` with `%d`/`%s` placeholders — nothing from `$_GET`,
  `$_POST`, or a CSV upload is ever concatenated directly into SQL. Table
  names always come from `Gimat_DB::events_table()` / `records_table()`
  (never from user input), and the one place a column name is interpolated
  (`Gimat_Records::distinct_values()`) is restricted to a fixed whitelist
  of two column names.
- Every state-changing action goes through `admin-post.php` with a nonce
  (`check_admin_referer`) and a capability check
  (`current_user_can( 'manage_gimat_dashboard' )`) — this now also covers
  the new "update event" action.
- Output is escaped with `esc_html`/`esc_attr`/`esc_url`/`esc_js` throughout.
- Delete actions require a JS confirm dialog; deleting an Event cascades to
  its Records, same as the original app — make sure whoever gets this
  capability understands that.
- **Access control** is layered: the capability check gates every page and
  every handler; the Client role additionally has the rest of wp-admin
  hidden from it (see "What's new" above), so a Client account genuinely
  cannot reach anything beyond this dashboard and their own profile/logout.
- **CSV import**: validated for upload-error code, `.csv` extension, and a
  5 MB size cap before parsing; unmatched events/dates are skipped and
  reported, never silently guessed.
- **CSV export**: values are neutralized against formula/CSV injection
  (`=`, `+`, `-`, `@`, tab, or CR as the first character get a leading `'`)
  so a poisoned facilitator name or custom field can't execute a formula
  when the export is opened in Excel/Sheets.
- **Uninstall** cleans up the capability, the Client role, and options
  (`uninstall.php`), and never touches the data tables unless you opt in.

## Extending

- To support real `.xlsx` uploads, add [PhpSpreadsheet](https://phpspreadsheet.readthedocs.io/)
  via Composer and swap the CSV parser in `includes/class-gimat-import-export.php`.
- To expose this data outside wp-admin (e.g. a front-end reporting page or a
  REST endpoint for another app), add a `rest_api_init` handler that wraps
  `Gimat_Records::query()` / `Gimat_Records::stats()`.
- Column set is fixed by design (matching the original schema). To make
  fields fully configurable per-client, you'd move from named columns to an
  EAV-style `record_meta` table.
