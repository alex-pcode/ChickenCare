# Introduction

This document outlines the complete fullstack architecture for ChickenCare, a poultry farm management application being rebuilt from a React + Supabase + Netlify SPA into a **Laravel 13 + HTMX + Blade** monolithic application backed by **MariaDB 10.6.22**. It serves as the single source of truth for AI-driven development.

This rebuild is for **local testing purposes** with **full feature parity** against the existing production application at `d:\Koke\Aplikacija`. The goal is to replicate all domain features — egg tracking, flock/batch management, CRM, expenses, feed inventory, dashboard analytics, and premium tier gating — using a traditional server-rendered architecture enhanced with HTMX for dynamic interactivity without a JavaScript framework.

## Starter Template or Existing Project

**Brownfield rebuild** — This is a full rebuild of the existing Chicken Manager application (React 19 + Supabase + Netlify). The existing app's database schema, business logic, and feature set are the requirements source. We use **Laravel Breeze (Blade)** as the starter kit for authentication scaffolding with Tailwind stripped out and replaced by pure SCSS.

## Change Log

| Date | Version | Description | Author |
|------|---------|-------------|--------|
| 2026-04-07 | 1.0 | Initial fullstack architecture for Laravel + HTMX rebuild | Winston |
| 2026-04-08 | 1.1 | Added accessibility standards (WCAG AA), pinned PHP 8.3, updated component templates with ARIA | Winston |
| 2026-04-08 | 1.2 | Added Laravel Boost MCP server for AI-assisted development | Winston |

---
