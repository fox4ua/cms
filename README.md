# CI4 CMS v14 — production hardening

Production-oriented modular CMS foundation for CodeIgniter 4.

```text
cms/system   — system modules supplied with the CMS
cms/modules  — project and third-party modules
```

CLI commands are intentionally not included in this release.

## System modules

- `Kernel` — bootstrap, contracts, admin shell, security and infrastructure services.
- `Installer` — one-time protected browser installer.
- `Auth` — login, sessions, remember-me, password policy, IP rules and extension hooks.
- `ModuleManager` — module discovery, preflight, install/update/enable/disable, locks and operation logs.
- `RouteManager` — database-backed routes and route administration.
- `Menu` — universal menus for admin and frontend areas.
- `Settings` — typed system settings, including HTTP security headers.
- `Dashboard` — administration start page.
- `AuditLog` — action and suspicious-event journals.
- `SystemHealth` — liveness, readiness and production-readiness checks.
- `Maintenance` — orchestration of maintenance providers exposed by modules.

## Added in v14

- protected web installation into a completely empty database;
- no default administrator credentials;
- atomic `.env` writing and one-time installer shutdown;
- failed-install cleanup for MySQL/MariaDB DDL;
- trusted proxy/CIDR support for IPv4 and IPv6;
- forwarded headers are accepted only from configured proxies;
- global and route-specific file-backed rate limiting;
- `/health` liveness and `/ready` readiness probes;
- optional detailed readiness output protected by `X-CMS-Probe-Token`;
- module operation journal and hardened expiring locks;
- stale-lock cleanup and controlled force unlock from the admin panel;
- editable CSP, HSTS, Referrer-Policy, Permissions-Policy and frame policy;
- secret and production-environment checks in `SystemHealth`;
- schema version tracking and upgrade-chain validation;
- PHPUnit-compatible unit tests and a mandatory release test plan.

## Install in a new project

1. Copy `cms`, `sql`, `public/assets/admin` and the required `app/Config` snippets into the CodeIgniter project.
2. Add the PSR-4 mapping shown in `app/Config/Autoload_append.php`.
3. Include the routes from `app/Config/Routes_append.php` in the project routes file.
4. Create a completely empty MySQL/MariaDB database and a dedicated database user.
5. Copy `.env.example` to `.env` and temporarily set:

```ini
CMS_INSTALLER_ENABLED = true
app.baseURL = "https://your-host.example/public/"
CMS_ALLOWED_HOSTS = "your-host.example"
```

6. Open `/install` and create the first administrator.
7. The installer creates random `CMS_APP_KEY`, `cms.auth.pepper` and `CMS_PROBE_TOKEN`, then writes `CMS_INSTALLER_ENABLED=false`.
8. Remove write permission from `.env` for the web-server user where the hosting layout permits it.
9. Open `/admin/system/health` and resolve every failed production check.

The installer requires a fully empty database. It does not overwrite or merge an existing schema.

## Upgrade an existing v13 installation

1. Make a site/database backup using the hosting panel or server tooling.
2. Copy the new application files.
3. Apply root update files in semantic version order. For v13 → v14, apply:

```text
sql/updates/1.4.0-production-hardening.sql
```

4. Open `/admin/modules`, synchronize manifests and apply pending module updates.
5. Verify `/admin/system/health` and `/ready`.

CMS does not create hosting-level backups. Its responsibility is preflight validation, operation locking, SQL safety checks, update history and diagnostics.

## Reverse proxy configuration

Direct deployment:

```ini
CMS_TRUSTED_PROXIES = ""
```

Deployment behind trusted Nginx/HAProxy/Traefik/Cloudflare hops:

```ini
CMS_TRUSTED_PROXIES = "10.0.0.0/8,192.168.50.10,2001:db8:1234::/48"
CMS_TRUSTED_PROXY_HEADERS = "forwarded,x-forwarded-for,x-real-ip,cf-connecting-ip,x-forwarded-proto"
```

Never add untrusted public networks to `CMS_TRUSTED_PROXIES`. Forwarded client IP and HTTPS headers are ignored unless the immediate peer is trusted.

## Health probes

```text
GET /health — liveness, no database dependency
GET /ready  — readiness, returns 200 or 503
```

Public readiness output is intentionally minimal. Detailed checks are returned only when the request contains:

```http
X-CMS-Probe-Token: <CMS_PROBE_TOKEN>
```

Do not put the probe token in a query string.

## Rate limits

Limits are configured in `.env`:

```ini
CMS_RATE_GLOBAL_MAX = 300
CMS_RATE_GLOBAL_WINDOW = 60
CMS_RATE_LOGIN_MAX = 10
CMS_RATE_LOGIN_WINDOW = 60
CMS_RATE_SENSITIVE_MAX = 30
CMS_RATE_SENSITIVE_WINDOW = 60
```

The storage directory is `writable/cache/rate-limit`. It must be writable and must not be publicly accessible.

## Production requirements

- PHP 8.2 or newer;
- MySQL/MariaDB with InnoDB and `utf8mb4`;
- HTTPS;
- `CI_ENVIRONMENT=production`;
- disabled PHP `display_errors`;
- writable `writable/cache`, `writable/logs`, `writable/session`;
- web root pointed to `public` where possible;
- when the project root is exposed, apply the deny rules from `docs/nginx-security.conf.example` or `docs/apache-security.conf.example`;
- `.env`, `cms`, `sql`, `tests` and `writable` inaccessible from the public web root;
- server/panel backups and monitoring configured outside the CMS.

## Security notes

- Keep system modules under `cms/system` read-only for the web-server user after deployment.
- Do not expose detailed readiness without a strong probe token.
- HSTS preload must only be enabled after the whole domain and required subdomains are permanently HTTPS-ready.
- CSP changes are validated for header injection, but policy changes still require browser testing.
- RBAC, CAPTCHA and 2FA are intentionally outside the scope of this release.

## Tests

See `tests/README.md`. PHP syntax and static release checks must run before every deployment. Database tests must use a disposable isolated schema.
