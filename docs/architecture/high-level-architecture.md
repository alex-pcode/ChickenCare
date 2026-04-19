# High Level Architecture

## Technical Summary

ChickenCare is a **server-rendered monolithic web application** built on **Laravel 12 with Blade templates and HTMX** for dynamic interactivity. The application uses **MariaDB 10.6.22** for persistent storage, **Laravel Breeze** for authentication scaffolding, and **pure SCSS** for styling. All business logic lives in Laravel controllers and services, with HTMX providing partial page updates, inline forms, and modal interactions without full page reloads. The architecture is optimized for **local development** using `php artisan serve` with no external infrastructure dependencies. MariaDB runs in a Docker container for easy management.

## Platform and Infrastructure Choice

**Platform:** Local development stack (PHP + MariaDB in Docker)
**Key Services:** Laravel 12, PHP 8.3, MariaDB 10.6.22 (Docker), Laravel Breeze, HTMX
**Deployment Host:** Local only (`127.0.0.1:8000`)

No cloud services, no serverless functions, no CDN. Everything runs on the local machine. This replaces:
- Supabase → MariaDB + Laravel Auth
- Netlify Functions → Laravel Controllers
- Netlify CDN → `php artisan serve`
- React SPA → Blade + HTMX

## Repository Structure

**Structure:** Single Laravel project (standard `laravel new` scaffold)
**Monorepo Tool:** N/A — single application, no package splitting needed
**Package Organization:** Standard Laravel conventions — `app/`, `resources/`, `routes/`, `database/`

## High Level Architecture Diagram

```mermaid
graph TB
    subgraph "Browser"
        HTML[Blade HTML + SCSS]
        HTMX[HTMX Engine]
        Alpine[Alpine.js - minimal client logic]
        HTML --> HTMX
        HTML --> Alpine
    end

    subgraph "Laravel Application"
        Router[Laravel Router]
        Middleware[Auth Middleware + Tier Middleware]
        
        subgraph "Controllers"
            DashboardCtrl[DashboardController]
            EggCtrl[EggEntryController]
            FlockCtrl[FlockProfileController]
            BatchCtrl[FlockBatchController]
            ExpenseCtrl[ExpenseController]
            FeedCtrl[FeedInventoryController]
            CRMCtrl[CustomerController]
            SaleCtrl[SaleController]
            DeathCtrl[DeathRecordController]
            BatchEventCtrl[BatchEventController]
        end

        subgraph "Service Layer"
            DashboardSvc[DashboardService]
            ReportSvc[ReportService]
            FlockSvc[FlockSummaryService]
        end

        subgraph "Eloquent Models"
            Models[User, FlockProfile, EggEntry,<br/>Expense, FeedInventory, Customer,<br/>Sale, FlockBatch, DeathRecord,<br/>BatchEvent, FlockEvent]
        end

        Router --> Middleware
        Middleware --> Controllers
        Controllers --> Service Layer
        Controllers --> Models
        Service Layer --> Models
    end

    subgraph "Database"
        MariaDB[(MariaDB 10.6.22<br/>Docker Container)]
        Models --> MariaDB
    end

    HTMX -->|HTTP Requests| Router
    Router -->|HTML Partials| HTMX
```

## Architectural Patterns

- **MVC (Model-View-Controller):** Laravel's native pattern — Models for data, Controllers for logic, Blade views for presentation. _Rationale:_ Industry standard, excellent tooling, maps directly to the existing app's domain structure.

- **Server-Side Rendering + HTMX Hypermedia:** Controllers return full pages or HTML partials (for HTMX requests). No JSON API needed. _Rationale:_ Eliminates the entire client-side state management layer (React Context, hooks, caching) — the server is the single source of truth.

- **Service Layer for Complex Logic:** Dashboard aggregations, reports, and flock summaries live in dedicated service classes rather than bloating controllers. _Rationale:_ Keeps controllers thin; mirrors the existing app's service layer pattern.

- **Policy-Based Authorization:** Laravel Policies replace Supabase RLS — each model gets a policy ensuring users only access their own data. _Rationale:_ Same user isolation guarantee, but enforced at application level with Laravel's built-in `authorize()`.

- **Blade Component Architecture:** Reusable Blade components (`<x-stat-card>`, `<x-data-table>`, `<x-form-group>`) replace the React shared UI library. _Rationale:_ Mirrors the existing component library structure while staying server-rendered.

---
