# 2. Requirements

## 2.1 Functional Requirements

- **FR1:** Users can register, log in, reset passwords, and manage their account using Laravel Breeze authentication (email + password).
- **FR2:** Users can create, read, update, and delete daily egg entries with date, count, size, color, and notes — with inline HTMX form submission and table updates.
- **FR3:** Users can manage a single flock profile per account with farm name, location, flock size, breed breakdown (hens/roosters/chicks/brooding), and notes. *(Premium)*
- **FR4:** Users can create and manage flock events (acquisition, laying start, broody, hatching, other) on their flock profile timeline. *(Premium)*
- **FR5:** Users can create, view, update, and archive flock batches with breed, acquisition date, bird counts by type, lifecycle dates, source, and cost. *(Premium)*
- **FR6:** Users can log batch events (health check, vaccination, relocation, breeding, laying start, etc.) and death records (with cause tracking) per batch. *(Premium)*
- **FR7:** Users can create, update, and delete expenses categorized by type (feed, medical, equipment, housing, utilities, other) with date and amount. *(Premium)*
- **FR8:** Users can track feed inventory with name, quantity, unit (kg/lbs), purchase date, expiry date, and cost. *(Premium)*
- **FR9:** Users can manage customers (name, phone, notes, active/inactive status) as a simple CRM. *(Premium)*
- **FR10:** Users can record egg sales with dozen/individual counts, total amount, payment status, and optional customer association. *(Premium)*
- **FR11:** Users can view sales reports with aggregated revenue data. *(Premium)*
- **FR12:** The dashboard displays aggregated stats (egg totals, expenses, revenue, flock counts, recent events) with Chart.js visualizations.
- **FR13:** Free-tier users have access to egg tracking and the dashboard only; premium features are gated by `EnsurePremiumTier` middleware with a clear upgrade prompt.
- **FR14:** All list views support pagination (15-25 items per page).
- **FR15:** HTMX-powered inline create/edit forms, tab switching, modal dialogs, and delete confirmations provide SPA-like interactivity without full page reloads.
- **FR16:** Savings calculator and viability analysis views are available to premium users.

## 2.2 Non-Functional Requirements

- **NFR1:** Full page loads must complete in < 200ms; HTMX partial swaps in < 100ms; CRUD operations in < 50ms (local environment).
- **NFR2:** CSS bundle must be < 50KB gzipped; JS bundle (HTMX + Alpine + Chart.js) < 100KB gzipped.
- **NFR3:** No more than 10 database queries per page load — enforce eager loading to prevent N+1.
- **NFR4:** All user data must be isolated via Policy-based authorization (`$user->id === $model->user_id`) — no user can access another user's records.
- **NFR5:** WCAG AA accessibility compliance — ARIA attributes on all interactive components, semantic HTML, keyboard navigability.
- **NFR6:** Application must run entirely locally with zero cloud dependencies — `php artisan serve` + Docker MariaDB only.
- **NFR7:** Pure SCSS styling with no CSS framework — neumorphic design system using variables, mixins, and BEM conventions.

## 2.3 Compatibility Requirements

- **CR1: Existing App Feature Parity:** Every feature in the React + Supabase app at `d:\Koke\Aplikacija` must have a functional equivalent in the Laravel rebuild.
- **CR2: Database Schema Parity:** All data models (User, FlockProfile, EggEntry, Expense, FeedInventory, Customer, Sale, FlockBatch, DeathRecord, BatchEvent, FlockEvent) must be represented with equivalent fields and relationships in MariaDB.
- **CR3: UI/UX Consistency:** The rebuild must replicate the same user workflows and navigation patterns (sidebar, tabs, inline forms, modals) even though the rendering technology changes.
- **CR4: Tier Gating Parity:** Free vs premium feature access must match the existing app's tier boundaries exactly.

---
