# Phase 1 – Proposal Document  
## Farm Management System (FMS)  
### Industry Project – 6 Month WIL

---

## 1. Name of the Project

**Farm Management System (FMS)** – also referred to as **Farm Management WordPress Portal**. A client–server, three-tier application for daily farm operations, built as a WordPress plugin with a front-end portal for farmers.

---

## 2. Domain Analysis

### General field of business
Agribusiness and farm operations management: livestock, crops, tasks, expenses, inventory, and animal health (vaccinations). The system targets small to medium farms that need a single place to record and report on daily operations.

### Terminology / glossary
- **Livestock:** Animals kept on the farm (cattle, sheep, etc.).
- **CPT (Custom Post Type):** WordPress entity type used to represent animals, crops, tasks, etc.
- **Portal:** Front-end web interface where farmers add and edit records without using WordPress admin.
- **Vaccination due soon / overdue:** Status derived from next-due date for health compliance.
- **Reorder level:** Inventory threshold below which the system alerts for restocking.
- **WIL:** Work Integrated Learning (the 6-month industry project period).

### Business environment
- **Tasks and procedures:** Recording animals (tag, species, breed, sex, DOB, status, weight); crops (type, location, planting/harvest dates, status); tasks (title, due date, priority, status); expenses (amount, category, date); inventory (item, category, quantity, unit, reorder level); vaccinations (animal, vaccine, date given, next due).
- **Customers and users:** Farm owners, farm managers, and farm staff (operators).
- **Competing software:** Generic farm record-keeping apps, spreadsheets, paper records. FMS differentiates by being integrated (one system), offering a dedicated farmer portal (no wp-admin for daily use), and running on standard WordPress + PHP/MySQL.

### Similarities to other domains
Similar to asset management (animals/crops as assets), inventory management (stock and reorder), and project/task management (tasks with due dates and status).

---

## 3. Define the Problem

**Problem:** Many small to medium farms have no single system to manage livestock, crops, tasks, expenses, inventory, and health records. Data is scattered across paper, spreadsheets, or multiple tools, leading to poor visibility and missed vaccinations or tasks.

**Opportunity:** A single, easy-to-use system that provides a central dashboard, a farmer-friendly portal for daily data entry, and basic reports will improve productivity, compliance (e.g. vaccination due dates), and decision-making.

---

## 4. Define the Scope (IRBM)

### Assess – Current situation
Farms often use manual records or spreadsheets; no unified dashboard, no reminders for vaccinations or low stock, and no role-based access (e.g. farmers vs managers).

### Think – Causes and who is involved
Lack of affordable, integrated software; farmers and managers need different views (daily entry vs reports/settings). Involved: farm owners, managers, staff, and (indirectly) vets for vaccination data.

### Envision – What we will achieve
A web-based Farm Management System with: (1) WordPress admin for managers (full CRUD, settings, reports); (2) a front-end portal for farmers (dashboard, quick add, add/edit forms); (3) reports (expenses by category, task completion, vaccinations due); (4) authentication and data isolation by user.

### Plan – How, with whom, when, with what resources
- **How:** Build as a WordPress plugin (PHP) with custom post types and meta; front-end shortcodes for the portal; business logic in plugin classes; data in MySQL via WordPress.
- **With whom:** Academic mentor (50%) and industry/academic assessor; optionally one pilot farm user for feedback.
- **When:** 6-month WIL; Phase 1 (proposal) by month 3; Phase 2–4 (modelling, UI, database) months 3–5; Phase 5 (final implementation and deliverable) by month 6.
- **Resources:** XAMPP (Apache, PHP, MySQL), WordPress 5.8+, PHP 7.4+, code editor, diagramming tool (e.g. Draw.io).

### Inputs, Activities, Outputs, Outcomes, Impact
- **Inputs:** Animal/crop/task/expense/inventory/vaccination data entered by users; login credentials.
- **Activities:** Add, edit, delete records; view dashboard and lists; run reports; manage settings; use portal or admin.
- **Outputs:** Stored records in database; dashboard counts and widgets; report tables (and optional chart); CSV export (admin).
- **Outcomes:** Centralised, up-to-date records; visibility of overdue vaccinations and low stock; clearer expense and task tracking.
- **Impact:** Improved productivity and compliance on the farm; better basis for decisions.

---

## 5. Vision and Objectives (SMART)

### Vision
*“A single system for daily farm operations and reporting, with a farmer-friendly portal and clear separation between presentation, business logic, and data.”*

### Objectives
1. **By month 6,** the system shall allow farm managers to add, edit, and delete animals, crops, tasks, inventory, expenses, and vaccinations from the WordPress admin. (Specific, measurable, achievable, relevant, time-bound.)
2. **By month 6,** farmers shall be able to add and edit the same record types from a front-end portal (shortcode-based pages) without using wp-admin.
3. **By month 6,** the system shall provide a dashboard with counts, quick-add links, and widgets (e.g. overdue vaccinations, tasks due soon, low stock).
4. **By month 6,** the system shall generate at least two reports: expenses by category and task completion summary; optional: monthly expenses chart.
5. **By month 6,** access shall be controlled by login; portal data shall be filtered by current user (post_author) where applicable.
6. **By month 6,** the application shall be deployable on XAMPP and documented (deployment manual, plugin archive, database backup).

---

## 6. Users of the System

| User role        | Description                                                                 | Access |
|------------------|-----------------------------------------------------------------------------|--------|
| **Farm Manager**| Full control: admin menu, all CRUD, settings, reports, export, delete.     | WordPress admin + portal (optional). |
| **Farm Staff**  | Daily records: add/edit; no delete, no export, no settings.               | Portal (and optionally admin list views). |
| **Administrator** (WordPress) | Site admin; can assign Farm Manager / Farm Staff roles.        | WordPress admin. |

Roles are implemented via WordPress roles and FMP capabilities (e.g. `fmp_manage_farm`, `fmp_export_reports`, `fmp_manage_settings`, `fmp_delete_records`).

---

## 7. Mandatory Functions

The system supports **Add/Register**, **Delete/Remove**, and **Update** for all main entities:

| Entity       | Add/Register | Update | Delete/Remove | Where (Admin / Portal) |
|-------------|--------------|--------|----------------|--------------------------|
| Animals     | Yes          | Yes    | Yes (manager) | Admin + Portal (add/edit) |
| Crops       | Yes          | Yes    | Yes (manager) | Admin + Portal (add/edit) |
| Tasks       | Yes          | Yes    | Yes (manager) | Admin + Portal (add/edit) |
| Inventory   | Yes          | Yes    | Yes (manager) | Admin + Portal (add/edit) |
| Expenses    | Yes          | Yes    | Yes (manager) | Admin + Portal (add/edit) |
| Vaccinations| Yes          | Yes    | Yes (manager) | Admin + Portal (add/edit) |

---

## 8. Functional Requirements

- **FR1:** The system shall allow authorised users to add, edit, and delete animals (tag/ID, species, breed, sex, date of birth, acquired date, status, weight, notes, optional photo).
- **FR2:** The system shall allow authorised users to add, edit, and delete crops (name, type, field/location, planting date, expected/actual harvest date, status, notes, optional photo).
- **FR3:** The system shall allow authorised users to add, edit, and delete tasks (title, assigned/due date, priority, status, optional link to animal/crop).
- **FR4:** The system shall allow authorised users to add, edit, and delete inventory items (name, category, quantity, unit, reorder level, supplier, notes).
- **FR5:** The system shall allow authorised users to add, edit, and delete expenses (title, amount, category, date, optional link, optional receipt).
- **FR6:** The system shall allow authorised users to add, edit, and delete vaccination records (animal, vaccine name, date given, next due date, notes).
- **FR7:** The system shall display a dashboard with counts per entity and quick-add links; for portal users, data shall be restricted by current user (post_author) where applicable.
- **FR8:** The system shall display widgets for overdue vaccinations, vaccinations due soon, tasks due soon, and low-stock inventory.
- **FR9:** The system shall generate a report of expenses by category and a task completion summary; optionally a monthly expenses view/chart.
- **FR10:** The system shall support CSV export of selected reports (admin, for users with export capability).
- **FR11:** Inputs: form fields as per entity (text, number, date, select, file upload where applicable). Outputs: confirmation messages, lists, report tables, dashboard values.
- **FR12:** Timing: vaccination status (OK / due soon / overdue) and task due dates shall be computed and displayed according to configured or default day ranges.

---

## 9. Non-Functional Requirements

- **Authentication:** WordPress login and logout. Unauthenticated users opening portal pages shall be redirected to the login page or shown a login-required message. Implemented via login checks and redirects in portal and frontend shortcodes.
- **Availability:** The system runs on XAMPP (local development) and is deployable on any host that supports WordPress (PHP 7.4+, MySQL, Apache or Nginx).
- **Security:** Nonces and capability checks on forms; sanitisation and escaping of inputs and outputs; file upload validation for animal/crop photos.

---

## 10. Use Cases

### Use case 1: Farmer adds an animal from the portal
- **Actor:** Farmer (farm staff).
- **Precondition:** User is logged in; portal add-animal page is set up.
- **Flow:** 1) Farmer opens portal dashboard. 2) Clicks “Add Animal”. 3) Fills form (tag, species, breed, sex, DOB, status, weight, notes, optional photo). 4) Submits. 5) System validates, creates post and meta, redirects to dashboard with success message.
- **Postcondition:** New animal record exists; owned by current user (post_author).

### Use case 2: Manager views expenses by category report
- **Actor:** Farm Manager.
- **Precondition:** User is logged in and has report access.
- **Flow:** 1) Manager opens Reports (admin or front-end reports page). 2) Views “Expenses by category” section. 3) System queries expenses, groups by category, displays table. 4) Optionally exports CSV.
- **Postcondition:** None (read-only).

### Use case 3: Farmer edits a crop from the portal
- **Actor:** Farmer.
- **Precondition:** User is logged in; crop exists and is owned by user (or user has manage capability).
- **Flow:** 1) Farmer opens dashboard or crop list. 2) Clicks “Edit” for a crop. 3) System shows add-crop form with ?id= pre-filled. 4) Farmer updates fields and submits. 5) System updates post and meta, redirects to dashboard with success.
- **Postcondition:** Crop record updated.

**Use case diagram:** Draw actors (Farm Manager, Farm Staff) and system boundary; show use cases: Add Animal, Edit Crop, View Dashboard, View Reports, Add Expense, etc., with associations. (Create in Draw.io or similar.)

---

## 11. Tools and Technologies

- **Language / platform:** PHP 7.4+, WordPress 5.8+.
- **Database:** MySQL (via WordPress: wp_posts, wp_postmeta, wp_options).
- **Server:** XAMPP (Apache, MySQL) for development and demonstration.
- **Front-end:** HTML, CSS, JavaScript (minimal); WordPress shortcodes for portal UI.
- **Diagrams:** Draw.io, Lucidchart, or similar for use case, class, sequence, state, activity, component, deployment diagrams.
- **Version control / delivery:** Plugin source in folder; final deliverable as .zip (no .war/.ear; PHP/WordPress equivalent).

### Three-tier architecture (client–server, physically separated)
- **Tier 1 – Presentation:** Browser; WordPress admin UI; front-end portal (shortcodes: dashboard, add forms, public home). All user interaction.
- **Tier 2 – Business logic:** Farm Management plugin PHP (classes in `includes/`): validation, workflows, reports, permissions, CRUD orchestration. No direct SQL in view layer.
- **Tier 3 – Data:** MySQL; WordPress tables (wp_posts for CPTs, wp_postmeta for entity attributes, wp_options for settings). Data access only via WordPress APIs (e.g. wp_insert_post, get_post_meta).

---

*End of Phase 1 Proposal. To be presented at the end of the first 3 months of the WIL period.*
