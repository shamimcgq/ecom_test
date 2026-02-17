# ecom_test

This project now uses **MySQL** instead of JSON files for application data.

## Database setup

1. Create a database (default name used by the app: `ecom_test`).
2. Configure connection env vars:
   - `DB_HOST` (default: `127.0.0.1`)
   - `DB_PORT` (default: `3306`)
   - `DB_NAME` (default: `ecom_test`)
   - `DB_USER` (default: `root`)
   - `DB_PASS` (default: empty)
   - `DB_CHARSET` (default: `utf8mb4`)
3. On first request, schema in `database/schema.sql` is applied automatically.

## Tables

- `site_config`
- `shipping_settings`
- `products`
- `admin_users`
- `users`
- `orders`
- `order_items`
