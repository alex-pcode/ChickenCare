# 10. Epic 5: Dashboard & Analytics

**Epic Goal:** Deliver the dashboard with aggregated stats and charts, sales reports, savings analysis, and viability calculator.

**Integration Requirements:** Depends on all prior epics — aggregates data from egg entries, batches, expenses, sales, and feed. Requires service classes for complex calculations.

## Story 5.1: Dashboard

As a user,
I want a dashboard showing my farm's key metrics and recent activity,
so that I get an at-a-glance overview when I log in.

**Acceptance Criteria:**

1. `DashboardController` with `index` action
2. `DashboardService` — `getSummary(User): array` aggregating eggs, expenses, sales, flock counts, recent events
3. `dashboard/index.blade.php` with `<x-ui.stat-card>` components
4. Chart.js visualizations (egg production trend, expense breakdown)
5. `dashboard/partials/` for HTMX-refreshable sections (if applicable)
6. Dashboard accessible to both free and premium users (data scoped to available features)
7. Queries optimized: < 10 queries, < 500ms load, eager loading
8. Feature SCSS: `_dashboard.scss`

**Integration Verification:**

- IV1: Dashboard loads with real aggregated data from seeded entries
- IV2: Free-tier user sees egg stats only; premium sees all stats
- IV3: Chart.js renders without JavaScript errors

## Story 5.2: Sales Reports

As a premium user,
I want to view sales reports with revenue summaries by period,
so that I can analyze my sales performance.

**Acceptance Criteria:**

1. `SalesReportController` with `index` action
2. `ReportService` — `getSalesReport(User, ?from, ?to): array`
3. `sales/reports.blade.php` with date range filter and summary tables
4. Per-customer revenue breakdowns
5. Totals by period (weekly/monthly)

**Integration Verification:**

- IV1: Report data matches manual sum of sales records
- IV2: Date range filter updates results via HTMX
- IV3: Per-customer breakdown includes correct sale totals

## Story 5.3: Savings & Viability Calculators

As a premium user,
I want to see financial analysis and viability calculations,
so that I can understand my farm's profitability.

**Acceptance Criteria:**

1. `SavingsController` with `index` action
2. `SavingsService` — `getFinancialAnalysis(User): array` (income vs expenses, profit margins, cost per egg)
3. `ViabilityController` with `index` action
4. `ViabilityService` — `calculate(User, array): array` (cost/profit per bird, break-even)
5. `savings/index.blade.php` and `viability/index.blade.php`
6. Feature SCSS: `_savings.scss`, `_viability.scss`

**Integration Verification:**

- IV1: Savings calculations are consistent with expense + sales data
- IV2: Viability calculator produces sensible outputs with seed data
- IV3: Both pages blocked for free-tier users

## Story 5.4: Dashboard & Analytics Tests

As a developer,
I want tests for dashboard, reports, savings, and viability,
so that aggregation logic is verified.

**Acceptance Criteria:**

1. Unit tests for `DashboardService`, `ReportService`, `SavingsService`, `ViabilityService`
2. Feature tests for all 4 controllers
3. Edge case tests: empty data (new user), single entry, large datasets
4. Performance: dashboard query count assertion (< 10)

**Integration Verification:**

- IV1: `php artisan test --filter=Dashboard` passes
- IV2: `php artisan test --filter=Report` passes
- IV3: `php artisan test --filter=Savings` passes
- IV4: `php artisan test --filter=Viability` passes

---
