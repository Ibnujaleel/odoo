# Brightloop UI guide

Use the shared components in `resources/views/components/ui` and Tailwind tokens throughout the authenticated application.

## Public landing page

The public welcome page uses a page-scoped style layer in `resources/css/app.css` under `.landing-page`. Its blue, navy, neutral, spacing, typography, radius, and shadow tokens keep the Brightloop marketing palette isolated from the existing indigo application theme. Outfit is used for landing-page headings and Inter remains the body font. The CRM loop illustration is decorative and does not present sample customer data.

- `<x-ui.page-header title="..." subtitle="...">` with an optional `actions` slot for the primary action.
- `<x-ui.stat-card label="..." value="..." hint="..." tone="primary|success|warning|danger" />` for dashboard metrics.
- `<x-ui.card title="..." subtitle="...">` for grouped content; `actions` and `footer` slots are available.
- `<x-ui.status-badge :status="$model->status" />` for every subscription/invoice/payment status.
- `<x-ui.empty-state title="..." description="...">` when a list has no rows.
- `<x-ui.form-section title="..." description="...">` to split long forms into readable sections.
- `<x-ui.data-table :paginator="$rows">` wraps responsive tables, filters and pagination.
- Authenticated application colours: primary=indigo, success=emerald, warning=amber, danger=rose, neutrals=slate.
- All layouts must work at 375px; tables scroll inside their wrapper, not the page.
