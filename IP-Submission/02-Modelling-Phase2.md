# Phase 2 – Modelling with Classes  
## Farm Management System (FMS)  
### Industry Project – 6 Month WIL

This document provides the six diagram types required by the template, with Mermaid source and short descriptions. Diagrams can be rendered in tools that support Mermaid (e.g. GitHub, VS Code with Mermaid extension) or redrawn in Draw.io/Lucidchart.

---

## 1. Class Diagram

Main plugin classes and their relationships. Entities (Animal, Crop, Task, etc.) are represented as WordPress post types with meta; the diagram shows the PHP classes that manage them.

```mermaid
classDiagram
    class FMP_Post_Types {
        +register()
        -register_animal()
        -register_crop()
        -register_task()
        -register_inventory_item()
        -register_expense()
    }
    class FMP_Meta_Boxes {
        +register()
        +render_animal_meta()
        +save_animal_meta()
        +render_crop_meta()
        +save_crop_meta()
    }
    class FMP_Portal {
        +render_add_animal()
        +render_add_crop()
        +render_add_task()
        +get_edit_url()
        +get_add_url()
    }
    class FMP_Frontend {
        +render_farm_dashboard()
        +render_public_home()
        +portal_wrap()
        +get_portal_tabs()
    }
    class FMP_Reports {
        +get_vaccinations_due_including_overdue()
        +get_animals_by_species_and_status()
        +get_expenses_by_category()
    }
    class FMP_Dashboard {
        +get_post_type_counts()
        +get_overdue_vaccinations()
        +get_tasks_due_soon()
        +get_low_stock_items()
    }
    class FMP_Capabilities {
        +MANAGE_FARM
        +EXPORT_REPORTS
        +add_roles()
        +map_meta_cap()
    }
    FMP_Post_Types ..> FMP_Meta_Boxes : registers CPTs used by
    FMP_Portal ..> FMP_Dashboard : uses counts
    FMP_Frontend ..> FMP_Portal : wraps portal content
    FMP_Frontend ..> FMP_Reports : displays reports
```

**Caption:** Main plugin classes. FMP_Post_Types registers CPTs; FMP_Meta_Boxes and FMP_Portal handle CRUD UI and persistence; FMP_Frontend renders portal shell and dashboard; FMP_Reports and FMP_Dashboard provide report and widget data; FMP_Capabilities defines roles and permissions.

---

## 2. Sequence Diagram

Sequence for the use case “Farmer adds an animal from the portal”.

```mermaid
sequenceDiagram
    participant Farmer
    participant Browser
    participant FMP_Portal
    participant WordPress
    participant MySQL

    Farmer->>Browser: Open portal Add Animal page
    Browser->>FMP_Portal: render_add_animal() GET
    FMP_Portal->>FMP_Portal: Check login, output form
    FMP_Portal->>Browser: HTML form
    Farmer->>Browser: Fill form, submit POST
    Browser->>FMP_Portal: render_add_animal() POST, nonce
    FMP_Portal->>FMP_Portal: Verify nonce, sanitize inputs
    FMP_Portal->>WordPress: wp_insert_post(fmp_animal)
    WordPress->>MySQL: INSERT wp_posts
    FMP_Portal->>WordPress: update_post_meta (tag, species, ...)
    WordPress->>MySQL: INSERT wp_postmeta
    FMP_Portal->>FMP_Portal: handle_animal_image_upload, set_post_thumbnail
    FMP_Portal->>Browser: redirect_dashboard(success)
    Browser->>Farmer: Dashboard with success message
```

**Caption:** Add-animal flow from portal. Farmer submits form; FMP_Portal validates, creates post and meta via WordPress API, then redirects to the farm dashboard.

---

## 3. State Diagram

Entity lifecycles with clear states.

### Task lifecycle
```mermaid
stateDiagram-v2
    [*] --> Open
    Open --> InProgress : Start task
    InProgress --> Done : Complete
    InProgress --> Open : Reopen
    Done --> [*]
```

### Animal status
```mermaid
stateDiagram-v2
    [*] --> Active
    Active --> Sold : Sold
    Active --> Deceased : Deceased
    Sold --> [*]
    Deceased --> [*]
```

### Vaccination status (derived)
```mermaid
stateDiagram-v2
    [*] --> OK : next_due > today + N
    OK --> DueSoon : next_due within N days
    DueSoon --> Overdue : next_due < today
    Overdue --> [*]
```

**Caption:** Task has Open → In progress → Done. Animal has Active / Sold / Deceased. Vaccination display states are OK, Due soon, Overdue (derived from next due date).

---

## 4. Activity Diagram

Flow for “Record expense” (portal or admin).

```mermaid
flowchart TD
    Start([User opens Add Expense]) --> Form[Display form]
    Form --> Fill[User fills amount, category, date, title, notes]
    Fill --> Submit[User submits]
    Submit --> Validate{Nonce and validation OK?}
    Validate -->|No| Error[Show error / redirect with error]
    Validate -->|Yes| Insert[wp_insert_post fmp_expense]
    Insert --> Meta[update_post_meta amount, date, category, notes]
    Meta --> Redirect[Redirect to dashboard or list]
    Redirect --> End([End])
    Error --> End
```

**Caption:** Record expense: display form → fill → submit → validate → insert post and meta → redirect. Parallel flows exist for add animal, add crop, etc., with the same pattern.

---

## 5. Component Diagram

High-level components and dependencies.

```mermaid
flowchart TB
    subgraph Presentation ["Presentation tier"]
        Admin[Admin Pages]
        Shortcodes[Shortcodes / Portal UI]
    end
    subgraph BusinessLogic ["Business logic tier"]
        Portal[FMP_Portal]
        Frontend[FMP_Frontend]
        Reports[FMP_Reports]
        Dashboard[FMP_Dashboard]
        Meta[FMP_Meta_Boxes]
    end
    subgraph DataAccess ["Data access"]
        WP_API[WordPress API]
    end
    subgraph Data ["Data tier"]
        MySQL[(MySQL)]
    end
    Admin --> Meta
    Admin --> Reports
    Shortcodes --> Portal
    Shortcodes --> Frontend
    Portal --> Dashboard
    Portal --> WP_API
    Frontend --> Reports
    Frontend --> Dashboard
    Reports --> WP_API
    Dashboard --> WP_API
    Meta --> WP_API
    WP_API --> MySQL
```

**Caption:** Presentation (admin pages and shortcodes) depends on business logic (Portal, Frontend, Reports, Dashboard, Meta); business logic uses WordPress API only; WordPress API accesses MySQL.

---

## 6. Deployment Diagram

Nodes and artefacts.

```mermaid
flowchart LR
    subgraph Client ["Client node"]
        Browser[Browser]
    end
    subgraph AppServer ["Application server - XAMPP"]
        Apache[Apache]
        PHP[PHP]
        WP[WordPress]
        Plugin[Farm Management Plugin]
    end
    subgraph DBServer ["Database server"]
        MySQL[(MySQL)]
    end
    Browser -->|HTTP/HTTPS| Apache
    Apache --> PHP
    PHP --> WP
    WP --> Plugin
    Plugin --> MySQL
```

**Artefacts:**
- **Client:** Web browser.
- **Application server:** XAMPP (Apache + PHP); WordPress core; theme; Farm Management plugin (PHP, CSS, JS in `wp-content/plugins/farm-management/`).
- **Database server:** MySQL; WordPress database (wp_posts, wp_postmeta, wp_options, etc.).

**Caption:** Client sends requests to Apache; PHP runs WordPress and the plugin; the plugin accesses MySQL via WordPress. For submission, deliver plugin .zip, database backup, and deployment manual.

---

*End of Phase 2 Modelling. All six diagram types are covered. Export or redraw as needed for the formal submission.*
