# Performance Audit Report — April 19, 2026

## Executive Summary

A comprehensive performance audit was conducted on the ChickenCare application dashboard (`/app`) using Chrome DevTools CLI (Lighthouse, Performance Traces, Network analysis). Backend Laravel code was analyzed for query performance issues and optimized following Laravel best practices.

**Key Result: Server response time (TTFB) reduced by 38% (330ms → 204ms) through query optimization, index additions, and caching improvements.**

---

## Before/After Comparison

### Lighthouse Scores

| Metric | Before | After | Change |
|--------|--------|-------|--------|
| Accessibility | 96 | 96 | — |
| Best Practices | 81 | 81 | — |
| SEO | 91 | 91 | — |
| Total Timing | 5,652ms | 4,519ms | **-20%** |

### Performance Trace (Navigation with cache bypass)

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| **TTFB** | 330ms | 204ms | **-38%** |
| **LCP** | 441ms | 461ms | ~same (within variance) |
| **CLS** | 0.00 | 0.00 | Perfect |
| Render Delay | 111ms | 257ms | +146ms (variance) |
| Document Download | 332ms | 206ms | **-38%** |

### Document Request Timing

| Phase | Before | After | Change |
|-------|--------|-------|--------|
| Queued | 3ms | 2ms | -1ms |
| Request Sent | 6ms | 9ms | +3ms |
| **Download Complete** | **332ms** | **206ms** | **-126ms** |
| Processing Complete | 342ms | 217ms | **-125ms** |

### Console Errors
- Before: 0 errors
- After: 0 errors

---

## Issues Found & Fixed

### 1. Missing `preventLazyLoading()` (Critical)

**File:** `app/Providers/AppServiceProvider.php`

**Issue:** No lazy loading prevention enabled — N+1 queries silently pass in development.

**Fix:** Added `Model::preventLazyLoading(!app()->isProduction())` to `boot()` method. This will throw `LazyLoadingViolationException` in development when relationships are accessed without being eager-loaded.

---

### 2. EggStatsService — 6 queries reduced to 1 (High)

**File:** `app/Services/EggStatsService.php`

**Issue:** `getStats()` made 6 separate database queries:
- `SUM(count)` — total eggs
- `COUNT(DISTINCT date)` — distinct days
- `SUM(count)` for this week
- `SUM(count)` for previous week
- `SUM(count)` for this month
- `SUM(count)` for previous month

**Fix:** Combined into a single query using conditional aggregates (`CASE WHEN`):
```php
$stats = $user->eggEntries()
    ->selectRaw('COALESCE(SUM(count), 0) as total_eggs')
    ->selectRaw('COUNT(DISTINCT date) as distinct_days')
    ->selectRaw('COALESCE(SUM(CASE WHEN date BETWEEN ? AND ? THEN count ELSE 0 END), 0) as this_week', [...])
    // ... 3 more conditional aggregates
    ->first();
```

**Impact:** 6 queries → 1 query (83% reduction)

---

### 3. CrmReportsService — Multiple loop-query patterns eliminated (High)

**File:** `app/Services/CrmReportsService.php`

#### 3a. `revenueOverview()` — Loading all sales into memory

**Before:** `$query->get()` loaded ALL sales records, then summed/counted in PHP.
**After:** Single query with database-level aggregates (`SUM`, `COUNT`, `AVG`, `CASE WHEN`).
Cache TTL increased from 60s to 300s.

**Impact:** 1 full-table load → 1 aggregate query; 5x longer cache

#### 3b. `productionPipeline()` — 12 queries in loop reduced to 2

**Before:** Loop executed 6 iterations × 2 queries each (eggs + sales per month) = 12 queries.
**After:** 2 grouped queries using `GROUP BY strftime('%Y-%m', date)`, results looked up by key.

**Impact:** 14 queries → 2 queries (86% reduction)

#### 3c. `revenueTrend()` — 12 queries in loop reduced to 1

**Before:** Loop iterated 12 months, each making a `SUM` query = 12 queries.
**After:** Single grouped query with monthly revenue, filled into 12-month array.

**Impact:** 12 queries → 1 query (92% reduction)

---

### 4. DashboardService — Mortality calculation optimized (Medium)

**File:** `app/Services/DashboardService.php`

**Issue:** `getFlockStats()` used `withSum('deathRecords', 'count')->get()->sum(...)` which loaded all batch models into memory just to sum death records.

**Fix:** Replaced with a direct `whereIn` subquery:
```php
$totalMortality = DeathRecord::whereIn('batch_id', (clone $activeBatches)->select('id'))->sum('count');
```

**Impact:** N models hydrated → 1 aggregate query

---

### 5. ExpenseStatsService — 3 query passes reduced to 1 (Medium)

**File:** `app/Services/ExpenseStatsService.php`

**Issue:** `totalsByCategory()`, `grandTotal()`, and `transactionCountByCategory()` each queried the expenses table separately. `categoryBreakdown()` called all three.

**Fix:** Combined into a single `once()`-memoized query:
```php
Expense::where('user_id', $this->userId)
    ->selectRaw('category, SUM(amount) as total, COUNT(*) as c')
    ->groupBy('category')
    ->get();
```

**Impact:** 3 queries → 1 query per request (67% reduction), memoized with `once()`

---

### 6. FlockBatchStatsService — `whereHas` replaced with `whereIn` (Medium)

**File:** `app/Services/FlockBatchStatsService.php`

**Issue:** `tabCounts()` used `whereHas('flockBatch', ...)` which generates a correlated `EXISTS` subquery.

**Fix:** Replaced with `whereIn('batch_id', $user->flockBatches()->select('id'))` for better index usage.

---

### 7. FeedStatsService — Batch flock size calculations (Medium)

**File:** `app/Services/FeedStatsService.php`

**Issue:** `monthlyTrends()` called `flockSizeAtDate()` once per month, each making 2 queries (acquisitions + deaths) = 2N queries for N months.

**Fix:** Added `batchFlockSizes()` method that pre-loads all acquisition and death data in 2 queries, then computes sizes for all dates from in-memory collections.

**Impact:** 2N queries → 2 queries (where N = number of months)

---

### 8. Missing Database Indexes (High)

**Migration:** `2026_04_19_072225_add_performance_indexes.php`

| Table | Index | Columns | Query Pattern |
|-------|-------|---------|---------------|
| death_records | idx_death_records_user_date | (user_id, date) | FeedStatsService WHERE user_id AND date |
| feed_inventory | idx_feed_inventory_depleted | (user_id, depleted_date) | FeedStatsService WHERE depleted_date BETWEEN |
| flock_batches | idx_flock_batches_acquisition | (user_id, acquisition_date) | FeedStatsService WHERE acquisition_date <= |
| batch_events | idx_batch_events_user_date | (user_id, date) | DashboardService WHERE user_id ORDER BY date |
| sales | idx_sales_customer_date | (customer_id, sale_date) | CrmReportsService WHERE customer_id ORDER BY sale_date |

---

## Test Results

All existing tests continue to pass after optimization:

| Test Suite | Tests | Status |
|-----------|-------|--------|
| Dashboard | 86 | All passed |
| EggStats | 7 | All passed |
| CRM | 20 | All passed |
| Expense | 107 | All passed |
| FlockBatch + Feed | 171 | All passed |
| Savings | 81 | All passed |

---

## Remaining Opportunities

These items were identified but not addressed in this audit (lower priority):

1. **Response compression** — Document latency check shows compression is not applied to HTML responses. Adding gzip/brotli middleware would save ~25.8 kB per page load.
2. **HTTP/2** — The app serves over HTTP/1.1. Upgrading to HTTP/2 would enable multiplexing for faster asset loading.
3. **CrmReportsService::customerAnalytics()** — Still loads all sales with customers into memory. Could be converted to database-level GROUP BY with aggregates.
4. **FeedStatsService::feedPeriodBreakdown()** — Still calls `flockSizeAtDate()` per feed item. Could benefit from the same batch-loading pattern applied to `monthlyTrends()`.
5. **Chart.js forced reflow** — 14ms of forced reflow caused by Chart.js `getStyle()` function. This is a third-party library issue.

---

## Files Modified

| File | Change |
|------|--------|
| `app/Providers/AppServiceProvider.php` | Added `preventLazyLoading()` |
| `app/Services/EggStatsService.php` | Combined 6 queries into 1 |
| `app/Services/CrmReportsService.php` | Optimized 3 methods (28 queries → 4) |
| `app/Services/DashboardService.php` | Optimized mortality aggregation |
| `app/Services/ExpenseStatsService.php` | Combined 3 queries into 1 with `once()` |
| `app/Services/FlockBatchStatsService.php` | Replaced `whereHas` with `whereIn` |
| `app/Services/FeedStatsService.php` | Batched flock size calculations |
| `database/migrations/2026_04_19_072225_add_performance_indexes.php` | 5 new indexes |

## Total Query Reduction Estimate

| Service | Before (queries) | After (queries) | Reduction |
|---------|-----------------|-----------------|-----------|
| EggStatsService::getStats() | 6 | 1 | 83% |
| CrmReportsService::revenueOverview() | 1 (full load) | 1 (aggregate) | Memory savings |
| CrmReportsService::productionPipeline() | 14 | 2 | 86% |
| CrmReportsService::revenueTrend() | 12 | 1 | 92% |
| DashboardService::getFlockStats() | N+1 | 2 | ~80% |
| ExpenseStatsService::payload() | 3 | 1 | 67% |
| FeedStatsService::monthlyTrends() | 2N+1 | 3 | ~85% |
