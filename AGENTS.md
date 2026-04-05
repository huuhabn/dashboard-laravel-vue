# AGENTS.md

Guidelines for AI agents working in this repository.

**Project Type:** Project
**Languages:** Typescript, Php

## Technology Stack

### Frameworks

- Vue (3.5.32)

### Libraries

- Shadcn-ui

### Styling

- Tailwindcss

### Package Manager

- Composer, Pnpm

## Project structure

High-level repository layout:

| Path                            | Role                                                                                                                                                        |
| ------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `app/Modules/Api/`              | Bounded context: HTTP API (`Http/Controllers`, `Http/Requests`, `Http/Resources`), services, repositories, events, `Models/User.php`, `Routes/api.php`      |
| `app/Http/Middleware/`          | Laravel middleware (e.g. appearance cookie)                                                                                                                 |
| `app/Providers/`                | Service providers                                                                                                                                           |
| `config/`                       | App config; `config/services.php` includes `services.dashboard.prefix` from `DASHBOARD_PREFIX` (default `admin`) for the SPA dashboard/settings URL segment |
| `database/`                     | Migrations, factories, seeders                                                                                                                              |
| `routes/`                       | `web.php` — SPA catch-all + signed verification/reset redirects; `console.php`                                                                              |
| `resources/views/app.blade.php` | Blade shell for the SPA; injects `window.__DASHBOARD_PREFIX__` before Vite                                                                                  |
| `resources/css/`                | Tailwind / global CSS entry                                                                                                                                 |
| `resources/js/`                 | Vue 3 SPA (TypeScript)                                                                                                                                      |
| `tests/`                        | PHPUnit feature tests                                                                                                                                       |

`resources/js/` (frontend) at a glance:

| Path                 | Role                                                                  |
| -------------------- | --------------------------------------------------------------------- |
| `app.ts` / `App.vue` | Entry, root layout; `App.vue` sets `lang` / `dir` (e.g. RTL for `ar`) |
| `router/`            | Vue Router; dashboard/settings paths use `config/dashboardPrefix.ts`  |
| `api/`               | Axios instance, auth header, API helpers                              |
| `stores/`            | Pinia (`auth`, `authConfig` — cached API config & social providers)   |
| `i18n/`              | `vue-i18n` setup; `locales/*.json` (`en`, `vi`, `ar`)                 |
| `layouts/`           | `app/` (sidebar shell), `auth/` (split / simple), `settings/`         |
| `pages/`             | Route views: `Welcome`, `Dashboard`, `auth/*`, `settings/*`           |
| `components/`        | App components + `ui/` (shadcn-vue primitives)                        |
| `composables/`       | Shared Vue composables                                                |
| `types/`             | TypeScript types                                                      |

## Development Commands

```bash
# Install dependencies
pnpm install

# Start development server
pnpm dev

# Build for production
pnpm build

# Run linter
pnpm lint

# Format code
pnpm format
```

## Code Conventions

- **Indentation:** spaces-4
- **Quotes:** single
- **Semicolons:** required
- **Max Line Length:** 80

### Patterns

- **API Style:** rest
- **Styling:** tailwind
- **Linting:** eslint
- **Formatting:** prettier

## Project Structure

**Structure Type:** flat
**Test Directory:** `tests/`

### Important Files

- `package.json`
- `tsconfig.json`
- `eslint.config.js`
- `.prettierrc`
- `vite.config.ts`
- `.env.example`
- `README.md`

## Development Guidelines

### TypeScript

- Use strict TypeScript with proper type annotations
- Prefer `interface` for object types, `type` for unions/intersections
- Avoid `any` - use `unknown` when type is uncertain

### Vue

- Use Composition API with `<script setup>`
- Keep components small and focused

### Styling

- Use Tailwind CSS utility classes
- Follow mobile-first responsive design
- Extract repeated patterns to components

### General

- Follow existing code patterns and conventions
- Write clear, self-documenting code
- Keep functions small and focused
- Add tests for new functionality
