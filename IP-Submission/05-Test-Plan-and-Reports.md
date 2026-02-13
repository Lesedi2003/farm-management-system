# Phase 5 – Test Plan, Reports List, and Final Checklist  
## Farm Management System (FMS)  
### Industry Project – 6 Month WIL

---

## 1. Test Plan

### Scope

- **Functional:** Add / Edit / Delete for animals, crops, tasks, inventory, expenses, vaccinations (admin and portal); dashboard counts and quick add; reports (vaccinations due, animals by species/status, expenses by category); login and role-based access.
- **UI:** Navigation, forms, validation messages, responsive behaviour on portal pages.
- **Security:** Nonces on forms, capability checks (e.g. `fmp_manage_farm`, `fmp_export_reports`), unauthenticated users cannot access portal data.

### Environment

- **Server:** XAMPP (Apache, MySQL).
- **Application:** WordPress 5.8+ with Farm Management plugin active.
- **Browser:** Any modern browser (Chrome, Firefox, Edge) for manual testing.

### Roles

- **Administrator / Farm manager:** Full access to wp-admin (Farm Management menu, all CPTs, Settings, Reports, CSV export, Demo create/delete).
- **Farmer / Farm staff:** Portal access (dashboard, add/edit own records via shortcodes); admin access only if granted `fmp_manage_farm` (optional).
- **Unauthenticated user:** Public home and contact only; portal shortcodes redirect to login or show “log in” message.

---

## 2. Test Cases

| ID | Scenario | Steps | Expected result | Pass/Fail |
|----|----------|--------|------------------|-----------|
| TC01 | Add animal (admin) | Log in as admin → Farm Management → Animals → Add New (or Quick Add) → fill tag, species, breed, sex, status, etc. → Publish | Animal appears in Animals list and on dashboard count | |
| TC02 | Edit animal (admin) | Open Animals list → click an animal → change e.g. weight/notes → Update | Changes saved; list shows updated data | |
| TC03 | Delete animal (admin) | Open animal → Move to Trash (or Delete permanently if allowed) | Animal removed from list and counts | |
| TC04 | Add animal (portal) | Log in as farmer → go to portal page with [fmp_animals] or dashboard → Add Animal → submit form | Animal created with post_author = current user; appears in portal list | |
| TC05 | Edit crop (portal) | Log in → portal Crops → open add-crop form with ?id=&lt;crop_id&gt; → change field → Save | Crop updated; only if user is author or has permission | |
| TC06 | Add task (admin) | Farm Management → Tasks → Add / Quick Add → set title, due date, priority, status → Save | Task appears in Tasks list and “Tasks due soon” on dashboard if due within threshold | |
| TC07 | Add expense (admin) | Farm Management → Expenses → Add → amount, category, date → Publish | Expense appears in list and in “Expenses this month” on dashboard and in Reports | |
| TC08 | Reports – Expenses by category | Log in as manager → Farm Management → Reports → select month/year → view “Monthly expenses summary” | Table shows categories and totals for selected month; CSV export works if user has fmp_export_reports | |
| TC09 | Reports – Vaccinations due | Farm Management → Reports → “Vaccinations due in next N days” | List shows vaccinations with next_due_date within N days (and overdue); export CSV works | |
| TC10 | Reports – Animals by species & status | Farm Management → Reports → “Animals by species & status” | Table shows counts by species and status (Alive/Sold/Dead); export CSV works | |
| TC11 | Unauthenticated portal access | Log out → open URL of page with [fmp_farm-dashboard] or [fmp_animals] | Redirect to login or message “You must be logged in”; no farm data visible | |
| TC12 | Dashboard counts | Log in as admin → Farm Management (dashboard) | Stat cards show counts for animals, tasks, inventory, expenses, vaccinations; “Tasks due soon” and “Vaccinations due/overdue” sections correct | |
| TC13 | Low-stock alert | Ensure an inventory item has quantity ≤ reorder level → open dashboard | Low stock count and list/link shown (e.g. “View low stock”) | |
| TC14 | Demo sample data | As admin → Settings → Create sample data | Sample animals, crops, tasks, inventory, expenses, vaccinations created; “Delete sample data” removes them | |
| TC15 | Portal Reports shortcode | Log in → page with [fmp_reports] | Vaccinations due, animals by species/status, monthly expenses summary visible; link to admin reports if user has capability | |

*Pass/Fail to be filled during execution. Repeat for different roles (e.g. farmer-only) where relevant.*

---

## 3. Reports List

| # | Report | Purpose | Inputs | Output | Where in the system |
|---|--------|---------|--------|--------|----------------------|
| 1 | **Expenses by category** | Show spending by category (feed, vet, fuel, labour, other) for a given month | Month, year (default: current month) | Table: category, total (ZAR). Optional CSV export | Admin: Farm Management → Reports. Portal: [fmp_reports] (current month only). Logic: `FMP_Reports::get_expenses_by_category_for_month()` |
| 2 | **Task completion summary** | Overview of tasks by status (open / in progress / done) | None (all tasks) | Dashboard shows total task count; “Tasks due soon” list. Full list and status per task: Farm Management → Tasks. A dedicated “summary by status” table can be added in Reports view | Admin: Dashboard (task count, tasks due soon); Tasks list (status column). Optional: extend Reports page with task status counts |
| 3 | **Vaccinations due (including overdue)** | List vaccinations due within N days or already overdue | N = “Due soon” days (Settings) | Table: animal tag/name, vaccine, next due date, status, location. CSV export | Admin: Farm Management → Reports. Portal: [fmp_reports]. Logic: `FMP_Reports::get_vaccinations_due_including_overdue()` |
| 4 | **Animals by species & status** | Count animals by species and status (Alive / Sold / Dead) | None | Table: species, status, count. CSV export | Admin: Farm Management → Reports. Portal: [fmp_reports]. Logic: `FMP_Reports::get_animals_by_species_and_status()` |
| 5 | **Monthly expenses chart (optional)** | Visualize monthly expenses over time | Month/year or date range | Chart (e.g. bar or line). Not implemented in current MVP; can be added using same expense data and a simple JS chart library | — |

---

## 4. Final Deliverable Checklist (Template Equivalence)

| Template requirement | Deliverable |
|----------------------|-------------|
| Application deployment execution and manual | **06-Deployment-Manual.md** (and PDF): how to install XAMPP, WordPress, plugin; create pages and shortcodes; setup portal; run and log in. |
| Application archive with source code | **farm-management-1.0.0.zip**: zip of `farm-management/` plugin folder (all PHP, CSS, JS; no node_modules). Version in zip name. |
| Database backup and DDL script | **database-backup.sql**: MySQL dump of WordPress database (or at least wp_posts, wp_postmeta, wp_options). **Schema/DDL**: Phase 4 document lists post types and meta keys; optional separate SQL reference file. |
| Complete source code | Same as plugin archive; includes `farm-management.php`, `includes/`, `assets/`, and all plugin files. |

---

## 5. Suggested Submission Folder Structure

See **IP-Submission/README-Submission.md** (or 06-Deployment-Manual) for:

- `01-Proposal-Phase1.pdf`
- `02-Modelling-Phase2.pdf`
- `03-User-Interface-Phase3.pdf`
- `04-Database-Phase4.pdf`
- `05-Test-Plan-and-Reports.pdf` (this document)
- `06-Deployment-Manual.pdf`
- `farm-management-1.0.0.zip`
- `database-backup.sql`
- (optional) `schema-ddl-reference.sql` or schema document

Export each `.md` to PDF (e.g. Print to PDF, pandoc, or VS Code export) before submission.
