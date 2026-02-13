# Deployment Manual  
## Farm Management System (FMS)  
### Industry Project – 6 Month WIL

This document describes how to install, configure, and run the Farm Management plugin on a local XAMPP WordPress environment, and how to prepare the final submission deliverables.

---

## 1. Prerequisites

- **XAMPP** (or equivalent: Apache + MySQL + PHP).
- **PHP:** 7.4 or higher (recommended 8.0+).
- **WordPress:** 5.8 or higher.
- **MySQL:** 5.7+ or MariaDB 10.3+.

---

## 2. Install XAMPP and WordPress

1. Install XAMPP (e.g. to `C:\xampp` on Windows or `/opt/lampp` on Linux).
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Create a WordPress site:
   - Download WordPress from https://wordpress.org and extract into e.g. `C:\xampp\htdocs\Farm-Management` (or use a subfolder like `htdocs\wordpress\farm`).
   - Create a MySQL database and user (e.g. via phpMyAdmin: `http://localhost/phpmyadmin`).
   - Run the WordPress installer in the browser (`http://localhost/Farm-Management/wp-admin/install.php`), set site title, admin user, and password.

---

## 3. Install the Farm Management Plugin

**Option A – From plugin folder (development)**

1. Copy the entire `farm-management` folder into the WordPress plugins directory:
   - Path: `wp-content/plugins/farm-management/`
   - So that the main file is: `wp-content/plugins/farm-management/farm-management.php`
2. In WordPress admin, go to **Plugins** and **Activate** “Farm Management”.

**Option B – From zip (submission / production)**

1. Use the submission zip `farm-management-1.0.0.zip` (see section 8 below).
2. In WordPress admin go to **Plugins → Add New → Upload Plugin**, choose the zip, then **Install Now** and **Activate**.

After activation, a top-level menu **“Farm Management”** appears in the admin sidebar.

---

## 4. Create Pages and Shortcodes (Portal Setup)

The plugin provides shortcodes for the **public site** and the **farmer portal**. Create WordPress pages and assign shortcodes as follows.

### 4.1 Public (pre-login) pages

| Page title (suggestion) | Shortcode | Purpose |
|-------------------------|-----------|--------|
| Home | `[fmp_public_home]` | Marketing-style home with feature list and “Login to Portal”. |
| Contact | `[fmp_contact]` | Contact form. |

Add these pages to your main menu (e.g. Home, Features, Pricing, Contact, Login).

### 4.2 Portal (post-login) pages

Create separate pages for the farmer portal and add them to a menu that is shown only when the user is logged in (or use one “Portal” page with tabs). Recommended shortcodes:

| Page title (suggestion) | Shortcode | Purpose |
|------------------------|-----------|--------|
| Portal Home | `[fmp_home]` | Portal entry with links to dashboard, animals, crops, etc. |
| Dashboard | `[fmp_farm-dashboard]` | Dashboard with counts, quick add, tasks due soon, vaccinations. |
| Animals | `[fmp_animals]` | List and add/edit animals. |
| Crops | `[fmp_crops]` | List and add/edit crops. |
| Vaccinations | `[fmp_vaccinations]` | List and add vaccinations. |
| Reports | `[fmp_reports]` | Vaccinations due, animals by species/status, monthly expenses. |
| Support | `[fmp_support]` | Support/contact form for logged-in users. |

**Important:** Do not add portal pages to the public menu. Link “Login to Portal” from the public home to the Dashboard page (or to `wp-login.php` with redirect to the Dashboard URL).

### 4.3 Optional: “Setup Portal Pages” (admin)

If the plugin provides a **Farm Management → Frontend / Portal** settings page:

- Use it to see the list of shortcodes and suggested page titles.
- Create the pages manually and paste the shortcodes into each page’s content.
- Configure the “Dashboard” and “Reports” page IDs if the plugin stores them in options.

---

## 5. Optional: Load Sample Data

To populate the database with sample animals, crops, tasks, inventory, expenses, and vaccinations:

1. Log in as an **Administrator** (or user with `fmp_manage_settings`).
2. Go to **Farm Management → Settings**.
3. If there is a **Demo** section, enable it if required.
4. Click **“Create Sample Data”** (or the equivalent button that runs the create-sample action).

Sample records are created and marked so they can be removed later via **“Delete Sample Data”** in the same place.

Alternatively, see **IP-Submission/scripts/README-populate.md** for the technical reference (the “populate script” is the Demo handler in `includes/class-fmp-demo.php`).

---

## 6. How to Run the Application

1. **Start services:** Open XAMPP Control Panel and start **Apache** and **MySQL**.
2. **Open site:** In the browser go to your WordPress URL (e.g. `http://localhost/Farm-Management/`).
3. **Public site:** View the home and contact pages; click “Login to Portal” (or your login link).
4. **Log in:** Use your WordPress admin or farmer user credentials.
5. **Portal:** After login, go to the Dashboard page (or Portal Home) and use the portal tabs/menu to open Animals, Crops, Vaccinations, Reports, Support.
6. **Admin:** Go to `http://localhost/Farm-Management/wp-admin/` and open **Farm Management** for dashboard, Animals, Crops, Tasks, Inventory, Expenses, Vaccinations, Reports, and Settings.

---

## 7. Roles and Permissions (optional)

- **Administrator:** Has all capabilities by default.
- **Farm manager / Farm staff:** The plugin may define roles (e.g. `farm_manager`, `farm_staff`) and capabilities (`fmp_manage_farm`, `fmp_export_reports`, `fmp_manage_settings`, `fmp_delete_records`). Assign these in **Users** or via a role manager if you need to test different access levels.
- **Farmer (portal only):** Use a user with no admin access; they see only the front-end portal and their own records (filtered by `post_author`).

---

## 8. Preparing Submission Deliverables

### 8.1 Plugin archive (zip)

1. From the WordPress installation root, ensure the plugin folder is complete:  
   `wp-content/plugins/farm-management/`
2. Zip the **contents** of the `farm-management` folder so that the zip root contains `farm-management.php`, `includes/`, `assets/`, etc. (not an extra `farm-management` folder inside the zip, unless the assignment asks for it).
   - **Recommended:** Zip the folder so that the archive has one root folder `farm-management` (e.g. right‑click `farm-management` → “Compress to farm-management-1.0.0.zip”).
3. Name the file e.g. **farm-management-1.0.0.zip** (include version number).

### 8.2 Database backup

1. Open **phpMyAdmin** (e.g. `http://localhost/phpmyadmin`).
2. Select the WordPress database.
3. Use **Export** (e.g. “Quick” or “Custom” with default options).
4. Save as **database-backup.sql**.
   - For a minimal backup that still demonstrates schema and data: export at least the tables `wp_posts`, `wp_postmeta`, and `wp_options` (table prefix may differ if you changed it during install).

### 8.3 Schema / DDL (optional)

- The “schema” is documented in **04-Database-Phase4.md** (post types and meta keys).
- Optionally export only the structure (DDL) of `wp_posts` and `wp_postmeta` from phpMyAdmin and save as **schema-ddl-reference.sql** for reference.

### 8.4 Phase documents as PDF

- Convert each Phase document to PDF:
  - `01-Proposal-Phase1.md` → **01-Proposal-Phase1.pdf**
  - `02-Modelling-Phase2.md` → **02-Modelling-Phase2.pdf**
  - `03-User-Interface-Phase3.md` → **03-User-Interface-Phase3.pdf**
  - `04-Database-Phase4.md` → **04-Database-Phase4.pdf**
  - `05-Test-Plan-and-Reports.md` → **05-Test-Plan-and-Reports.pdf**
  - `06-Deployment-Manual.md` → **06-Deployment-Manual.pdf**
- Use “Print to PDF”, pandoc, or an editor export feature.

### 8.5 Final submission folder layout

```
Farm-Management-IP-Submission/
├── 01-Proposal-Phase1.pdf
├── 02-Modelling-Phase2.pdf
├── 03-User-Interface-Phase3.pdf
├── 04-Database-Phase4.pdf
├── 05-Test-Plan-and-Reports.pdf
├── 06-Deployment-Manual.pdf
├── farm-management-1.0.0.zip
├── database-backup.sql
└── (optional) schema-ddl-reference.sql
```

Place this folder (or its contents) in the location required by your institution for hand-in.

---

## 9. Three-Tier Architecture (Reminder)

- **Tier 1 (Presentation):** Browser, WordPress admin UI, front-end portal (shortcodes).
- **Tier 2 (Business logic):** Plugin PHP in `farm-management.php` and `includes/` (validation, workflows, reports, permissions).
- **Tier 3 (Data):** MySQL via WordPress (wp_posts, wp_postmeta, wp_options); no custom tables.

The application runs on a single machine (XAMPP) with this logical separation; deployment to a remote server follows the same steps (install WordPress, upload plugin, create pages, configure database and URLs).
