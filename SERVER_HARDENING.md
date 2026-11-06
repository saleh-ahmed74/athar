# Server hardening checklist

## Network and access

- [ ] Put Cloudflare in front of the domain; enable SSL/TLS Full (strict), WAF managed rules, Bot Fight Mode where available, and rate limits for login, registration, password reset, search, and contact. Test rules in monitor mode first where possible.
- [ ] Enforce HTTPS and verify the certificate renewal. Configure the exact reverse-proxy IP ranges in `TRUSTED_PROXIES`; do not trust arbitrary forwarded headers from untrusted clients. Confirm the app responds with HSTS only over the production domain.
- [ ] On a VPS, expose only 80/443 and the required SSH port; use SSH keys, disable root login and password authentication, and enable Fail2ban and unattended security updates. Hostinger Single does not provide SSH according to its current plan documentation, so these host-level SSH/firewall controls require a plan with SSH or Hostinger support.
- [ ] Do not expose MySQL publicly. Use a dedicated least-privilege database account scoped to this app.

## Files and secrets

- [ ] Keep the Laravel app root above/outside `public_html`; serve only the Laravel `public/` directory. Verify `/.env`, `/.git/config`, `/vendor/`, `/storage/`, `/composer.json`, and `/artisan` return 403/404.
- [ ] Keep `.env`, backups, logs, and database dumps private. Never place secrets in GitHub, deployment logs, or public directories. Restrict PHP write permissions to `storage/` and `bootstrap/cache/`.
- [ ] Disable directory listing, deny dotfiles, and prevent PHP execution in upload/storage paths. Confirm the live Hostinger web server actually honors the Apache configuration.

## Recovery and monitoring

- [ ] Configure daily encrypted database backups to an off-server location and separately back up uploaded images. Host-provided backups alone are not an offsite backup.
- [ ] Perform a restore test on a separate non-production database at least quarterly and before risky schema changes.
- [ ] Add server-side error monitoring (for example Sentry) only after reviewing its data handling and alert routing. Keep generic errors on public pages and ensure logs do not contain passwords, tokens, or unnecessary personal data.
- [ ] Write an incident plan: restrict/disable the site, preserve logs, rotate `APP_KEY`, database credentials, and all exposed integration keys, rebuild from a known-good release, then restore a verified backup.

## Laravel release controls

- [ ] Keep `APP_DEBUG=false`, `LOG_LEVEL=warning`, secure/HTTP-only cookies, and a correct HTTPS `APP_URL`.
- [ ] Before each `migrate --force`, take and verify a database backup. Never run `migrate:fresh` or rollback against production without an approved maintenance plan.
- [ ] Review dependency advisories monthly and deploy updates only after tests and build pass.
