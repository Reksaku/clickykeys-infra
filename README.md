
<div align="center">
<img src="./web/img/Icon.png" width="10%" > 

# [ClickyKeys](https://github.com/Reksaku/ClickyKeys) Infrastructure
</div>

Infrastructure repository for the **ClickyKeys** web platform.

This project contains the complete Dockerized environment used to run and deploy the ClickyKeys website. 

 ⚙️ Portfolio Project – DevOps / Full Stack Infrastructure 
 ### 🔀 Also available: [k8s branch](https://github.com/Reksaku/clickykeys-infra/tree/k8s) — fully automated Kubernetes deployment with Terraform + Ansible + cert-manager (work in progress)

---

## 📌 Overview

`clickykeys-infra` provides a fully containerized web stack including:

- 🐳 Docker Compose orchestration (8 services)
- 🌐 Nginx reverse proxy with automatic SSL (Let's Encrypt)
- 🐘 PHP 8.2-FPM backend with PDO/MySQL
- 🗄️ MySQL 8.4 with auto-initialized schema
- 📊 Monitoring stack: Prometheus + Grafana + cAdvisor
- 🔒 Nginx rate limiting and security headers
- 🚀 GitHub Actions CI/CD pipeline with automatic rollback

The goal of this repository is to maintain a clean, reproducible, and portable infrastructure setup for the ClickyKeys ecosystem.

---

## 🏛 Architecture

The stack consists of 8 Docker services:

| Service | Image | Role |
|---|---|---|
| `website` | `nginx:alpine` | Static file server + PHP-FPM proxy |
| `php` | `php:8.2-fpm-alpine` (custom build) | PHP application runtime |
| `db` | `mysql:8.4` | Relational database |
| `proxy` | `nginxproxy/nginx-proxy:1.6-alpine` | Reverse proxy (HTTP/HTTPS) |
| `letsencrypt` | `nginxproxy/acme-companion` | Automatic SSL certificate management |
| `cadvisor` | `gcr.io/cadvisor/cadvisor:v0.55.1` | Container resource metrics |
| `prometheus` | `prom/prometheus:v3.11.3` | Metrics storage (30-day retention) |
| `grafana` | `grafana/grafana:13.1` | Monitoring dashboards |

Architecture flow:

```
Client → nginx-proxy (80/443 + SSL termination) → website (Nginx) → php-fpm → MySQL
                                                 ↘ Grafana (monitoring subdomain)
cAdvisor → Prometheus → Grafana
```

All services communicate via internal Docker networking. Prometheus and cAdvisor are exposed only on `127.0.0.1` (SSH tunnel access). The database binds to `127.0.0.1:3306` only.

---

## 📁 Repository Structure

```
.
├── web/                        # Application source files
│   ├── index.html              # Main page (EN)
│   ├── pl/index.html           # Polish language version
│   ├── css/                    # Themes: base, light, neon, terminal, vaporwave
│   ├── js/                     # i18n system (en.js, pl.js) + analytics tracker
│   ├── img/                    # Static assets
│   ├── db_php/
│   │   ├── track.php           # Page view tracking endpoint
│   │   └── event.php           # Click event tracking endpoint
│   └── api/
│       └── releases.php        # Release data API
├── nginx/
│   └── default.conf            # Nginx config with rate limiting & security headers
├── php-fpm/
│   ├── dockerfile              # PHP 8.2-FPM-Alpine + PDO MySQL
│   └── zz-healthcheck.conf     # PHP-FPM ping/pong healthcheck
├── mysql-init/
│   └── 01-schema.sql           # Auto-applied DB schema (4 tables)
├── monitoring/
│   ├── prometheus.yml          # Prometheus scrape config
│   └── grafana/provisioning/   # Grafana datasource provisioning
├── .github/workflows/
│   └── deploy.yml              # CI/CD: test → scan → deploy
├── docker-compose.yml
├── .env.example
└── README.md
```

---

## 🛠 Technologies Used

- **Docker** & **Docker Compose**
- **Nginx** (static serving, rate limiting, security headers)
- **PHP 8.2-FPM** with PDO
- **MySQL 8.4**
- **nginxproxy/nginx-proxy** + **acme-companion** (automatic Let's Encrypt SSL)
- **Prometheus v3** + **cAdvisor** + **Grafana 13**
- **GitHub Actions** (CI/CD)
- **Trivy** (container vulnerability scanning)

---

## 🚀 Local Development Setup

### 1. Clone the repository

```bash
git clone https://github.com/Reksaku/clickykeys-infra.git
cd clickykeys-infra
```

### 2. Configure environment variables

```bash
cp .env.example .env
```

Edit the `.env` file with your values (see [Environment Configuration](#-environment-configuration) below).

### 3. Build and start containers

```bash
docker compose up --build -d
```

The startup order is enforced via `healthcheck` dependencies:
`db` → `php` → `website` and `proxy` → `letsencrypt`, `cadvisor` → `prometheus` → `grafana`.

---

## 🔧 Environment Configuration

All configuration is managed through the `.env` file (never committed to source control).

```env
HOST=change_me                    # Container name prefix

VIRTUAL_HOST=change_me            # Domain for the website
LETSENCRYPT_HOST=change_me        # Domain for SSL certificate
LETSENCRYPT_EMAIL=change_me       # Email for Let's Encrypt notifications

MYSQL_HOST=change_me              # DB host (service name: db)
MYSQL_DATABASE=change_me
MYSQL_ROOT_PASSWORD=change_me
MYSQL_USER=change_me
MYSQL_PASSWORD=change_me

GRAFANA_USER=change_me
GRAFANA_PASSWORD=change_me
GRAFANA_HOST=change_me            # Domain for Grafana monitoring UI
```

---

## 🗄️ Database Schema

MySQL is initialized automatically via `mysql-init/01-schema.sql` on first start. The schema includes four tables:

- **`page_views`** – anonymous website visit tracking (device, browser, OS, viewport, load time, path, referrer)
- **`click_events`** – user interaction events linked to page views (element ID, label, event type, JSON extras)
- **`release_library`** – ClickyKeys application release registry (ID, safety signature, release date)
- **`api_requests`** – API call logging (path, client type, version, distribution, anonymized IP)

IP addresses are anonymized before storage (last IPv4 octet zeroed / last IPv6 group zeroed).

---

## 🔒 Security

- **Rate limiting** in Nginx:
  - Tracking endpoints (`/db_php/`): 10 req/min per IP, burst 5
  - API endpoints (`/api/`): 30 req/min per IP, burst 10
- **Security headers** on every response: `X-Content-Type-Options`, `X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy`, `X-XSS-Protection`
- **SSL/TLS** via Let's Encrypt (automated renewal with acme-companion)
- **Container image scanning** with Trivy on every push (blocks on CRITICAL/HIGH CVEs)
- **Secrets validation** in CI – blocks deployment if `.env.example` contains real credentials
- **DB access** restricted to `127.0.0.1` (no external exposure)
- **Monitoring ports** (Prometheus :9090, cAdvisor :8080) bound to `127.0.0.1` only

---

## 🔄 CI/CD Pipeline

Defined in `.github/workflows/deploy.yml`. Triggered on every push to `main`.

**Stage 1 – `test`:**
- PHP syntax check on all `.php` files
- `docker compose config` validation
- Verify `.env.example` contains no real secrets

**Stage 2 – `scan_image`** *(requires test to pass):*
- Builds the custom PHP-FPM image
- Scans it with Trivy for CRITICAL and HIGH vulnerabilities (ignoring unfixed CVEs)

**Stage 3 – `deploy`** *(requires scan_image to pass):*
- SSH into Oracle VM
- `git pull` + `docker compose up -d --build` + image cleanup
- Health check loop (12 × 10s) polling the live domain for HTTP 200
- **Automatic rollback** to previous commit if health check fails

---

## 📦 Deployment

The project is designed for deployment on any Docker-compatible Linux host (VPS, dedicated server, Oracle Cloud, etc.).

```bash
git clone <repository-url>
cd clickykeys-infra
cp .env.example .env
# Edit .env
docker compose up -d --build
```

In production, deployment is fully automated via the GitHub Actions pipeline (see above).

---

## 💼 Portfolio Value

This repository demonstrates:

- Multi-service Docker Compose orchestration with healthcheck-based startup ordering
- Automated SSL with nginx-proxy + acme-companion
- MySQL schema management via init scripts
- Anonymous analytics tracking (privacy-preserving IP masking)
- Nginx rate limiting and hardened security headers
- Full-stack monitoring with Prometheus, cAdvisor, and Grafana
- GitHub Actions CI/CD with Trivy scanning and automatic rollback
- i18n web app with multiple themes (light, neon, terminal, vaporwave)

It reflects my ability to manage both application development and infrastructure engineering.

---

## 🔗 Related Projects

ClickyKeys Application Repository  
[GitHub](https://github.com/Reksaku/ClickyKeys) · [Windows Store](https://apps.microsoft.com/store/detail/9PJT83WPC06K?cid=DevShareMCLPCS)

ClickyKeys Infrastructure — k8s branch (Terraform + Ansible + Kubernetes)  
[GitHub](https://github.com/Reksaku/clickykeys-infra/tree/k8s)

---

## 👤 Author

**Mateusz Wyrzykowski**

GitHub: https://github.com/Reksaku

---

## 📄 License

This project is part of a personal portfolio.

#### README file was co-created with Claude (Anthropic).