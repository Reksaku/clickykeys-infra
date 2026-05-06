<div align="center">

<img src="./web/img/Icon.png" width="10%">

# ClickyKeys Infrastructure &mdash; Docker edition (legacy)

![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white)
![Docker Compose](https://img.shields.io/badge/Docker%20Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white)
![NGINX](https://img.shields.io/badge/NGINX-009639?style=for-the-badge&logo=nginx&logoColor=white)
![PHP](https://img.shields.io/badge/PHP--FPM%208.2-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL%208.4-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Let's Encrypt](https://img.shields.io/badge/Let's%20Encrypt-003A70?style=for-the-badge&logo=letsencrypt&logoColor=white)
![Prometheus](https://img.shields.io/badge/Prometheus-E6522C?style=for-the-badge&logo=prometheus&logoColor=white)
![Grafana](https://img.shields.io/badge/Grafana-F46800?style=for-the-badge&logo=grafana&logoColor=white)
![cAdvisor](https://img.shields.io/badge/cAdvisor-4285F4?style=for-the-badge&logo=google&logoColor=white)
![GitHub Actions](https://img.shields.io/badge/GitHub%20Actions-2088FF?style=for-the-badge&logo=githubactions&logoColor=white)
![Trivy](https://img.shields.io/badge/Trivy-1904DA?style=for-the-badge&logo=aquasecurity&logoColor=white)

**Production-grade, fully containerised infrastructure for the [ClickyKeys](https://github.com/Reksaku/ClickyKeys) web platform.**

*Orchestrated with Docker Compose &middot; Reverse-proxied by nginx-proxy &middot; TLS automated by Let's Encrypt &middot; Observed by Prometheus + Grafana*

</div>

> [!IMPORTANT]
> **This branch is the legacy Docker Compose deployment.** It is preserved as a historical reference of the original setup that powered the ClickyKeys platform.
> The current production deployment lives on the [`kubernetes` branch](https://github.com/Reksaku/clickykeys-infra/tree/kubernetes) &mdash; a fully automated, IaC-driven Kubernetes (k3s) stack provisioned with Terraform and Ansible.

---

## DevOps Portfolio &mdash; At a Glance

This repository is a working DevOps portfolio piece and the original infrastructure that backed the **ClickyKeys** project. It demonstrates an end-to-end, container-native deployment that takes a clean Linux host and produces a running, TLS-secured, monitored web application via a single `docker compose up`. CI/CD is fully wired through GitHub Actions, including container vulnerability scanning and an automatic rollback on failed health checks.

### Technology Stack

<table>
<tr>
<td><b>Containerisation</b></td>
<td><b>Docker</b> &middot; <b>Docker Compose</b> (8 services with healthcheck-based startup ordering)</td>
</tr>
<tr>
<td><b>Web Server / Reverse Proxy</b></td>
<td><b>NGINX</b> (<code>nginx:alpine</code>) for static + FastCGI &middot; <b>nginx-proxy</b> (<code>nginxproxy/nginx-proxy:1.6-alpine</code>) for HTTP/HTTPS termination</td>
</tr>
<tr>
<td><b>Application Runtime</b></td>
<td><b>PHP 8.2-FPM</b> on Alpine, custom Dockerfile with <code>pdo</code> &amp; <code>pdo_mysql</code> extensions</td>
</tr>
<tr>
<td><b>Database</b></td>
<td><b>MySQL 8.4</b> with auto-applied schema via <code>docker-entrypoint-initdb.d</code></td>
</tr>
<tr>
<td><b>TLS / Certificates</b></td>
<td><b>Let's Encrypt</b> via <b>nginxproxy/acme-companion</b> (automatic issuance &amp; renewal)</td>
</tr>
<tr>
<td><b>Monitoring &amp; Observability</b></td>
<td><b>Prometheus v3</b> (30-day retention) &middot; <b>cAdvisor</b> (container metrics) &middot; <b>Grafana 13</b> (dashboards, behind TLS)</td>
</tr>
<tr>
<td><b>CI/CD</b></td>
<td><b>GitHub Actions</b> (lint &rarr; scan &rarr; deploy) with SSH-based remote deploy and automatic rollback on failed health checks</td>
</tr>
<tr>
<td><b>Security Scanning</b></td>
<td><b>Trivy</b> (image vulnerability scanning, blocks on CRITICAL/HIGH CVEs)</td>
</tr>
<tr>
<td><b>Secrets</b></td>
<td><code>.env</code> files (gitignored) with CI-side validation that <code>.env.example</code> never contains real credentials</td>
</tr>
</table>

### Highlights for Recruiters

- **Eight-service Docker Compose stack** wired together with `healthcheck` dependencies, so each service waits for its upstreams to become healthy before starting.
- **Automated TLS** for both the website and the Grafana subdomain via `nginx-proxy` + `acme-companion` &mdash; certificates are issued and renewed without manual intervention.
- **Production-style CI/CD**: every push to `main` runs PHP lint, validates the Compose file, scans the custom PHP image with **Trivy**, then SSH-deploys to the target host and polls a live health-check loop that performs an **automatic rollback** to the previous commit if HTTP 200 is not reached within ~2 minutes.
- **Hardened web tier**: Nginx rate limiting on tracking and API endpoints, plus a full set of security headers (`X-Content-Type-Options`, `X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy`, `X-XSS-Protection`).
- **Privacy-first analytics** baked into the application schema: IPv4 last octet and IPv6 last group are zeroed before storage.
- **End-to-end observability**: Prometheus scrapes cAdvisor and exposes metrics to Grafana; the Grafana datasource is provisioned as code.
- **Real running site.** This stack powered the public ClickyKeys website in production for its full lifecycle on this branch.

---

## Architecture

The stack is composed of **8 services** orchestrated by Docker Compose:

| # | Service | Image | Role |
|---|---|---|---|
| 1 | `website` | `nginx:alpine` | Static file server + FastCGI proxy to PHP-FPM |
| 2 | `php` | `php:8.2-fpm-alpine` (custom build) | Application runtime (PDO/MySQL) |
| 3 | `db` | `mysql:8.4` | Relational database, auto-initialised schema |
| 4 | `proxy` | `nginxproxy/nginx-proxy:1.6-alpine` | Public reverse proxy (HTTP/HTTPS, vhost routing) |
| 5 | `letsencrypt` | `nginxproxy/acme-companion` | Automatic Let's Encrypt issuance &amp; renewal |
| 6 | `cadvisor` | `gcr.io/cadvisor/cadvisor:v0.55.1` | Per-container resource metrics |
| 7 | `prometheus` | `prom/prometheus:v3.11.3` | Metrics storage (30-day retention) |
| 8 | `grafana` | `grafana/grafana:13.1` | Monitoring dashboards (behind TLS) |

```
                       ┌───────────────────────────┐
                       │    Client (browser)       │
                       └─────────────┬─────────────┘
                                     │ HTTPS / 443
                                     ▼
                ┌────────────────────────────────────────┐
                │  nginx-proxy   (TLS termination)       │
                │  acme-companion  (Let's Encrypt auto)  │
                └─────┬───────────────────────┬──────────┘
                      │                       │
            VIRTUAL_HOST                GRAFANA_HOST
                      │                       │
                      ▼                       ▼
           ┌──────────────────┐       ┌──────────────┐
           │ website (nginx)  │       │  grafana     │
           │  + rate limits   │       │              │
           │  + security hdrs │       │              │
           └────────┬─────────┘       └──────┬───────┘
                    │ FastCGI                │
                    ▼                        ▼
           ┌──────────────────┐       ┌──────────────┐
           │ php (php-fpm)    │       │ prometheus   │
           └────────┬─────────┘       └──────┬───────┘
                    │ PDO/MySQL              │ scrape
                    ▼                        ▼
           ┌──────────────────┐       ┌──────────────┐
           │ db (MySQL 8.4)   │       │  cadvisor    │
           └──────────────────┘       └──────────────┘
```

All services share an internal Docker network. Database (`3306`), Prometheus (`9090`) and cAdvisor (`8080`) are bound to `127.0.0.1` only &mdash; access from the outside world goes exclusively through the SSH tunnel or `nginx-proxy`.

---

## Repository Structure

```
.
├── web/                          # Application source (frontend + backend code)
│   ├── index.html                # Main page (EN)
│   ├── pl/index.html             # Polish localisation
│   ├── css/                      # Themes: base, light, neon, terminal, vaporwave
│   ├── js/                       # i18n (en.js, pl.js) + analytics tracker
│   ├── img/                      # Static assets
│   ├── db_php/
│   │   ├── track.php             # Page-view tracking endpoint
│   │   └── event.php             # Click-event tracking endpoint
│   └── api/
│       └── releases.php          # Release data API
│
├── nginx/
│   └── default.conf              # Nginx server config: rate limits + security headers
│
├── php-fpm/
│   ├── dockerfile                # PHP 8.2-FPM-Alpine + PDO MySQL + fcgi
│   └── zz-healthcheck.conf       # PHP-FPM ping/pong endpoint for healthcheck
│
├── mysql-init/
│   └── 01-schema.sql             # Auto-applied schema (4 tables)
│
├── monitoring/
│   ├── prometheus.yml            # Prometheus scrape config
│   └── grafana/provisioning/     # Grafana datasource provisioning (as code)
│
├── .github/workflows/
│   └── deploy.yml                # CI/CD: test → scan_image → deploy (with rollback)
│
├── docker-compose.yml            # 8-service stack with healthcheck dependencies
├── .env.example                  # ← copy to .env and fill in
└── README.md
```

---

## Setup &amp; Deployment

### Prerequisites

- **Docker Engine** &ge; 24.0
- **Docker Compose v2** (bundled with recent Docker Engine releases)
- A Linux host with public ports **80** and **443** reachable (any VPS / dedicated server / Oracle Cloud / etc.)
- A registered domain with two A records pointing at the host (one for the site, one for the Grafana subdomain)
- An SMTP-reachable email address for Let's Encrypt registration

### 1. Clone the repository

```bash
git clone https://github.com/Reksaku/clickykeys-infra.git
cd clickykeys-infra
```

### 2. Configure environment variables

All configuration is supplied through a `.env` file (gitignored). Start from the provided template:

```bash
cp .env.example .env
```

Edit `.env` with your real values:

```env
HOST=clickykeys                   # Container name prefix

VIRTUAL_HOST=clickykeys.example.com   # Public domain for the website
LETSENCRYPT_HOST=clickykeys.example.com
LETSENCRYPT_EMAIL=you@example.com

MYSQL_HOST=db
MYSQL_DATABASE=clickykeys
MYSQL_ROOT_PASSWORD=<strong-root-password>
MYSQL_USER=clickykeys
MYSQL_PASSWORD=<strong-app-password>

GRAFANA_USER=admin
GRAFANA_PASSWORD=<strong-grafana-password>
GRAFANA_HOST=monitoring.example.com   # Public domain for the Grafana UI
```

### 3. Build and start the stack

```bash
docker compose up --build -d
```

Startup order is enforced through `healthcheck` dependencies:

```
db ──► php ──► website ──┐
                         ├──► proxy ──► letsencrypt
cadvisor ──► prometheus ─┤
                          └──► grafana
```

### 4. Verify the deployment

```bash
docker compose ps
docker compose logs -f letsencrypt   # watch for ACME certificate issuance
curl -I https://<your-domain>
```

The site will be live at `https://<VIRTUAL_HOST>` once `acme-companion` finishes the HTTP-01 ACME challenge (typically under 1 minute). Grafana will be reachable at `https://<GRAFANA_HOST>` using the admin credentials supplied in `.env`.

### 5. Local-only access to internal services

Database, Prometheus, and cAdvisor are bound to `127.0.0.1` on the host. To inspect them from your laptop, tunnel through SSH:

```bash
ssh -L 3306:127.0.0.1:3306 \
    -L 9090:127.0.0.1:9090 \
    -L 8080:127.0.0.1:8080 \
    user@<host>
```

---

## Configuration Reference

### Environment variables &mdash; `.env`

| Variable | Description |
|---|---|
| `HOST` | Container name prefix (e.g. `clickykeys` &rarr; `clickykeys-website`, `clickykeys-php`, &hellip;) |
| `VIRTUAL_HOST` | Public domain handled by `nginx-proxy` for the website |
| `LETSENCRYPT_HOST` | Domain for which `acme-companion` will request a certificate (usually equal to `VIRTUAL_HOST`) |
| `LETSENCRYPT_EMAIL` | Contact email used to register with Let's Encrypt |
| `MYSQL_HOST` | Hostname of the database service (the Compose service name `db`) |
| `MYSQL_DATABASE` | Application database name |
| `MYSQL_ROOT_PASSWORD` | MySQL root password |
| `MYSQL_USER` | Application database user |
| `MYSQL_PASSWORD` | Application database password |
| `GRAFANA_USER` | Grafana admin username |
| `GRAFANA_PASSWORD` | Grafana admin password |
| `GRAFANA_HOST` | Public domain for the Grafana UI (also TLS-terminated by `nginx-proxy`) |

### Database schema &mdash; `mysql-init/01-schema.sql`

Applied automatically on first start (when the MySQL data volume is empty). Four tables:

| Table | Purpose |
|---|---|
| `page_views` | Anonymous visit tracking &mdash; device, browser, OS, viewport, load time, path, referrer |
| `click_events` | UI interaction events linked to a page view (element ID, label, type, JSON extras) |
| `release_library` | ClickyKeys application release registry (release ID, safety signature, date) |
| `api_requests` | API call log (path, client type, version, distribution, anonymised IP) |

> IP addresses are anonymised before storage: the last IPv4 octet / last IPv6 group is zeroed.

---

## Security

- **TLS everywhere.** Public traffic is terminated at `nginx-proxy`; certificates are issued and renewed automatically by `acme-companion` via Let's Encrypt.
- **Rate limiting in Nginx**:
  - Tracking endpoints (`/db_php/`): 10 req/min per IP, burst 5, response code `429` on overflow
  - API endpoints (`/api/`): 30 req/min per IP, burst 10
- **Security headers** sent on every response: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin`, `Permissions-Policy`, `X-XSS-Protection`.
- **Image scanning** with Trivy on every push to `main` &mdash; the deploy job is **blocked** if any CRITICAL or HIGH (fixed) CVE is found in the custom PHP image.
- **Secrets validation** in CI &mdash; the pipeline fails if `.env.example` ever contains a value that looks like a real password / secret / key.
- **Database is not exposed publicly.** MySQL is bound to `127.0.0.1:3306` on the host.
- **Monitoring is not exposed publicly.** Prometheus (`:9090`) and cAdvisor (`:8080`) bind to `127.0.0.1` only; only Grafana is reachable from the internet, behind TLS and admin credentials.
- **No secrets in version control.** `.gitignore` excludes the `.env` file; only `.env.example` (with `change_me` placeholders) is committed.

---

## CI/CD Pipeline

Defined in `.github/workflows/deploy.yml` &mdash; triggered on every push to `main`.

**Stage 1 &mdash; `test`**

- `php -l` syntax check on every `.php` file under `web/`
- `docker compose config --quiet` validates the Compose file
- A regex check that fails the build if `.env.example` contains real-looking secrets

**Stage 2 &mdash; `scan_image`** *(needs `test`)*

- Builds the custom PHP-FPM image
- Scans it with **Trivy** for CRITICAL and HIGH vulnerabilities (`ignore-unfixed: true`)
- Fails the build on any matching CVE

**Stage 3 &mdash; `deploy`** *(needs `scan_image`)*

- SSH into the production host (`appleboy/ssh-action`)
- Records the current commit as a **rollback point**
- `git pull origin main` &rarr; `docker compose up -d --build` &rarr; `docker image prune -f`
- **Health-check loop**: 12 attempts &times; 10 s pause, polling the live domain for HTTP 200
- On failure: **automatic rollback** &mdash; `git reset --hard <previous-commit>` and rebuild

```
push to main ──► test ──► scan_image (Trivy) ──► deploy ──┬─ healthcheck OK ─► done
                                                          │
                                                          └─ healthcheck FAIL ─► rollback
```

---

## Branch Status &mdash; `main` (legacy) vs `kubernetes` (current)

| Aspect | `main` (Docker Compose, **legacy** &mdash; this branch) | `kubernetes` (k3s, **current production**) |
|---|---|---|
| Orchestration | Docker Compose | Kubernetes (k3s) |
| Provisioning | Manual | Terraform (AWS EC2) |
| Configuration | `.env` file, manual `docker compose up` | Ansible (fully automated, idempotent roles) |
| Ingress / proxy | `nginx-proxy` container | `ingress-nginx` via Helm |
| TLS | Let's Encrypt via `acme-companion` | `cert-manager` (automatic renewal) |
| Scaling | `docker compose scale` | `kubectl scale` / replica count |
| Secrets | `.env` files | Ansible Vault + Kubernetes `Secret` resources |
| Observability | Prometheus + cAdvisor + Grafana (in-stack) | Cluster-native (k8s metrics, future Prometheus Operator) |
| Portability | Any Docker host | Any Kubernetes / k3s cluster |

> The `kubernetes` branch has been promoted to production. This `main` branch remains in the repository as a **legacy** reference of the original Docker Compose setup that powered ClickyKeys.

---

## Related Projects

- **ClickyKeys production page** &mdash; [clickykeys.fun](https://clickykeys.fun)
- **ClickyKeys application** &mdash; [GitHub](https://github.com/Reksaku/ClickyKeys) &middot; [Microsoft Store](https://apps.microsoft.com/store/detail/9PJT83WPC06K?cid=DevShareMCLPCS)
- **ClickyKeys infrastructure (current, Kubernetes)** &mdash; [`kubernetes` branch](https://github.com/Reksaku/clickykeys-infra/tree/kubernetes)

---

## Author

**Mateusz Wyrzykowski** &mdash; [github.com/Reksaku](https://github.com/Reksaku)

---

## License

This project is part of a personal portfolio.

*This README was co-authored with Claude (Anthropic).*
