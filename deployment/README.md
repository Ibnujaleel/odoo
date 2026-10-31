# Brightloop deployment

The app ships as a single Docker image (PHP 8.3 + Apache) with PostgreSQL as the database. Assets are built in a Node stage and Composer dependencies are installed in a separate stage, so the runtime image does not need Node or Composer.

## Required environment

Copy `deployment/env.production.example` into your platform's environment settings and set:

- `APP_KEY` — generate with `php artisan key:generate --show` and paste the `base64:...` value.
- `APP_URL` — the public HTTPS URL of the service.
- `DB_*` — your PostgreSQL connection details (Render Postgres, Railway Postgres or Neon).
- `RUN_MIGRATIONS=true` for the first deploy, or run `php artisan migrate --force` from the platform shell and then set it to `false`.

The container links storage, runs migrations when `RUN_MIGRATIONS=true`, caches config and views, then starts Apache. The health check is `/up`.

## Render

1. Push this repository to GitHub.
2. Create a Blueprint from `deployment/render.yaml`, or create a Web Service with runtime Docker, Dockerfile path `deployment/Dockerfile` and context `.`.
3. Set `APP_KEY` and `APP_URL` when prompted. The Blueprint creates a Render Postgres database and wires the `DB_*` values.
4. After the first deploy, run `php artisan db:seed` once from the Render shell if you want the demo dataset (only for demo environments).

## Railway + Neon

1. Create a Neon Postgres project and copy the connection details. Use the pooled host for the web service.
2. Create a Railway project from this repository. Railway reads `deployment/railway.json`; if it does not, set the builder to Dockerfile and the path to `deployment/Dockerfile`.
3. Add the variables from `deployment/env.production.example`, using the Neon host, database, user and password.
4. Deploy. Railway checks `/up`; migrations run on boot unless `RUN_MIGRATIONS=false`.

## Demo seed and scheduled billing

For a demo deployment, seed once:

```bash
php artisan db:seed --force
```

The nightly billing command is scheduled in `routes/console.php` at 01:00. Run Laravel's scheduler as a separate worker/service:

```bash
php artisan schedule:work
```

On platforms without a persistent scheduler process, add a cron job that runs `php artisan schedule:run` every minute.

## Caveats

- No queue worker is required for the current demo because queued jobs are not used in the critical path. If queue usage grows, run `php artisan queue:work` as a worker and keep `QUEUE_CONNECTION=database`.
- Invoice PDFs need `php artisan storage:link`; the entrypoint does this automatically.
- The image does not run `route:cache` because the app uses closure routes. Config and view caching are enabled.
