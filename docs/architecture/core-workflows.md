# Core Workflows

## Workflow 1: User Registration & First Login

```mermaid
sequenceDiagram
    participant B as Browser
    participant L as Laravel Router
    participant MW as Auth Middleware
    participant C as RegisterController
    participant M as User Model
    participant DB as MariaDB

    B->>L: GET /register
    L->>C: create()
    C->>B: Render register.blade.php

    B->>L: POST /register (name, email, password)
    L->>C: store(RegisterRequest)
    C->>M: User::create() with tier='free'
    M->>DB: INSERT INTO users
    DB->>M: OK
    C->>C: Auth::login($user)
    C->>B: Redirect /app (303)

    B->>L: GET /app
    L->>MW: auth middleware check
    MW->>L: Authenticated
    L->>B: Render dashboard.blade.php
```

## Workflow 2: Add Egg Entry (HTMX Inline Create)

```mermaid
sequenceDiagram
    participant B as Browser/HTMX
    participant L as Laravel
    participant FR as StoreEggEntryRequest
    participant M as EggEntry Model
    participant DB as MariaDB

    Note over B: User fills inline form on eggs page

    B->>L: POST /app/eggs (HX-Request: true)
    L->>FR: Validate input

    alt Validation fails
        FR->>B: 422 — re-render form partial with @error messages
    else Validation passes
        FR->>L: $request->validated()
        L->>M: $user->eggEntries()->create(data)
        M->>DB: INSERT INTO egg_entries
        DB->>M: OK
        L->>B: 200 — render eggs/partials/entry-row.blade.php
    end

    Note over B: Table updates without page reload
```

## Workflow 3: Dashboard Load (Multi-Model Aggregation)

```mermaid
sequenceDiagram
    participant B as Browser
    participant L as Laravel
    participant DS as DashboardService
    participant DB as MariaDB

    B->>L: GET /app
    L->>DS: getSummary($user)

    par Parallel queries
        DS->>DB: SELECT COUNT, SUM from egg_entries WHERE user_id=?
        DS->>DB: SELECT SUM(amount) from expenses WHERE user_id=?
        DS->>DB: SELECT SUM(total_amount) from sales WHERE user_id=?
        DS->>DB: SELECT current_count from flock_batches WHERE user_id=?
        DS->>DB: SELECT * from batch_events WHERE user_id=? LIMIT 5
    end

    DB->>DS: Aggregated results
    DS->>L: DashboardData array
    L->>B: Render dashboard.blade.php with stat cards and charts
```

## Workflow 4: Batch Detail View with Tabs (HTMX Tab Switching)

```mermaid
sequenceDiagram
    participant B as Browser/HTMX
    participant L as Laravel
    participant P as FlockBatchPolicy
    participant DB as MariaDB

    B->>L: GET /app/batches/5
    L->>P: authorize('view', $batch)
    P->>P: $user->id === $batch->user_id
    L->>DB: Load batch + relationships
    L->>B: Full page: batches/show.blade.php (overview tab)

    Note over B: User clicks "Events" tab

    B->>L: GET /app/batches/5?tab=events (HX-Request: true)
    L->>DB: BatchEvent::where('batch_id', 5)->orderBy('date', 'desc')
    L->>B: HTML partial: batches/partials/events-tab.blade.php
    Note over B: HTMX swaps tab content, URL updates via hx-push-url

    Note over B: User clicks "Add Event"

    B->>L: GET /app/batches/5/events/create (HX-Request: true)
    L->>B: HTML partial: event-form.blade.php in modal

    B->>L: POST /app/batches/5/events (HX-Request: true)
    L->>DB: INSERT INTO batch_events
    L->>B: Updated events-tab.blade.php
    Note over B: Modal closes, events tab refreshes
```

## Workflow 5: Premium Feature Gate

```mermaid
sequenceDiagram
    participant B as Browser
    participant L as Laravel Router
    participant MW as EnsurePremiumTier

    B->>L: GET /app/expenses
    L->>MW: Check user tier
    MW->>MW: $user->tier === 'free'

    alt HTMX request
        MW->>B: 200 — render premium-gate partial
    else Standard request
        MW->>B: Redirect /app with flash warning
    end

    Note over B: User never reaches ExpenseController
```

## Workflow 6: Delete with Confirmation (HTMX)

```mermaid
sequenceDiagram
    participant B as Browser/HTMX
    participant L as Laravel
    participant P as CustomerPolicy
    participant DB as MariaDB

    B->>B: hx-confirm="Delete this customer?" browser dialog
    B->>L: DELETE /app/customers/12 (HX-Request: true)
    L->>P: authorize('delete', $customer)
    P->>P: $user->id === $customer->user_id
    L->>DB: UPDATE customers SET is_active = false WHERE id = 12
    L->>B: 200 — empty response
    Note over B: hx-swap="outerHTML swap:500ms" fades out the row
```

---
