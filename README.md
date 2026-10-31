# Brightloop Subscription Management

Subscription lifecycle, recurring billing and finance reporting for growing teams.

- **Admin/staff panel:** `/admin` (Filament 3)
- **Customer portal:** `/portal` (Livewire + Tailwind)
- **Stack:** Laravel 11, PHP 8.3, PostgreSQL, Eloquent, Filament, Spatie Permission, Dompdf, Pest.

## Local setup (Laravel Sail)

```bash
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail npm install && ./vendor/bin/sail npm run build
```

Open `http://localhost`. The admin panel is at `http://localhost/admin` and the customer portal at `http://localhost/portal`.

## Demo logins

| Role | Email | Password |
| --- | --- | --- |
| Admin | admin@brightloop.test | Brightloop@123 |
| Staff | staff@brightloop.test | Brightloop@123 |
| Customer | customer@brightloop.test | Brightloop@123 |

Customers are redirected to `/portal`; admin and staff are redirected to `/admin`.

## Useful commands

```bash
./vendor/bin/sail artisan migrate:fresh --seed   # reset + 3000-row demo dataset
./vendor/bin/pint                                # format
./vendor/bin/pest                                # tests
./vendor/bin/sail artisan schedule:work          # run the daily billing job locally
```

## Branches and merge schedule

| Dev | Branch | Area |
| --- | --- | --- |
| Dev 1 | `feature/dev1-foundation-shell` | schema, models, auth, RBAC, shared UI, dashboard, integration |
| Dev 2 | `feature/dev2-catalogue-billing` | catalogue, plans, tax, discounts, billing engine |
| Dev 3 | `feature/dev3-sales-portal` | customers, quotations, subscriptions, customer portal |
| Dev 4 | `feature/dev4-finance` | invoices, payments, finance reporting |

Branch from `develop`, one feature branch per developer, open small PRs into `develop` at every milestone. Another teammate reviews; check owned paths only, `pint` and `pest`, and the screen. Run `git fetch origin && git rebase origin/develop` after every merge. `main` is updated only by Dev 1 from `develop` at the end of the day.

## Demo data

`migrate:fresh --seed` creates roles and three demo users, the catalogue needed by the demo, 3,000 customers, 3,000 subscriptions with lines, 2,500 invoices, and 1,800 payments. The bulk seeder uses chunked inserts so it finishes in a couple of seconds.

## Deployment

Deployment files live in `deployment/`. See `deployment/README.md` for the Docker/Render or Railway + Neon Postgres instructions.
