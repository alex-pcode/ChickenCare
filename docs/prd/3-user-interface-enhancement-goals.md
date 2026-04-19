# 3. User Interface Enhancement Goals

## 3.1 Integration with Existing UI

The UI is a complete rewrite from React components to Blade + HTMX, but it deliberately mirrors the original app's component structure. The architecture defines a 1:1 mapping of 17 React components to Blade equivalents (e.g., `StatCard` -> `<x-ui.stat-card>`, `DataTable` -> `<x-tables.data-table>`). The design system shifts from Tailwind CSS to pure SCSS with neumorphic styling using BEM naming, custom variables, and mixins. No CSS framework is used.

Key UI patterns preserved from the original:

- **Sidebar navigation** with tier-based feature visibility
- **Inline HTMX forms** for CRUD (no separate create/edit pages)
- **Modal dialogs** for complex operations (loaded via HTMX)
- **Tab switching** via HTMX partial swaps with URL push state
- **Flash messages** with auto-dismiss
- **Empty states** with friendly messaging

## 3.2 Modified/New Screens and Views

| Screen | Type | Tier | Notes |
|--------|------|------|-------|
| Landing page (`/`) | Rebuild | Public | Marketing page + costs page |
| Login / Register / Password Reset | Rebuild | Public | Laravel Breeze scaffolding |
| Dashboard (`/app`) | Rebuild | Auth | Stat cards + Chart.js charts |
| Egg Tracking (`/app/eggs`) | Rebuild | Free | Inline HTMX CRUD table |
| Flock Profile (`/app/flock`) | Rebuild | Premium | Single profile + events timeline |
| Batch Management (`/app/batches`) | Rebuild | Premium | List + detail view with tabs (overview, events, deaths) |
| Expenses (`/app/expenses`) | Rebuild | Premium | Inline HTMX CRUD table |
| Feed Inventory (`/app/feed`) | Rebuild | Premium | Inline HTMX CRUD table |
| Customers (`/app/customers`) | Rebuild | Premium | CRUD with search |
| Sales (`/app/sales`) | Rebuild | Premium | CRUD + reports sub-page |
| Savings (`/app/savings`) | Rebuild | Premium | Read-only financial analysis |
| Viability (`/app/viability`) | Rebuild | Premium | Calculator page |
| Account Settings (`/app/account`) | Rebuild | Auth | Edit profile |
| Onboarding Wizard | Rebuild | Auth | Multi-step first-login flow |
| Premium Gate | New pattern | Free | HTMX-aware upgrade prompt (partial or redirect) |

## 3.3 UI Consistency Requirements

- All interactive components must include ARIA attributes (WCAG AA) — `aria-invalid`, `aria-describedby`, `aria-live`, `role="alert"` on errors
- Form inputs must restore state on validation failure via Laravel's `old()` helper
- HTMX swap transitions must use consistent CSS animations (fade, slide) defined in `_animations.scss`
- Tables must use the shared `<x-tables.data-table>` component with standardized column formatting
- Modals load into a single `#modal-container` with `aria-live="polite"`
- Delete operations always use `hx-confirm` browser dialogs before executing
- Sidebar highlights active route; hides premium features for free-tier users

---
