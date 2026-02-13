# Farm Management System – Industry Project Submission

This folder contains the **Phase 1–5 documents** and **scripts** for the Farm Management WordPress plugin submission.

## Contents

| Item | Description |
|------|-------------|
| **01-Proposal-Phase1.md** | Phase 1: Project name, domain analysis, problem, scope (IRBM), vision/objectives, users/roles, mandatory functions, requirements, use cases, tools, three-tier mapping. |
| **02-Modelling-Phase2.md** | Phase 2: Class, sequence, state, activity, component, and deployment diagrams (Mermaid). |
| **03-User-Interface-Phase3.md** | Phase 3: Screens/shortcodes, demo script, heuristic evaluation, field validation list. |
| **04-Database-Phase4.md** | Phase 4: WordPress schema, post types and meta keys, integrity, normalization, populate instructions, transactions/queries. |
| **05-Test-Plan-and-Reports.md** | Phase 5: Test plan (scope, environment, roles), test cases table, reports list, final deliverable checklist. |
| **06-Deployment-Manual.md** | Deployment: XAMPP, WordPress, plugin install, pages/shortcodes, portal setup, sample data, how to run, and **how to prepare the submission zip and database backup**. |
| **scripts/** | **README-populate.md** – how to run the database populate (Demo “Create sample data” from Settings). |

## Final submission layout

For hand-in, produce PDFs from each `.md` (e.g. Print to PDF or pandoc) and gather:

- `01-Proposal-Phase1.pdf` … `06-Deployment-Manual.pdf`
- `farm-management-1.0.0.zip` (plugin folder zipped; see 06-Deployment-Manual §8.1)
- `database-backup.sql` (MySQL dump; see 06-Deployment-Manual §8.2)
- (optional) `schema-ddl-reference.sql`

See **06-Deployment-Manual.md** for step-by-step deployment and deliverable preparation.

## Suggested folder structure for submission

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

Export each `.md` to PDF (e.g. Print to PDF, pandoc, or VS Code) before creating the submission folder.
