# Athar

![Athar logo](public/images/athar-logo.png)

Athar is a bilingual (Arabic and English) storefront and lightweight store-management application built with Laravel. Customers can browse products, manage a cart, and place cash-on-delivery orders. Store staff can manage the catalog, orders, customer messages, and storefront settings.

## Features

- Arabic and English storefront with localized product and category content.
- Product catalog with categories, search, filters, featured products, galleries, and archive support.
- Shopping cart with offline cart recovery and cached public storefront pages.
- Cash-on-delivery checkout with configurable delivery zones, order history, and optional stock tracking.
- Customer contact form and order-management tools.
- Admin screens for products, categories, orders, messages, store settings, and WhatsApp message templates.
- Account registration, sign-in, password reset, and profile settings.
- Image uploads are validated and converted to WebP.
- SEO-friendly robots and sitemap routes, request throttling, and security response headers.

## Technology

- PHP 8.2 or newer
- Laravel 12
- Livewire and Volt
- MySQL (recommended for deployment; the test suite uses in-memory SQLite)
- Node.js 20 or newer and npm
- Vite and Tailwind CSS 4
- GD with WebP support for image processing

## Getting started

### Requirements

Install PHP 8.2+, Composer, Node.js 20+, and a MySQL-compatible database. Enable the PHP extensions required by Laravel and the application, including PDO MySQL, mbstring, fileinfo, OpenSSL, ctype, tokenizer, XML, and GD with WebP support.

### Install dependencies

```sh
composer install
npm ci
```

Copy the example environment file and generate the application key:

```sh
cp .env.example .env
php artisan key:generate
```

On Windows PowerShell, use `Copy-Item .env.example .env` for the first command.

Update `.env` with your local database settings, then create the schema and the default store settings:

```sh
php artisan migrate --seed
php artisan storage:link
npm run build
```

Start the local development server:

```sh
php artisan serve
```

Open the URL printed by Artisan. For frontend development with hot reload, use `npm run dev` in another terminal.

> `DatabaseSeeder` creates starter categories and store settings. Demo catalog data is separate and restricted to the `local` and `testing` environments; load it with `php artisan db:seed --class=DemoDataSeeder` only in a disposable development database.

## Administrator access

Register a user through the application, then grant administrator access from a trusted command line in the app environment:

```sh
php artisan athar:make-admin user@example.com
```

Replace the example address with the registered user's email. This command grants access to the admin area; protect access to the server console and never expose a public endpoint for running Artisan commands.

The admin URL defaults to `/admin`. Set `ADMIN_PATH` to a different path if needed. This is an additional routing choice, not a replacement for authentication and authorization.

## Useful commands

```sh
# Run the test suite
php artisan test --compact

# Build frontend assets for release
npm run build

# Format PHP files
vendor/bin/pint --format agent

# Inspect application routes and migration status
php artisan route:list
php artisan migrate:status
```

The PHPUnit configuration runs tests against SQLite in memory. Tests do not require production credentials.

## Configuration

Start from `.env.example` for local development. For production, create a private `.env` from `.env.production.example` and supply real values for at least:

- `APP_ENV=production`, `APP_DEBUG=false`, a unique `APP_KEY`, and the exact HTTPS `APP_URL`.
- Database name, host, username, and password for a dedicated least-privilege database user.
- `TRUSTED_PROXIES` only when the deployment uses a proxy, with the actual trusted proxy addresses.
- `SESSION_SECURE_COOKIE=true` and the other secure cookie settings shown in the production example.
- A real mail transport if password-reset emails must be delivered.

Do not commit `.env`, credentials, database backups, or uploaded customer data. Do not use demo seeders with production data.

## Orders and integrations

Checkout currently supports cash on delivery. There is no online payment integration, external email feature, or required background queue worker configured by this project. Password reset uses Laravel's notification flow and needs a production mail transport to reach users.

## GitHub workflow

Use `develop` for ongoing changes and open a pull request into `main` for a release. GitHub Actions runs the test, asset-build, and PHP-format workflows on pushes and pull requests to either branch. Before running CI, add the repository secrets `FLUX_USERNAME` and `FLUX_LICENSE_KEY` under the `Testing` environment so Composer can install the licensed Flux dependency.

The repository does not contain an automatic production deployment yet. Connect production only after confirming the host's supported deployment method, document root, and required credentials. Until then, release from `main` by following [DEPLOYMENT.md](DEPLOYMENT.md).

## Deployment and security

See [DEPLOYMENT.md](DEPLOYMENT.md) for release requirements and hosting guidance, and [SERVER_HARDENING.md](SERVER_HARDENING.md) for the server and application hardening checklist.

Serve only Laravel's `public/` directory from the web root. Keep `.env`, `vendor/`, `storage/`, `composer.json`, and `artisan` outside the public document root. Back up the database and verify the backup can be restored before applying production migrations. Never run `migrate:fresh` against production.

## Project structure

```text
app/                 Laravel application code, Livewire screens, middleware, and support classes
bootstrap/           Application bootstrap
config/              Application configuration
database/            Migrations, factories, and seeders
resources/            Blade views and frontend source
routes/               Web and authentication routes
tests/                Pest feature and unit tests
public/               Web root, built assets, images, and service worker
```


