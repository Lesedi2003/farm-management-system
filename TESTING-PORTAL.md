# Testing the Portal Login Guard

Use this checklist to verify that the Farm Management portal behaves correctly for logged-in and logged-out users.

---

## Before you start

1. **Menu setup:** Your site should have a link labeled **"Portal"** (or "Farm Dashboard") that points to the **portal entry page** — the page that contains the shortcode `[fmp_farm-dashboard]`.  
   - Typical page slug: `farm-dashboard` or `dashboard`.  
   - In **Appearance → Menus**, add that page to the menu and name it "Portal" (or "Farm Dashboard").

2. **Shortcode on the portal page:** The page your "Portal" link points to must contain **one** of:
   - `[fmp_farm-dashboard]` (recommended — this is the main dashboard)
   - Or `[fmp_animals]`, `[fmp_crops]`, `[fmp_vaccinations]`, `[fmp_reports]` (any portal shortcode)

---

## Case 1: User is NOT logged in

**Goal:** Clicking Portal must show the restricted message and a Log in button. No dashboard, no data.

### Steps

1. **Log out** (if you are logged in):
   - Open your site in the browser.
   - If you see the WordPress admin bar at the top, click your username → **Log out**.

2. **Go to the public home page** (e.g. `http://localhost/farm-management/` or your site URL).

3. **Click "Portal"** (or "Farm Dashboard") in the menu.

### Expected result

- You see a **card** with:
  - **Title:** "Restricted Area"
  - **Text:** "This portal is for authorized farm staff only."
  - A **"Log in"** button (green/primary style).
- You do **not** see:
  - The dashboard (Quick Add, KPI cards, tables).
  - Tabs for Animals, Crops, Vaccinations, Reports.
  - Any farm data.

4. **Click "Log in".**  
   - You should be taken to the WordPress login page.  
   - After logging in, you should be sent back to the page you tried to open (e.g. dashboard).

---

## Case 2: User IS logged in

**Goal:** Clicking Portal goes straight into the Farm Dashboard with full access.

### Steps

1. **Log in** to WordPress (wp-admin or the login link from the restricted message).

2. **Go to the public home page** (e.g. `http://localhost/farm-management/`).

3. **Click "Portal"** (or "Farm Dashboard") in the menu.

### Expected result

- You see the **full Farm Management dashboard**:
  - Header: "Farm Management" / "Welcome to your farm operations dashboard."
  - **Tabs:** Home, Dashboard, Animals, Crops, Vaccinations, Reports, Support, Logout.
  - **Quick Add** buttons (Add Animal, Add Crop, etc.).
  - **KPI cards** (Animals, Tasks, Inventory, Expenses, Vaccinations).
  - **Widgets:** Overdue Vaccinations, Vaccinations Due Soon, Tasks Due Soon, Low Stock Items.
- You can click **Animals**, **Crops**, **Vaccinations**, **Reports** and see the correct content (no restricted message).

---

## Quick reference

| Action              | Logged out                    | Logged in                          |
|---------------------|-------------------------------|------------------------------------|
| Click "Portal"      | Restricted Area + Log in btn  | Full dashboard + tabs               |
| Open /animals/      | Restricted Area + Log in btn  | Animals list                       |
| Open /crops/        | Restricted Area + Log in btn  | Crops list                         |
| Open / (Home)       | Public home (no login)        | Public home (no login)              |
| Open /contact/      | Public contact page          | Public contact page                |

---

## Troubleshooting

- **"Portal" shows the dashboard even when I’m logged out**  
  Your "Portal" link might point to the **Home** page (with `[fmp_home]`). The Home page is public and does not require login. Change the menu so "Portal" links to the **Dashboard** page (the one with `[fmp_farm-dashboard]`).

- **I see "Restricted Area" even when I’m logged in**  
  Clear cookies for the site and log in again. Confirm you’re logged in (e.g. you see the admin bar or "Howdy, [your name]" when visiting wp-admin).

- **Where do I set the menu?**  
  **WordPress Admin → Appearance → Menus.** Create or edit a menu, add the "Farm Dashboard" (or "Dashboard") page, and name the link "Portal". Assign the menu to the correct location (e.g. Primary).
