# Error Handling Strategy

## Error Response Matrix

| Error Type | HTTP Status | Standard Response | HTMX Response |
|-----------|-------------|-------------------|---------------|
| Validation failure | 422 | Redirect back with errors | Re-render form with `@error` |
| Unauthenticated | 401/302 | Redirect to `/login` | `HX-Redirect: /login` |
| CSRF mismatch | 419 | Error page | JS redirect to `/login` |
| Unauthorized (policy) | 403 | 403 error page | Forbidden partial |
| Not found | 404 | 404 error page | Not-found partial |
| Premium gate | 200 | Redirect with flash | Premium gate partial |
| Server error | 500 | 500 error page | Generic error partial |

## HTMX Error Configuration

```javascript
// 422 validation errors swap normally
document.body.addEventListener('htmx:beforeSwap', function(evt) {
    if (evt.detail.xhr.status === 422) {
        evt.detail.shouldSwap = true;
        evt.detail.isError = false;
    }
});

// 419 session expiry redirects to login
document.body.addEventListener('htmx:responseError', function(evt) {
    if (evt.detail.xhr.status === 419) {
        window.location.href = '/login';
    }
});
```

---
