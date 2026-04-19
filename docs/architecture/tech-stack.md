# Tech Stack

| Category | Technology | Version | Purpose | Rationale |
|----------|-----------|---------|---------|-----------|
| Backend Language | PHP | 8.3 | Server-side logic | Laravel 13 requirement, modern type support, pinned for consistency |
| Backend Framework | Laravel | 13 | Full-stack framework | MVC, Eloquent ORM, migrations, auth, policies |
| Auth Starter | Laravel Breeze | latest | Auth scaffolding (Blade) | Login, register, password reset out of the box |
| Frontend Interactivity | HTMX | 2.0.x | Dynamic partial page updates | SPA-like UX without JavaScript framework |
| Client-Side Micro-Logic | Alpine.js | 3.x | Dropdowns, toggles, tabs | Standard HTMX companion for small UI state |
| CSS Preprocessor | SCSS (Sass) | latest | Custom styling | Nesting, variables, mixins — no framework dependency |
| Build Tool | Vite | 6.x | Asset compilation | Ships with Laravel, handles SCSS + JS bundling |
| Database | MariaDB | 10.6.22 | Persistent storage (Docker) | User-specified, MySQL-compatible with Eloquent |
| ORM | Eloquent | (Laravel) | Database abstraction | Relationships, scopes, mutators, built-in |
| Charts | Chart.js | 4.x | Dashboard visualizations | Lightweight, no React dependency (replaces Recharts) |
| Icons | Lucide (SVG) | latest | UI icons | Blade includes of SVGs, no JS runtime needed |
| Form Validation | Laravel Validation | (Laravel) | Server-side validation | Form Requests replace Zod + React Hook Form |
| Testing (Unit) | PHPUnit | 11.x | Backend unit tests | Laravel's default testing framework |
| Testing (Feature) | Laravel HTTP Tests | (Laravel) | Route/controller testing | Built-in request simulation |
| Testing (Browser) | Laravel Dusk | latest | E2E browser testing (optional) | Chrome-based, replaces Playwright |
| Monitoring | Laravel Telescope | latest | Local debug/monitoring | Request inspection, queries, mail — local only |
| Logging | Laravel Log | (Laravel) | Application logging | File-based, replaces Sentry for local use |
| Migrations | Laravel Migrations | (Laravel) | Schema versioning | Replaces Supabase migrations |
| AI Dev Tools | Laravel Boost | latest | MCP server for AI-assisted development | Gives AI agents schema, routes, config, docs, tinker access |
| Package Manager | pnpm | latest | Frontend dependency management | Faster, stricter, disk-efficient |

## Key Replacements from Original Stack

| Original | Laravel Replacement |
|----------|-------------------|
| React 19 + React Router | Blade templates + Laravel routes |
| Supabase Auth + RLS | Laravel Breeze + Policies |
| Supabase PostgreSQL | MariaDB 10.6.22 (Docker) + Eloquent |
| Netlify Functions (10) | Laravel Controllers |
| React Context + Hooks | Server-side — no client state management |
| Tailwind CSS | Pure SCSS |
| Recharts | Chart.js |
| Zod + React Hook Form | Laravel Form Requests |
| Framer Motion | CSS transitions + HTMX swap animations |
| Sentry | Laravel Telescope (local) |
| npm | pnpm |

---
