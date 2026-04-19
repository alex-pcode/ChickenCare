# API Specification (Routes + HTMX)

Since we use **Laravel + HTMX** (server-rendered), there is no traditional JSON API. Controllers return either full Blade views (standard navigation) or HTML partials (HTMX requests with `HX-Request` header).

## Route Architecture

```