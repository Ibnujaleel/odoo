# AGENTS.md — Development Rules for AI Agents

> **Read this file in full before writing any code.** These rules are mandatory for every task in this project. If a task conflicts with a rule, stop and flag the conflict instead of silently breaking the rule.

---

## 0. How to Use This File

- Treat every rule here as a requirement, not a suggestion.
- Re-read the relevant section before starting a feature, and the **Definition of Done** (Section 7) before finishing it.
- If a rule is unclear or a decision is not covered, choose the simplest option that satisfies the rules, and record the decision in `docs/DECISIONS.md` (see Section 6).
- Never delete or weaken a rule in this file without explicit instruction from the project owner.

---

## 1. Data: Real-Time and Dynamic First

**Rule:** Use real-time or dynamic data sources wherever possible. Static JSON is allowed **only** for initial prototyping.

### Requirements
1. **Default to live sources**: REST/GraphQL APIs, databases, WebSockets, server-sent events, or other dynamic providers.
2. **Static JSON is temporary.**
   - Allowed only in early prototypes, tests, and storybook/demo fixtures.
   - Keep it in `/mocks` or `/fixtures`, never mixed into production source.
   - Every static data usage must have a `// TODO(dynamic): replace with live source` comment and a matching entry in `docs/TODO.md`.
3. **Use a data-access layer.** UI components must never fetch directly. All data goes through a service/repository module (e.g. `src/services/`), so sources can be swapped without touching the UI.
4. **Handle the full request lifecycle** for every data call:
   - Loading state
   - Success state
   - Empty state
   - Error state (with retry where sensible)
5. **Network resilience**: set timeouts, retry transient failures with backoff, and cancel stale requests.
6. **Caching & freshness**: cache responses where it helps, define a clear refresh/invalidate strategy, and show "last updated" where data freshness matters.
7. **Secrets & config**: API keys, URLs, and tokens go in environment variables. Never hard-code them or commit them. Maintain `.env.example` (see Section 6).
8. **Never fabricate data.** If a source is unavailable, show an error or empty state. Do not display invented values as if they were real.

---

## 2. UI & Design: Responsive, Clean, Consistent

**Rule:** Build a responsive, clean, and user-friendly UI with a consistent colour scheme, layout, and design language.

### Design system (single source of truth)
1. Define design tokens **once** (CSS variables, theme file, or Tailwind config) and use them everywhere:
   - Colours (primary, secondary, accent, background, surface, text, border, success, warning, error)
   - Typography (font family, size scale, weights, line heights)
   - Spacing scale (e.g. 4 / 8 / 12 / 16 / 24 / 32 / 48 px)
   - Border radius, shadows, z-index layers, breakpoints
2. **No magic values.** Do not hard-code hex colours, pixel sizes, or fonts inside components. Reference tokens.
3. Build **reusable components** (Button, Input, Card, Modal, Table, Toast, etc.) and reuse them. Do not duplicate styling per page.
4. Support **light and dark mode** if the project's theme allows it, using tokens only.

### Responsiveness
1. **Mobile-first.** Design for the smallest screen, then enhance upward.
2. Test at minimum: ~360px (phone), ~768px (tablet), ~1280px+ (desktop).
3. Use flexbox/grid and relative units; avoid fixed widths. No horizontal page scroll.
4. Touch targets must be at least 44×44 px on touch devices.
5. Wide content (tables, code, charts) scrolls inside its own container, never the page body.

### Cleanliness & usability
1. Clear visual hierarchy: one primary action per view, consistent button styles, readable text.
2. Maintain accessible contrast (WCAG AA minimum) and visible focus states.
3. Use semantic HTML, `alt` text, labels for every input, and ARIA only where semantics fall short.
4. Provide **feedback for every action**: loading indicators, success confirmations, and clear error messages.
5. Keep copy short, plain, and consistent in tone and terminology.
6. Respect `prefers-reduced-motion`; keep animations subtle and purposeful.

---

## 3. Input Validation: Robust and Defensive

**Rule:** Implement robust input validation to handle invalid, incomplete, and unexpected user inputs.

### Requirements
1. **Validate on both sides.** Client-side validation is for UX; **server-side/API-boundary validation is mandatory** for security and correctness. Never trust the client.
2. **Use a schema validation library** (e.g. Zod, Yup, Joi, Pydantic) so rules are declared once and reused.
3. Validate for:
   - **Type** (string vs number vs date, etc.)
   - **Presence** (required vs optional, empty strings, whitespace-only)
   - **Format** (email, phone, URL, date, ID patterns)
   - **Range & length** (min/max, character limits)
   - **Allowed values** (enums, whitelists)
   - **Cross-field logic** (e.g. end date after start date, password confirmation)
4. **Handle unexpected input** gracefully: `null`, `undefined`, very long strings, special characters, emoji, unicode, SQL/HTML/script injection attempts, duplicate submissions, and malformed files.
5. **Sanitize and escape** output. Prevent XSS and injection; use parameterised queries only.
6. **Files & uploads**: validate type (by content, not just extension), size, and count.
7. **Error messages** must be:
   - Specific and actionable ("Enter a valid email like name@example.com")
   - Shown inline next to the field, plus a summary for long forms
   - Free of internal details (no stack traces or system info shown to users)
8. **Preserve user input** on error. Never wipe a form because one field failed.
9. Disable or debounce submit buttons during submission to prevent double-sends.
10. Add **tests** for valid, invalid, empty, boundary, and malicious inputs for every validated field.

---

## 4. Navigation & Layout: Intuitive and Consistent

**Rule:** Ensure intuitive navigation with properly positioned menus, consistent spacing, and easy access to key features.

### Requirements
1. **Persistent, predictable navigation.**
   - Primary navigation in a consistent location on every page (top bar or side bar on desktop; collapsible/bottom nav or hamburger menu on mobile).
   - Navigation must not move or change structure between pages.
2. **Key features within two clicks/taps** from anywhere in the app. Primary actions are always visible or clearly reachable.
3. **Show location.** Highlight the active menu item; use breadcrumbs for deep hierarchies; page titles must match their nav labels.
4. **Labels over guesses.** Use clear text labels (icons alone only when universally understood, and then with tooltip/`aria-label`).
5. **Consistent spacing.** Use only the spacing scale tokens. Keep consistent padding, margins, and gutters across all pages.
6. **Menus & overlays**: dropdowns, modals, and drawers must be keyboard-accessible (Tab, Enter, Esc), trap focus when modal, and close predictably.
7. **Handle edge routes**: provide a friendly 404 page, an error boundary, and a route guard/redirect for unauthorised access.
8. **Keyboard & screen-reader support**: logical tab order, skip-to-content link, and correct landmarks (`header`, `nav`, `main`, `footer`).
9. Preserve state on back/forward navigation where reasonable (scroll position, filters, form drafts).

---

## 5. General Engineering Standards

1. **Small, focused changes.** One concern per commit/PR. Write clear commit messages.
2. **Clean code**: meaningful names, small functions, no dead code, no commented-out blocks, no unused dependencies.
3. **Consistent structure**: follow the folder layout below. Don't invent new top-level folders without recording why in `docs/DECISIONS.md`.
4. **Error handling**: never swallow errors silently. Log meaningfully, show friendly messages to users.
5. **Security**: least privilege, no secrets in code or logs, validate all inputs, keep dependencies updated.
6. **Performance**: lazy-load heavy assets, optimise images, avoid unnecessary re-renders and requests, paginate large lists.
7. **Testing**: add or update tests with every feature or bug fix (unit for logic/validation, integration for data flow, a smoke test for key user journeys).
8. **Linting & formatting**: use the project's linter/formatter; code must pass with zero errors before completion.
9. **Do not guess silently.** If requirements are ambiguous, state assumptions in the PR/response and log them.
10. **Do not break existing behaviour.** Run the existing tests before and after changes.

### Suggested folder structure (adapt to the stack, keep it consistent)

```
/
├── AGENTS.md
├── README.md
├── .env.example
├── .gitignore
├── CHANGELOG.md
├── docs/
│   ├── ARCHITECTURE.md
│   ├── DESIGN_SYSTEM.md
│   ├── API.md
│   ├── DECISIONS.md
│   └── TODO.md
├── src/
│   ├── components/      # reusable UI components
│   ├── pages/ or views/ # route-level screens
│   ├── services/        # data-access layer (APIs, DB, sockets)
│   ├── validation/      # shared schemas
│   ├── hooks/ or utils/ # helpers
│   ├── styles/ or theme/# design tokens, global styles
│   └── config/          # env loading, constants
├── mocks/ or fixtures/  # prototype/test data ONLY
└── tests/
```

---

## 6. Supporting Files: Create What the Project Needs

**Rule:** Agents must create and maintain any additional files required for the project to be understandable, runnable, and maintainable. Do not leave these for "later."

### Create when missing (and keep up to date)

| File | Purpose | When to create |
|---|---|---|
| `README.md` | What the project is, setup, run, build, test, deploy commands, env vars, project structure | At project start |
| `.env.example` | Every required environment variable with placeholder values and comments (never real secrets) | As soon as any env var is introduced |
| `.gitignore` | Exclude `node_modules`, build output, `.env`, logs, OS/editor files | At project start |
| `docs/ARCHITECTURE.md` | Stack, data flow, module responsibilities, diagrams | After the initial structure exists; update on major changes |
| `docs/DESIGN_SYSTEM.md` | Tokens (colours, type, spacing), component list, usage examples | When tokens/components are first defined |
| `docs/API.md` | External APIs/endpoints used or exposed: URLs, params, response shapes, rate limits, auth | When integrating or creating any API |
| `docs/DECISIONS.md` | Short log of technical decisions and assumptions (date, decision, reason) | Whenever a non-obvious choice is made |
| `docs/TODO.md` | Known gaps, static-data placeholders to replace, deferred work | Whenever something is deferred |
| `CHANGELOG.md` | Notable changes per version/date | With every meaningful change |
| Config files (`package.json`/`requirements.txt`, linter, formatter, TS/build configs) | Reproducible builds and consistent code style | At project start or when tooling is added |
| Test config & test files | Automated verification | With the first feature |
| `Dockerfile` / CI workflow (e.g. `.github/workflows/ci.yml`) | Reproducible environment and automated lint + test + build | When the project is deployable or has a team workflow |
| `LICENSE`, `CONTRIBUTING.md`, `SECURITY.md` | Legal and contribution guidance | If the project is shared or open-source |

### File-creation principles
1. **Only create what is useful.** Don't add empty or boilerplate-only files. If a file listed above is not relevant (e.g. no API, so no `API.md`), skip it and note why in `docs/DECISIONS.md`.
2. **Keep docs truthful.** Update documentation in the same change that alters behaviour. Outdated docs are bugs.
3. **Never commit secrets.** Real values live only in local/CI environment settings.
4. **Check before creating.** Search for an existing file or module before adding a new one; extend instead of duplicating.

---

## 7. Definition of Done (Checklist)

A task is **not complete** until every applicable box is true:

**Data**
- [ ] Uses live/dynamic data, or static data is clearly marked as prototype with a TODO
- [ ] Loading, empty, success, and error states are all implemented
- [ ] No hard-coded secrets or URLs; `.env.example` updated

**UI**
- [ ] Uses design tokens and shared components only (no magic values)
- [ ] Verified at mobile, tablet, and desktop widths with no horizontal page scroll
- [ ] Contrast, focus states, labels, and alt text are in place

**Validation**
- [ ] Inputs validated on client **and** server/API boundary via a shared schema
- [ ] Invalid, empty, boundary, and malicious inputs tested
- [ ] Clear inline error messages; user input preserved on error

**Navigation**
- [ ] Menu is consistent and correctly positioned; active page is indicated
- [ ] Key features reachable within two clicks/taps
- [ ] Keyboard navigation works; 404 and error pages exist

**Quality**
- [ ] Linter/formatter pass; tests added and passing
- [ ] No console errors or warnings
- [ ] Supporting files created or updated (README, docs, CHANGELOG, etc.)
- [ ] Assumptions and decisions recorded in `docs/DECISIONS.md`

---

## 8. Reporting Back

When finishing a task, the agent must summarise:
1. **What was built or changed** (brief).
2. **Files created or modified.**
3. **Assumptions made** and any rule that could not be fully met (with the reason).
4. **Follow-ups** added to `docs/TODO.md`.
5. **How to run and verify** the change.

---

*These rules apply to every agent and every task in this repository. When in doubt, favour: live data, consistency, defensive validation, and obvious navigation.*
