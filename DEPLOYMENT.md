# Athar deployment guide

## Runtime requirements

This app requires PHP 8.2 or newer (Laravel 12), the PHP extensions required by `composer.json` and Laravel, and GD with WebP support for product and store images. It uses Vite; compile assets before upload. Set the hosting PHP version to 8.2+ and verify the PHP used by any command runner separately. `vendor/` must be built with the locked dependencies and shipped with the release.

## Before every release

1. Confirm the target branch and review the commit. Keep the production environment file and credentials outside Git.
2. Back up the live database and important uploaded files. **Take a DB backup before every `migrate --force`.** Verify the backup can be restored before relying on it.
3. Build a release locally or in CI with PHP 8.2+ and Node.js 20+:

   ```sh
   composer install --no-dev --optimize-autoloader
   npm ci
   npm run build
   ```

4. Upload the Laravel application to a directory outside the document root, for example `~/domains/your-domain.com/athar_app/`. Put only the contents of Laravel's `public/` directory in the domain document root. Do not put `.env`, `.git`, `storage`, `vendor`, `composer.json`, or `artisan` under the public web root.
5. Update the public `index.php` paths to point to `../athar_app/vendor/autoload.php` and `../athar_app/bootstrap/app.php` (adjust the relative path for the actual directory layout). Keep the app's own `public/.htaccess` in the document root.
6. Create the MySQL database and a dedicated user in hPanel. Grant that user access only to this database. Copy `.env.production.example` to the application root as `.env`, fill in real values in the hosting file manager, and generate a unique `APP_KEY` once. Never upload the example as `.env` or commit a populated `.env`. Use the exact `APP_URL`, DB credentials, and actual trusted proxy addresses.
7. Set PHP to 8.2 or newer and enable the required extensions (including PDO MySQL, mbstring, fileinfo, OpenSSL, ctype, tokenizer, XML, GD/WebP, and Intl if present). Confirm PHP's upload limit is at least 5 MB plus request overhead.
8. In the deployment command runner, run these commands from the application root in order. First confirm the configured DB name is the production database and the backup is complete:

   ```sh
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan event:cache
   php artisan storage:link
   ```

   The migration command changes the production schema. Never run `migrate:fresh` against production. This project currently has no Laravel API routes, payment provider integration, queued background worker requirement, or external email feature; password reset remains the framework's existing notification flow and requires an email transport if you want that flow to deliver messages.
9. Ensure the app root, `storage/`, and `bootstrap/cache/` are writable by the PHP process; use the narrowest permissions the panel supports. Avoid `777`. Check `storage/logs/laravel.log` access is private.
10. Visit the home, shop, category, product, checkout, contact, login, and admin screens. Test the actual HTTPS certificate, one image upload, order creation, and a backup restore plan. Verify `https://your-domain.com/.env`, `/.git/config`, `/vendor/`, `/storage/`, `/composer.json`, and `/artisan` all return 403/404.

## Hostinger Single Web Hosting constraints

Hostinger's current help pages say SSH is available on Premium Web and higher, not Single. Node.js application hosting is listed for Business Web and Cloud plans, not Single. This Laravel app can still serve PHP on Single, but perform `npm ci` and `npm run build` on your own machine/CI and upload `public/build/` plus the complete PHP `vendor/` tree. Single permits at most two cron jobs. Use hPanel's custom cron runner only if it is available in your account to run one-time Artisan commands; schedule it once, inspect its output, then remove it. Do not create a public web route that runs Artisan. If the Single account does not expose a safe command runner or permit the storage symlink, a custom Laravel deployment cannot complete safely on that plan; upgrade to a plan with SSH/Composer access or use Hostinger's managed Laravel deployment option.

Hostinger's Git integration can deploy a selected GitHub branch for custom PHP/HTML websites, but its default target is `public_html`. Choose a non-public application directory if the panel allows it, then publish only `public/` into the document root and adjust `index.php`. Confirm the destination layout before connecting auto-deploy: deployments can overwrite files in their target directory. Do not point the Git integration at a populated directory without a backup.

## Apache and Nginx hardening snippets

The document root must be Laravel's `public/` directory. For Apache, keep `Options -Indexes` and deny dotfiles; configure this in the vhost or the root `.htaccess`:

```apache
Options -Indexes
<FilesMatch "^\.">
    Require all denied
</FilesMatch>
<FilesMatch "^(artisan|composer\.(json|lock))$">
    Require all denied
</FilesMatch>
```

Do not expose `storage/` or `vendor/` in the document root. If the host requires a public storage link, ensure uploads are re-encoded images and PHP execution is disabled in the target. Example Nginx rules (for a host that allows server configuration):

```nginx
autoindex off;
location ~ /\.(?!well-known).* { deny all; }
location ~* ^/(vendor|storage)(/|$) { deny all; }
location ~* ^/(artisan|composer\.(json|lock))$ { deny all; }
location ~* ^/storage/.*\.php$ { deny all; }
```

Shared hosting may not permit Nginx configuration changes. In that case use hPanel's supported document-root and Apache controls, and verify the URLs above from an external browser.

## Routine maintenance

Monthly, review `composer outdated`, `composer audit`, and `npm audit`; update lockfiles in a development branch, run the full test suite and build, then deploy through a reviewed production branch. Review PHP support dates and Hostinger plan capabilities before changing the minimum runtime.
