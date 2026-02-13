# Phase 3 – User Interface  
## Farm Management System (FMS)  
### Industry Project – 6 Month WIL

---

## 1. Design User Interfaces

### Screens to document

| Screen | Description | Location / shortcode |
|--------|-------------|----------------------|
| **Public home** | Landing: hero title, subtitle, CTA (Open Portal, Contact), feature cards, screenshots. | `[fmp_public_home]` on front page. |
| **Portal dashboard** | Quick Add buttons (Add Animal, Crop, Task, Inventory, Expense, Vaccination); stat cards (counts); widgets: Overdue Vaccinations, Due Soon Vaccinations, Tasks Due Soon, Low Stock. | `[fmp_farm-dashboard]` or `[fmp_portal]` on portal page. |
| **Add Animal** | Form: Tag/ID, Photo, Species, Breed, Sex, DOB, Acquired date, Status, Weight, Notes. Buttons: Save, Cancel. | Portal page with `[fmp_add_animal]`. |
| **Add Crop** | Form: Crop name, Crop type (dropdown), Planting date, Expected harvest date, Field/Location, Status, Notes. Buttons: Save, Cancel. | Portal page with `[fmp_add_crop]`. |
| **Add Task** | Form: Title, Due date, Priority, Status, Notes. Buttons: Save, Cancel. | Portal page with `[fmp_add_task]`. |
| **Add Inventory** | Form: Item name, Category (dropdown), Quantity, Unit (dropdown), Reorder level, Notes. Buttons: Save, Cancel. | Portal page with `[fmp_add_inventory]`. |
| **Add Expense** | Form: Title, Amount, Date, Category (dropdown), Notes. Buttons: Save, Cancel. | Portal page with `[fmp_add_expense]`. |
| **Add Vaccination** | Form: Animal (dropdown), Vaccine name, Date given, Next due date (required), Notes. Buttons: Save, Cancel. | Portal page with `[fmp_add_vaccination]`. |
| **Reports** | Tabs/sections: Expenses by category (table), Task completion summary; optional chart. Export CSV (admin). | Admin Reports page or front-end `[fmp_reports]`. |
| **Admin list screens** | List of animals, crops, tasks, inventory, expenses, vaccinations with columns and Edit/Delete. | WP Admin → Farm Management submenus. |

### Wireframes / screenshots

- **Recommendation:** Capture screenshots of each screen (public home, portal dashboard, each add form, reports) and paste into this document or a separate UI-spec PDF. Label each with: screen name, main elements (fields, buttons), and short description.
- **Wireframes:** Optional; simple boxes for layout (hero, feature grid, form rows) can be drawn in Draw.io. Implementation is in `includes/class-fmp-frontend.php` (dashboard, public home, reports) and `includes/class-fmp-portal.php` (add forms).

### Design highlights

- **Responsive:** Farmer UI CSS (`assets/css/fmp-farmer-ui.css`) provides breakpoints (e.g. 479px, 640px, 768px); tables stack on small screens; buttons and grids reflow.
- **Portal vs admin:** Farmers use the front-end portal (shortcodes); managers can use wp-admin. Portal uses a consistent card-based layout, primary green buttons, and clear back/dashboard links.

---

## 2. Demo the Prototype

### Suggested demo script (5–10 minutes)

1. **Public home (1 min):** Open site front page. Show hero (“Farm Management System”), feature cards, CTA “Open Portal”.
2. **Login (0.5 min):** Click Open Portal → redirect to login. Log in as a farmer/staff user.
3. **Portal dashboard (1.5 min):** Show dashboard: Quick Add buttons, stat cards (Animals, Tasks, Inventory, Expenses, Vaccinations), Overdue Vaccinations widget, Tasks Due Soon, Low Stock (if any).
4. **Add animal (2 min):** Click Add Animal. Fill Tag, Species, Breed, optional dates. Submit. Confirm redirect to dashboard and success message.
5. **Edit crop (1.5 min):** From dashboard or crops list, click Edit on a crop. Show pre-filled form. Change one field, submit. Confirm redirect and success.
6. **Reports (1–2 min):** Go to Reports (admin or front-end). Show “Expenses by category” table and “Task completion” summary.
7. **Optional:** Show admin Animals list and one edit from admin.

Record a screen recording as backup if live demo is not possible.

---

## 3. Heuristic Evaluation

Using Nielsen’s usability heuristics, score each as **Pass**, **Minor issue**, or **Major issue**, and note defects.

| # | Heuristic | Score | Notes / defects |
|---|-----------|--------|-----------------|
| 1 | **Visibility of system status** | Pass | Success/error messages after add/edit; dashboard shows counts and widget data. |
| 2 | **Match between system and real world** | Pass | Terms match domain (Animals, Crops, Vaccinations, Reorder level, etc.). |
| 3 | **User control and freedom** | Pass | Cancel and “Back to Dashboard” on forms; can navigate via portal tabs. |
| 4 | **Consistency and standards** | Pass | Buttons (Save/Cancel), form layout, and card style are consistent across portal. |
| 5 | **Error prevention** | Minor | Required fields (e.g. vaccination next due) enforced; some forms could add more client-side validation or inline hints. |
| 6 | **Recognition rather than recall** | Pass | Labels on all fields; dropdowns for category/unit/status; dashboard shows current state. |
| 7 | **Flexibility and efficiency of use** | Pass | Quick Add and direct edit links reduce steps for frequent tasks. |
| 8 | **Aesthetic and minimalist design** | Pass | Farmer UI uses clear hierarchy, spacing, and limited colour set. |
| 9 | **Help users recognize, diagnose, and recover from errors** | Minor | Server-side errors redirect with generic “Something went wrong”; could add field-level messages. |
| 10 | **Help and documentation** | Minor | No inline help or help tab; deployment manual and this doc serve as external help. |

**Summary:** Mostly Pass; a few Minor issues (stronger validation feedback, optional inline help). No Major issues identified for core flows.

---

## 4. Validate Fields – Verification and Validation

### Portal add forms (FMP_Portal)

| Form | Required fields | Server-side checks | Client-side |
|------|-----------------|--------------------|-------------|
| Add/Edit Animal | None (title derived from tag/species) | Nonce, sanitize text/textarea, number for weight, file type for photo | Optional: HTML5 type=number, type=date |
| Add/Edit Crop | None | Nonce, sanitize | type=date |
| Add/Edit Task | None | Nonce, sanitize | type=date |
| Add/Edit Inventory | None | Nonce, sanitize, number for quantity/reorder | type=number |
| Add/Edit Expense | None | Nonce, sanitize, number for amount | type=number, type=date |
| Add/Edit Vaccination | Next due date, Animal | Nonce, sanitize; animal must be in user’s list | required on next due date and animal select |

### Admin forms

- Same meta fields; admin pages use capability checks (`FMP_Capabilities::MANAGE_FARM`, etc.) and nonces; sanitisation in meta box save callbacks and admin save handlers.

### Verification and validation summary

- **Verification:** Inputs are sanitised (e.g. `sanitize_text_field`, `sanitize_textarea_field`); file uploads validated (e.g. image type); nonces prevent CSRF; capabilities enforce roles.
- **Validation:** Business rules (e.g. vaccination next due required; animal ID must belong to user) are enforced in PHP before `wp_insert_post` / `update_post_meta`. Client-side: HTML5 `required`, `type="number"`, `type="date"` where used.
- **Reference:** Form handling in `class-fmp-portal.php` (render_add_* and POST blocks); admin in `includes/admin/pages/class-fmp-admin-*.php` and `class-fmp-meta-boxes.php`.

---

*End of Phase 3 User Interface. Include screenshots or wireframes in the final submission as needed.*
