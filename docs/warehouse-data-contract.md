# Warehouse Data Contract

This document defines the current warehouse data contract. It is intended to protect existing production data while later cleanup work is planned in small, reversible steps.

## Legacy Core Data

The following tables and columns are the legacy core of the warehouse workflow. They must not be removed, renamed, reinterpreted, or overwritten casually.

- `products.serial_number`
  - Unique physical serial/barcode identity for one real inventory item.
  - This is the primary lookup key for serial trace behavior.

- `products.status`
  - Current inventory state for a serial.
  - `1` means the item is still in stock.
  - `2` means the item has been exported/sold.

- `products.created_at`
  - Legacy import timestamp for existing serials.
  - Some reports and fallback behavior still infer import timing from this value.

- `export_vouchers`
  - Legacy export voucher header and financial record.
  - Revenue reports use `total_amount`, `total_cost`, and `exported_at` from this table.

- `export_vouchers.items`
  - Legacy JSON archive of exported items and serial lists.
  - This remains a source of truth for old export contents.
  - It is also a fallback source for serial trace when newer links are missing.

- `export_vouchers.exported_at`
  - Legacy export timestamp.
  - Used by reports, printed vouchers, and fallback trace behavior.

## Add-On Audit Layer

The following tables and columns were added after the legacy core. They improve traceability and reporting, but they do not replace the legacy core.

- `import_vouchers`
  - Import voucher layer for batches created by newer import flows.
  - Links new imports to supplier, product catalog, location, user, quantity, and import time.

- `stock_movements`
  - Audit timeline layer for warehouse events.
  - Stores import/export movements per serial when the schema and code path support it.
  - This table is an audit layer, not the only source of truth.

- `products.import_voucher_id`
  - Optional link from a serial to its import voucher.

- `products.imported_at`
  - Optional explicit import timestamp.
  - For legacy rows, this may have been backfilled from `products.created_at`.

- `products.export_voucher_id`
  - Optional link from a serial to its export voucher.

- `products.exported_at`
  - Optional explicit export timestamp.

## Data Rules

- Each `serial_number` represents exactly one physical product.
- `products.status = 1` means the serial is currently in stock.
- `products.status = 2` means the serial has been exported/sold.
- Exported serials must not be deleted.
- `stock_movements` is an audit layer and must not be treated as a replacement for the legacy core tables.
- `export_vouchers.items` JSON remains a legacy archive and fallback trace source.
- New code should preserve compatibility with both old records that only have legacy data and new records that also have audit-layer links.

## Reporting Rules

- Warehouse history should prefer `stock_movements` when the audit schema is available.
- Inventory summary should use `stock_movements` for period movement calculations and reconcile against `products.status` for current stock.
- Serial trace must fall back to `products` plus `export_vouchers.items` when movement rows or direct voucher links are missing.
- Revenue reporting remains based on `export_vouchers`, especially `exported_at`, `total_amount`, and `total_cost`.
- Import value derived from legacy data should be treated as estimated unless linked to `import_vouchers` or `stock_movements`.

## Areas Not Yet Refactored

These areas are known to contain mixed responsibilities or incomplete audit behavior. They should not be rewritten without focused tests and data reconciliation first.

- `ProductController::storeManual`
  - Handles supplier/catalog/location creation, pricing updates, product creation, import voucher creation, and movement creation in one flow.

- `ExportController::store`
  - Handles customer creation, seller snapshotting, voucher creation, financial totals, serial export, product status updates, and movement creation in one flow.

- `ProductCatalogController::update`
  - Can update `location_id` in bulk for all products under a catalog.
  - This location change does not currently write `stock_movements`.

- `routes/api.php`
  - Contains export API routes, but the application routing configuration does not currently register this file.
  - The active export API routes are declared under `routes/web.php`.

- `resources/js/warehouse/import.js`
  - Exists but is empty.
  - The active import page logic currently lives in `resources/js/import-warehouse.js` and is loaded through `resources/js/app.js`.

