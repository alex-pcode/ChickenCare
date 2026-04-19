# Coding Standards

## Accessibility Standard

**Target: WCAG 2.1 AA compliance** for all Blade components and views.

**Required ARIA attributes on all Blade components:**
- **Forms:** `aria-invalid="true"` + `aria-describedby` on fields with errors. `role="alert"` on error messages. `aria-hidden="true"` on decorative elements (icons, required asterisks).
- **Modals:** `role="dialog"` + `aria-modal="true"` + `aria-labelledby` pointing to modal title. Focus trapped inside modal. Escape key closes modal.
- **Navigation:** `role="navigation"` + `aria-label` on `<nav>` elements. `aria-current="page"` on active sidebar link.
- **Tables:** `<th scope="col">` on column headers. `aria-sort` on sortable columns. `aria-label` on action buttons (delete, edit) that lack visible text.
- **Flash messages:** `role="alert"` + `aria-live="polite"` for success, `aria-live="assertive"` for errors.
- **Empty states:** `role="status"` so screen readers announce them.
- **Charts:** `aria-label` describing the chart purpose. Fallback `<table>` inside `<noscript>` for data accessibility.

**Testing:** Use browser DevTools Accessibility panel for manual checks during development. Consider `axe-core` via Laravel Dusk for automated checks if needed later.

## Critical Rules

1. **Ownership scoping:** Every query MUST start from `$request->user()->relationship()`
2. **HTMX dual responses:** Every mutating action handles both HTMX and standard requests
3. **Validation in Form Requests only:** No `$request->validate()` in controllers
4. **No raw HTML output:** Always `{{ }}`, never `{!! !!}` for user content
5. **Blade components for reusable UI:** Extract if used more than once
6. **SCSS BEM naming:** `.block__element--modifier`
7. **Models are thin:** `$fillable`, `$casts`, relationships, scopes only
8. **Policies for authorization:** Never check ownership in controllers directly
9. **No Eloquent in Blade:** Pass all data from controllers

## Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Controllers | PascalCase, singular | `EggEntryController` |
| Models | PascalCase, singular | `FlockBatch` |
| DB tables | snake_case, plural | `flock_batches` |
| Form Requests | Store/Update + Model | `StoreFlockBatchRequest` |
| Policies | Model + Policy | `FlockBatchPolicy` |
| Services | Domain + Service | `DashboardService` |
| Blade views | kebab-case | `entry-row.blade.php` |
| Blade components | dot-namespaced | `<x-forms.date-input>` |
| Routes | kebab-case, RESTful | `/app/flock-batches/{batch}` |
| Route names | dot-separated | `app.batches.store` |
| SCSS files | `_` prefix, kebab-case | `_stat-card.scss` |
| SCSS classes | BEM | `.stat-card__value--positive` |
| Test methods | snake_case with test_ | `test_user_can_view_their_eggs()` |

---
