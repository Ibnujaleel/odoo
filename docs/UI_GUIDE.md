# Brightloop UI guide

Use the shared components in `resources/views/components/ui` and Tailwind tokens only.

- `<x-ui.page-header title="..." subtitle="...">` with an optional `actions` slot for the primary action.
- `<x-ui.stat-card label="..." value="..." hint="..." tone="primary|success|warning|danger" />` for dashboard metrics.
- `<x-ui.card title="..." subtitle="...">` for grouped content; `actions` and `footer` slots are available.
- `<x-ui.status-badge :status="$model->status" />` for every subscription/invoice/payment status.
- `<x-ui.empty-state title="..." description="...">` when a list has no rows.
- `<x-ui.form-section title="..." description="...">` to split long forms into readable sections.
- `<x-ui.data-table :paginator="$rows">` wraps responsive tables, filters and pagination.
- Colours: primary=indigo, success=emerald, warning=amber, danger=rose, neutrals=slate. Never hard-code hex values.
- All layouts must work at 375px; tables scroll inside their wrapper, not the page.
