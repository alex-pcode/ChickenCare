# 1. Intro Project Analysis and Context

## 1.1 Existing Project Overview

### Analysis Source

Architecture documentation (v1.2, sharded at `docs/architecture/`) — comprehensive and current as of 2026-04-08.

### Current Project State

ChickenCare is a **poultry farm management application** currently running as a React 19 SPA with Supabase (PostgreSQL + Auth + RLS) and Netlify hosting at `d:\Koke\Aplikacija`. It provides egg tracking, flock/batch management, CRM, expenses, feed inventory, dashboard analytics, and free/premium tier gating. The rebuild targets **full feature parity** using Laravel 12 + Blade + HTMX + MariaDB as a local server-rendered monolith.

## 1.2 Available Documentation Analysis

- [x] Tech Stack Documentation
- [x] Source Tree / Architecture
- [x] Coding Standards
- [x] API Documentation (routes + HTMX specs)
- [x] External API Documentation
- [ ] UX/UI Guidelines — design is embedded in component specs and SCSS architecture
- [ ] Technical Debt Documentation — N/A (greenfield rebuild)

## 1.3 Enhancement Scope Definition

### Enhancement Type

- [x] Technology Stack Upgrade (React/Supabase -> Laravel/HTMX)
- [x] UI/UX Overhaul (SPA -> server-rendered + HTMX)

### Enhancement Description

Complete rebuild of the Chicken Manager application from a React 19 SPA with Supabase backend to a Laravel 12 monolith with Blade + HTMX, MariaDB, and pure SCSS styling. Goal is full feature parity for local development/testing use.

### Impact Assessment

- [x] Major Impact (architectural changes required) — this is a complete rewrite, not a modification.

## 1.4 Goals and Background Context

### Goals

- Full feature parity with the existing production React + Supabase app
- Local-first architecture — no cloud dependencies, runs entirely on `php artisan serve` + Docker MariaDB
- Server-rendered simplicity — eliminate client-side state management complexity
- HTMX-driven interactivity — SPA-like UX without a JavaScript framework
- Maintain free/premium tier gating model
- Clean, maintainable codebase optimized for AI-assisted development

### Background Context

The existing Chicken Manager is a production app that works but is over-engineered for its use case — React 19 with complex state management, Supabase with RLS policies, and Netlify serverless functions add unnecessary infrastructure complexity. The rebuild simplifies the stack to Laravel's traditional MVC pattern, making it easier to understand, modify, and extend. This is a learning/testing rebuild — not a migration of production users.

## 1.5 Change Log

| Change | Date | Version | Description | Author |
|--------|------|---------|-------------|--------|
| Initial | 2026-04-08 | 1.0 | Brownfield Enhancement PRD created for Laravel rebuild | John (PM) |

---
