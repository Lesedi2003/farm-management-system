# Phase 4 – Database and Integration  
## Farm Management System (FMS)  
### Industry Project – 6 Month WIL

---

## 1. Build the Database

The system uses the **WordPress database schema** without custom tables. All farm data is stored in:

- **wp_posts:** One row per animal, crop, task, inventory item, expense, or vaccination. Discriminator: `post_type`. Key columns: `ID`, `post_author`, `post_title`, `post_status`, `post_type`, `post_date`, `post_modified`.
- **wp_postmeta:** Key–value attributes for each post. Columns: `meta_id`, `post_id`, `meta_key`, `meta_value`. Each entity uses a set of `_fmp_*` meta keys (see below).
- **wp_options:** Plugin settings (e.g. due-soon days, demo mode) stored under option names like `fmp_settings`, `fmp_portal_pages`.

The “database” is thus **built** by WordPress core and the plugin’s registration of custom post types on `init`. No separate DDL is required to create tables; the schema is fixed by WordPress. For submission, a **schema reference** (list of post types and meta keys) and optional **CREATE TABLE** excerpts for wp_posts/wp_postmeta serve as the “DDL” documentation.

---

## 2. Manage Objects, Schema, and Integrity

### Post types (objects)

| Post type | Purpose |
|-----------|---------|
| `fmp_animal` | Livestock record |
| `fmp_crop` | Crop / field record |
| `fmp_task` | Task with due date and status |
| `fmp_inventory_item` | Inventory item with quantity and reorder level |
| `fmp_expense` | Expense with amount and category |
| `fmp_vaccination` | Vaccination record linked to animal |

### Meta keys (schema) per entity

**fmp_animal**  
- `_fmp_tag`  
- `_fmp_species`  
- `_fmp_breed`  
- `_fmp_sex`  
- `_fmp_date_of_birth`  
- `_fmp_acquired_date`  
- `_fmp_status` (e.g. alive, sold, dead)  
- `_fmp_weight`  
- `_fmp_notes`  
- Featured image (thumbnail) via WordPress attachment.

**fmp_crop**  
- `_fmp_crop_name`  
- `_fmp_crop_type` (e.g. veg, fruit, grain)  
- `_fmp_field_location`  
- `_fmp_planting_date`  
- `_fmp_expected_harvest_date`  
- `_fmp_crop_status` (e.g. planned, planted, growing, harvested)  
- `_fmp_crop_notes`  
- Featured image via thumbnail.

**fmp_task**  
- `_fmp_due_date`  
- `_fmp_priority` (e.g. low, medium, high)  
- `_fmp_status` (e.g. open, in-progress, done)  
- `_fmp_notes`

**fmp_inventory_item**  
- `_fmp_item_name`  
- `_fmp_category` (e.g. feed, medicine, equipment, other)  
- `_fmp_quantity`  
- `_fmp_unit` (e.g. kg, l, pcs)  
- `_fmp_reorder_level`  
- `_fmp_notes`  
- `_fmp_supplier` (admin)

**fmp_expense**  
- `_fmp_amount`  
- `_fmp_date`  
- `_fmp_category` (e.g. feed, vet, fuel, labour, other)  
- `_fmp_notes`

**fmp_vaccination**  
- `_fmp_animal_id` (post ID of fmp_animal)  
- `_fmp_vaccine_name`  
- `_fmp_date_given`  
- `_fmp_next_due_date`  
- `_fmp_notes`

### Integrity constraints

- **Referential:** Vaccination `_fmp_animal_id` references `wp_posts.ID` where `post_type = 'fmp_animal'`. Enforced in application logic (dropdown of user’s animals).
- **Author:** `post_author` set on insert; portal queries filter by `author = current_user_id()` so users see only their own data where applicable.
- **Status:** `post_status = 'publish'` for active records; trashed records use WordPress trash.
- **Meta:** No application-level constraint that meta_key must exist; missing keys return empty string. Application code uses default values where needed.

---

## 3. Normalization Process

- **wp_posts:** Each row is one entity (one animal, one crop, etc.). Columns are atomic; non-key attributes (e.g. post_title, post_date) depend on the primary key `ID`. The table is in 3NF for WordPress’s intended use.
- **wp_postmeta:** EAV (entity–attribute–value): (post_id, meta_key) can be considered a composite key; meta_value is a single value. Redundancy is minimal (key names repeated); 2NF/3NF satisfied for the key-value design.
- **Avoiding redundancy:** One post per animal/crop/task/etc.; attributes stored once in postmeta. No repeated groups; report data is derived by querying posts and meta (e.g. aggregate by category) rather than storing duplicated summaries.

---

## 4. Populate Data – Script

The plugin provides **sample data** via the **Demo** module:

- **Location:** `includes/class-fmp-demo.php`
- **How to run:** In WordPress admin, go to **Farm Management → Settings**. When “Demo mode” is enabled, use the button **Create sample data** (or the equivalent action that calls `admin_post_fmp_demo_create_sample`). This creates sample animals, crops, tasks, inventory items, expenses, and vaccinations for the current user, and marks them with `_fmp_seeded = 1` so they can be removed with **Delete sample data**.

**Alternative for submission:** A standalone **populate script** is provided in this submission folder: `scripts/README-populate.md`. It can be run once from the browser (when logged in as an administrator) or documented as “execute Demo create from Settings”. The script (or the Demo handler) performs the following in one transaction-like flow:

1. Insert rows into wp_posts (post_type, post_author, post_title, post_status).
2. Insert corresponding rows into wp_postmeta (post_id, meta_key, meta_value) for each attribute.

No separate SQL file is required; the Demo create from Settings is the populate script. See’s `scripts/README-populate.md` for instructions.

---

## 5. Transactions and Queries – Correlation with Functional Requirements and Use Cases

### Key operations that touch the database

| Operation | FR / Use case | Implementation | Tables / API used |
|-----------|----------------|-----------------|-------------------|
| Add animal | FR1, UC1 | `wp_insert_post` then multiple `update_post_meta` | wp_posts, wp_postmeta |
| Edit animal | FR1, UC3-style | `wp_update_post` (title) + `update_post_meta` | wp_posts, wp_postmeta |
| Add expense | FR5 | `wp_insert_post` then `update_post_meta` (amount, date, category, notes) | wp_posts, wp_postmeta |
| Dashboard counts | FR7 | `WP_Query` or `get_posts` with `post_type`, `post_status`, `author`; count `found_posts` | wp_posts |
| Overdue vaccinations | FR8 | `WP_Query` on `fmp_vaccination` with `meta_query` on `_fmp_next_due_date` &lt; today | wp_posts, wp_postmeta |
| Low-stock items | FR8 | `get_posts` (fmp_inventory_item) then filter in PHP where quantity &lt;= reorder_level (meta) | wp_posts, wp_postmeta |
| Expenses by category | FR9 | `get_posts` (fmp_expense), group by `_fmp_category`, sum `_fmp_amount` (e.g. in `FMP_Reports::get_expenses_by_category`) | wp_posts, wp_postmeta |
| Task completion summary | FR9 | `get_posts` (fmp_task), group by `_fmp_status` | wp_posts, wp_postmeta |

WordPress does not expose explicit BEGIN/COMMIT for these; each `wp_insert_post` / `update_post_meta` is auto-committed. The “transaction” for “add animal” is the logical sequence: one insert + N meta updates; if a later step fails, the application can redirect with an error (no partial-rollback in this implementation).

---

## DDL Reference (optional)

WordPress core creates `wp_posts` and `wp_postmeta`. For reference, the structure is:

- **wp_posts:** ID (PK), post_author, post_date, post_content, post_title, post_status, post_type, post_name, post_modified, post_parent, guid, menu_order, comment_count, etc.
- **wp_postmeta:** meta_id (PK), post_id (FK to wp_posts.ID), meta_key, meta_value (longtext).

The plugin does not create additional tables; it only inserts/updates rows in these tables. A full MySQL dump of the WordPress database (or of wp_posts, wp_postmeta, wp_options) serves as the “database backup” for the final deliverable.

---

*End of Phase 4 Database. Use the Demo “Create sample data” or the provided populate script to populate the database for demonstration.*
