# Monitoring and Observability

## Tools

- **Laravel Telescope** (`/telescope`) — requests, queries, exceptions, mail, cache, models
- **Laravel Debugbar** (optional) — in-page query/timing overlay
- **`storage/logs/laravel.log`** — application log file
- **`php artisan tinker`** — interactive REPL for query experiments

## Laravel Boost (AI Development MCP Server)

**Purpose:** [Laravel Boost](https://laravel.com/ai/boost) is an MCP (Model Context Protocol) server that exposes 15 tools to AI agents (Claude Code, Cursor, Windsurf, etc.), giving them deep context about the Laravel project during development.

**Install:**
```bash
composer require laravel/boost --dev
php artisan boost:install
```

**Tools provided to AI agents:**
- **Application Info** — Project structure, installed packages, configuration
- **Database Schema** — Inspect table structures, columns, relationships
- **Database Queries** — Run read queries against the database
- **Route Inspector** — List and inspect application routes
- **Artisan Commands** — List and run Artisan commands
- **Tinker Integration** — Execute PHP code in application context
- **Configuration Access** — Read application configuration values
- **Documentation Search** — Search Laravel docs with version-aware results
- **Error Tracking** — Read application logs and exceptions
- **Browser Logs** — Read browser console logs and errors

**Why this matters for ChickenCare:** This project is designed for AI-driven development. Boost gives AI agents the ability to inspect our database schema, run test queries, check routes, read logs, and access Laravel documentation — all without leaving the conversation. This dramatically improves the quality of AI-generated code because the agent has real-time context rather than relying on documentation alone.

## Key Monitoring Points

- Slow queries (>100ms flagged by Telescope)
- N+1 query detection via Telescope Queries tab
- Exception stack traces with request context
- Mail captures (password reset emails in log driver)

---

**Generated with [Claude Code](https://claude.ai/code)**

**Co-Authored-By:** Winston (Architect Agent)
