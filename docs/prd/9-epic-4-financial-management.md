# 9. Epic 4: Financial Management

**Epic Goal:** Deliver expenses, feed inventory, CRM (customers), and sales tracking — the financial features behind the premium tier.

**Integration Requirements:** All routes behind premium middleware. Sales link to customers (optional FK). These features follow the same HTMX CRUD pattern established in Epic 2.

## Story 4.1: Expense Tracking

As a premium user,
I want to record and categorize farm expenses,
so that I can understand my operating costs.

**Acceptance Criteria:**

1. `Expense` model, migration, factory, seeder, policy
2. `ExpenseController` — `index`, `store`, `update`, `destroy`
3. `StoreExpenseRequest` form request
4. `expenses/index.blade.php` with inline HTMX CRUD + pagination
5. Category filtering (feed, medical, equipment, housing, utilities, other)
6. Feature SCSS: `_expenses.scss`

**Integration Verification:**

- IV1: HTMX inline create/edit/delete works
- IV2: Free-tier user is blocked
- IV3: Expenses scoped to authenticated user only

## Story 4.2: Feed Inventory

As a premium user,
I want to track feed purchases and inventory,
so that I can monitor feed costs and avoid running out.

**Acceptance Criteria:**

1. `FeedInventory` model, migration, factory, seeder, policy
2. `FeedInventoryController` — `index`, `store`, `update`, `destroy`
3. `StoreFeedInventoryRequest` form request
4. `feed/index.blade.php` with inline HTMX CRUD
5. Expiry date visibility (highlight expired/near-expiry)
6. Feature SCSS: `_feed.scss`

**Integration Verification:**

- IV1: HTMX CRUD cycle works
- IV2: Expired feed is visually distinguished
- IV3: Quantities validate as non-negative

## Story 4.3: Customer CRM

As a premium user,
I want to manage my egg customers with contact info and status,
so that I can maintain buyer relationships.

**Acceptance Criteria:**

1. `Customer` model, migration, factory, seeder, policy
2. `CustomerController` — `index`, `create`, `store`, `edit`, `update`, `destroy`
3. `StoreCustomerRequest` form request
4. `customers/index.blade.php` with search and active/inactive filtering
5. Soft deactivation (`is_active` flag) instead of hard delete
6. Delete uses `hx-confirm` + fade-out animation
7. Feature SCSS: `_crm.scss`

**Integration Verification:**

- IV1: Search filters customers by name
- IV2: Deactivated customers hidden from default view
- IV3: Customer with sales cannot be hard-deleted (if applicable)

## Story 4.4: Sales Tracking

As a premium user,
I want to record egg sales with customer association and payment tracking,
so that I can monitor my revenue.

**Acceptance Criteria:**

1. `Sale` model, migration, factory, seeder, policy
2. `SaleController` — `index`, `store`, `update`, `destroy`
3. `StoreSaleRequest` form request
4. `sales/index.blade.php` with inline HTMX CRUD + pagination
5. Optional customer association via dropdown (populated from user's customers)
6. Payment status toggle (paid/unpaid)
7. Feature SCSS: `_sales.scss`

**Integration Verification:**

- IV1: Sale linked to customer displays customer name
- IV2: Sale without customer works (nullable FK)
- IV3: Payment status toggles via HTMX

## Story 4.5: Financial Features Tests

As a developer,
I want full test coverage for expenses, feed, customers, and sales,
so that financial data integrity is guaranteed.

**Acceptance Criteria:**

1. Feature tests for all 4 controllers
2. Policy tests for all 4 models
3. Validation tests for currency/quantity fields (decimal precision, non-negative)
4. Sale-customer relationship tests (nullable FK, customer deletion impact)
5. Tier enforcement tests

**Integration Verification:**

- IV1: `php artisan test --filter=Expense` passes
- IV2: `php artisan test --filter=Feed` passes
- IV3: `php artisan test --filter=Customer` passes
- IV4: `php artisan test --filter=Sale` passes

---
