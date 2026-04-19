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

## SCSS Standards

The project uses SCSS with BEM naming, organized into `_variables.scss`, `_mixins.scss`, `components/`, and `features/` partials.

### 1. Mobile First

Write base styles for mobile. Use `min-width` media queries to expand for larger screens. The `@include mobile` mixin (which uses `max-width`) is reserved only for mobile-specific overrides — not as the primary layout strategy.

```scss
// Good: mobile-first
.grid {
    grid-template-columns: 1fr;

    @media (min-width: 768px) {
        grid-template-columns: repeat(2, 1fr);
    }
}

// Bad: desktop-first
.grid {
    grid-template-columns: repeat(2, 1fr);

    @media (max-width: 480px) {
        grid-template-columns: 1fr;
    }
}
```

### 2. Use Design Tokens

All common values must come from `_variables.scss`. Do not hardcode colors, spacing, transitions, border-radii, or font sizes when a token exists.

| Token category | Example variable | Replaces |
|---------------|-----------------|----------|
| Spacing | `$space-3` | `0.75rem` |
| Transitions | `$transition-duration-slow` | `0.3s`, `300ms` |
| Transition easing | `$transition-easing` | `cubic-bezier(0.4, 0, 0.2, 1)` |
| Border radius | `$radius-lg` | `0.75rem` |
| Hover lift | `$hover-lift-sm` | `-2px` |
| Semantic colors | `$color-success-accent` | `#16a34a` |
| Error state | `$color-error-bg`, `$color-error-text`, `$color-error-border` | `#fef2f2`, `#991b1b`, `#fecaca` |

When a file needs these tokens, add `@use '../variables' as *;` at the top.

### 3. No `#id` Selectors and No `!important`

Both create specificity conflicts. Use doubled class selectors for specificity when needed:

```scss
// Good: doubled selector for specificity
.shiny-cta--success.shiny-cta--success {
    background: $color-success-accent;
}

// Bad: !important
.shiny-cta--success {
    background: #16a34a !important;
}
```

**Sole exception:** `[x-cloak] { display: none !important; }` for Alpine.js hydration.

### 4. No Magic Numbers

Every hardcoded `px`, `rem`, or timing value must either use a variable or include a comment explaining its purpose.

```scss
// Good
transform: translateY($hover-lift-sm);
transition: all $transition-duration-slow $transition-easing;

// Good (with comment)
// 56px matches mobile bottom nav spec
min-height: 56px;

// Bad
transform: translateY(-2px);
transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
```

### 5. BEM Naming

Follow `.block__element--modifier` convention. Name selectors by semantic role, not appearance.

```scss
// Good
.savings__kpi-grid { }
.savings__section-title { }

// Bad
.big-grid { }
.blue-text { }
```

### 6. Zero Values and Decimals

- Omit units on zero-length: `margin: 0;` (not `0px`)
- Keep units on zero-duration: `animation-delay: 0s;`
- Always include leading zero: `opacity: 0.4;` (not `.4`)

### 7. Comments Above, Not Beside

Place `//` comments on the line above the property they describe. Prefer `//` over `/* */`.

```scss
// Good
// Neumorphic shadow for depth
box-shadow: $shadow-md;

// Bad
box-shadow: $shadow-md; // shadow
```

### 8. Group Media Queries at Block Root

Keep responsive overrides grouped at the root of the BEM block, not scattered inside each sub-selector.

```scss
// Good
.card {
    &__title { font-size: 1rem; }
    &__body { padding: $space-4; }

    @media (min-width: 768px) {
        &__title { font-size: 1.25rem; }
        &__body { padding: $space-6; }
    }
}

// Bad
.card {
    &__title {
        font-size: 1rem;
        @media (min-width: 768px) { font-size: 1.25rem; }
    }
}
```

### 9. Reduced-Motion Accessibility

Every page with animations must include a `prefers-reduced-motion: reduce` block. Do not use `!important` in these blocks — place them after the animation declarations so cascade handles specificity.

```scss
@media (prefers-reduced-motion: reduce) {
    .hero__image,
    .hero__badge {
        animation: none;
        opacity: 1;
        transform: none;
    }
}
```

---
