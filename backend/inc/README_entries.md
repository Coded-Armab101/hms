Summary
-------
This folder contains a migration and a helper to introduce a DRY `entries` model used for prescriptions, lab results, notes, pharmacy actions, nursing records, etc., plus simple billing tables.

Files
-----
- `backend/sql/001_create_entries_and_billing.sql` - SQL migration to create `entries`, `bills`, and `bill_items`.
- `backend/inc/entry_helper.php` - PHP helper with functions: `create_entry`, `update_entry`, `get_entries_by_entity`, `get_entry`, `delete_entry`, `can_edit_entry`.

How to use
----------
1. Back up your DB.
2. Run the SQL migration in your `hmisphp` database (phpMyAdmin or mysql CLI).
3. Include the helper from pages that already have `$mysqli` set up and a valid session. Example:

   ```php
   include __DIR__ . '/../../assets/inc/config.php'; // already in your app
   include __DIR__ . '/../inc/entry_helper.php';

   // create an entry
   $author_id = $_SESSION['ad_id'];
   $author_name = $_SESSION['ad_name'] ?? null;
   $author_role = $_SESSION['role'] ?? 'doctor';
   $entry_id = create_entry($mysqli, 'prescription', $presc_id, 'Rx for Amoxicillin', $content, $author_id, $author_name, $author_role);
   ```

Notes & Next Steps
------------------
- This helper includes simple ownership checks; integrate your role-based access control where needed.
- Next actions: refactor doctor prescription pages, lab upload pages, pharmacy pages to use `create_entry()` and `get_entries_by_entity()` and then wire `bills` creation from selected entries.
- I can now refactor a specific module (pick one) to demonstrate the pattern. Request which module to start with.