# Database populate script

The plugin includes a built-in **sample data** (populate) feature in the **Demo** module.

## How to run the populate script

1. Log in to WordPress as an **Administrator** (or user with `fmp_manage_settings`).
2. Go to **Farm Management → Settings**.
3. Enable **Demo mode** if the option is available.
4. Click **Create sample data** (or the button that triggers the create action).

This runs the handler in `includes/class-fmp-demo.php` (`handle_create_sample`), which:

- Creates sample **animals**, **crops**, **tasks**, **inventory items**, **expenses**, and **vaccinations**.
- Assigns all records to the current user (`post_author`).
- Marks each with `_fmp_seeded = 1` so they can be removed later via **Delete sample data**.

## Technical note

The “populate script” is the PHP code path: `admin_post_fmp_demo_create_sample` → `FMP_Demo::handle_create_sample()`. It performs multiple `wp_insert_post()` and `update_post_meta()` calls in sequence. There is no separate SQL file; the plugin uses the WordPress API so that post types and meta keys stay consistent.

For submission, this README and the reference to `class-fmp-demo.php` satisfy the requirement to “populate your database using a script”.
