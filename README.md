<div align="center">

<img src="./website/web/img/Icon.png" width="10%">

# ClickyKeys Infrastructure

![AWS](https://img.shields.io/badge/AWS-EC2-FF9900?style=for-the-badge&logo=amazonaws&logoColor=white)
![Terraform](https://img.shields.io/badge/Terraform-7B42BC?style=for-the-badge&logo=terraform&logoColor=white)
![Ansible](https://img.shields.io/badge/Ansible-EE0000?style=for-the-badge&logo=ansible&logoColor=white)
![Kubernetes](https://img.shields.io/badge/Kubernetes-326CE5?style=for-the-badge&logo=kubernetes&logoColor=white)
![k3s](https://img.shields.io/badge/k3s-FFC61C?style=for-the-badge&logo=k3s&logoColor=black)
![Helm](https://img.shields.io/badge/Helm-0F1689?style=for-the-badge&logo=helm&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white)
![NGINX](https://img.shields.io/badge/NGINX-009639?style=for-the-badge&logo=nginx&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![PHP](https://img.shields.io/badge/PHP--FPM-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Let's Encrypt](https://img.shields.io/badge/Let's%20Encrypt-003A70?style=for-the-badge&logo=letsencrypt&logoColor=white)
![GitHub Actions](https://img.shields.io/badge/CI%2FCD-GitHub%20Actions-2088FF?style=for-the-badge&logo=githubactions&logoColor=white)
![GHCR](https://img.shields.io/badge/GHCR-181717?style=for-the-badge&logo=github&logoColor=white)
![Grafana Cloud](https://img.shields.io/badge/Grafana%20Cloud-F46800?style=for-the-badge&logo=grafana&logoColor=white)

**Production-grade, fully automated cloud-native infrastructure for the [ClickyKeys](https://github.com/Reksaku/ClickyKeys) web platform.**

*Provisioned with Terraform &middot; Configured with Ansible &middot; Orchestrated with Kubernetes (k3s) &middot; Shipped with GitHub Actions*

</div>

> [!NOTE]
> **This is the current production branch** &mdash; a fully automated, IaC-driven Kubernetes (k3s) stack provisioned with Terraform and Ansible, with a complete GitHub Actions CI/CD pipeline and Grafana Cloud observability.
> The earlier Docker Compose deployment is preserved on the [`docker-legacy` branch](https://github.com/Reksaku/clickykeys-infra/tree/docker-legacy) as a historical reference of the original setup that powered the ClickyKeys platform.

---

## DevOps Portfolio &mdash; At a Glance

This repository is a working DevOps portfolio piece and the live infrastructure backing the **ClickyKeys** project. It demonstrates an end-to-end, declarative pipeline that takes an empty AWS account and produces a running, TLS-secured, Kubernetes-hosted web application running **two isolated environments (production + staging)** on a single node &mdash; with **zero manual steps** after the variables are filled in, and a **push-to-deploy CI/CD pipeline** that builds, tests, promotes through staging, and auto-rolls-back on failure.

### Technology Stack

<table>
<tr>
<td><b>Cloud / IaC</b></td>
<td>AWS (EC2, Security Groups, Key Pairs) &middot; <b>Terraform</b> (provider <code>hashicorp/aws ~&gt; 5.0</code>)</td>
</tr>
<tr>
<td><b>Configuration Management</b></td>
<td><b>Ansible</b> (roles, handlers, group_vars) &middot; <b>Ansible Vault</b> for secrets</td>
</tr>
<tr>
<td><b>Container Orchestration</b></td>
<td><b>Kubernetes</b> via <b>k3s</b> (lightweight, single-node, Traefik disabled)</td>
</tr>
<tr>
<td><b>Multi-environment</b></td>
<td><b>production</b> and <b>staging</b> as separate namespaces on the same cluster, each with its own domain, database, credentials, and replica counts &mdash; driven by a single <code>environments</code> list</td>
</tr>
<tr>
<td><b>CI/CD</b></td>
<td><b>GitHub Actions</b> &mdash; PHPUnit &rarr; build &amp; push images &rarr; deploy to staging &rarr; smoke test &rarr; deploy to production &rarr; smoke test &rarr; auto-rollback on failure</td>
</tr>
<tr>
<td><b>Package Management</b></td>
<td><b>Helm 3</b> (ingress-nginx, cert-manager, grafana/k8s-monitoring)</td>
</tr>
<tr>
<td><b>Ingress &amp; TLS</b></td>
<td><b>ingress-nginx</b> (DaemonSet, hostNetwork) &middot; <b>cert-manager</b> + <b>Let's Encrypt</b> (HTTP-01 ACME)</td>
</tr>
<tr>
<td><b>Observability</b></td>
<td><b>Grafana Cloud</b> via the <b>grafana/k8s-monitoring</b> Helm chart (Grafana Alloy shipping cluster metrics + logs)</td>
</tr>
<tr>
<td><b>Containers</b></td>
<td><b>Docker</b> images published to <b>GitHub Container Registry (ghcr.io)</b>, tagged by short commit SHA &middot; <code>nginx:alpine</code> &middot; <code>php-fpm</code></td>
</tr>
<tr>
<td><b>Database</b></td>
<td><b>MySQL</b> (provisioned on the host by Ansible, exposed to the cluster via <code>Service</code> + <code>Endpoints</code>)</td>
</tr>
<tr>
<td><b>Analytics</b></td>
<td>First-party, cookieless visit analytics with <b>GeoIP</b> country lookup (DB-IP IP-to-Country Lite MMDB, refreshed monthly in CI) and IP anonymization</td>
</tr>
<tr>
<td><b>Templating</b></td>
<td><b>Jinja2</b> for parameterised Kubernetes manifests (cluster-wide and per-environment)</td>
</tr>
<tr>
<td><b>Secrets</b></td>
<td><b>Ansible Vault</b> on disk &middot; Kubernetes <b>Secret</b> resources at runtime</td>
</tr>
</table>

### Highlights for Recruiters

- **Infrastructure as Code, end to end.** A single `terraform apply` followed by a single `ansible-playbook` produces a fully working, TLS-terminated stack hosting both production and staging &mdash; no clicks, no copy-paste.
- **Push-to-deploy CI/CD.** Merging to `main` triggers GitHub Actions to run PHPUnit, build and push both container images to GHCR, deploy to **staging**, smoke-test it, promote to **production**, smoke-test again, and **automatically roll back** the production deployment if the post-deploy health check fails.
- **True multi-environment design.** Production and staging are fully isolated (separate namespaces, domains, databases, credentials, replica counts) yet defined by one declarative `environments` list, rendered through the same Jinja2 manifests.
- **Modular Ansible roles** (`swap` &rarr; `k3s` &rarr; `helm` &rarr; `mysql` &rarr; `k8s_cluster` &rarr; `monitoring`, then a per-environment `k8s_env` loop) with idempotent tasks, handlers, and templated configuration.
- **Cloud-native networking and security**: ingress-nginx as a DaemonSet on host network, Let's Encrypt automation through cert-manager, namespace isolation, secrets injected from Kubernetes `Secret` objects.
- **Observability out of the box**: Grafana Alloy ships cluster metrics and logs to Grafana Cloud, installed declaratively by Ansible from Vault-encrypted credentials.
- **Secrets hygiene**: every sensitive value is stored encrypted with Ansible Vault and excluded from version control via `.gitignore`.
- **Real running site.** This infrastructure powers the public ClickyKeys website &mdash; it is not a toy demo.

---

## Architecture

```
                              ┌─────────────────────────┐
                              │   Client (browser)      │
                              └────────────┬────────────┘
                                           │ HTTPS / 443
                                           ▼
                  ┌────────────────────────────────────────────────┐
                  │  AWS EC2 instance  (Ubuntu, t3.medium, gp3 20G) │
                  │                                                │
                  │   ┌────────────────────────────────────────┐   │
                  │   │ ingress-nginx (DaemonSet, hostNet)     │   │
                  │   │   TLS terminated by cert-manager       │   │
                  │   └──────────────┬─────────────────────────┘   │
                  │                  │                             │
                  │   ┌──────────────▼─────────────────────────┐   │
                  │   │ k3s cluster                            │   │
                  │   │  ├─ ns: production                     │   │
                  │   │  │    ├─ nginx-web  (Deployment + Svc) │   │
                  │   │  │    ├─ php-fpm    (Deployment + Svc) │   │
                  │   │  │    └─ mysql-svc  (Service + Endpts) │◄──┼── MySQL on host
                  │   │  ├─ ns: staging                        │   │   (Ansible-managed)
                  │   │  │    └─ (same set, scaled to 0 idle)  │   │
                  │   │  ├─ cert-manager / ingress-nginx       │   │
                  │   │  └─ ns: monitoring (Grafana Alloy) ────┼───┼──► Grafana Cloud
                  │   └────────────────────────────────────────┘   │   (metrics + logs)
                  └────────────────────────────────────────────────┘
```

### Provisioning & configuration pipeline

```
terraform apply
   │  provisions EC2, Security Group, Key Pair (gp3 20 GB root volume)
   │  writes ansible/inventory.ini with the new public IP
   ▼
ansible-playbook playbook.yml --ask-vault-pass
   ├── swap          – RAM-aware swapfile
   ├── k3s           – installs k3s, sets up kubeconfig
   ├── helm          – installs ingress-nginx + cert-manager
   ├── mysql         – installs MySQL, creates per-env DBs & users, imports schema
   ├── k8s_cluster   – renders + applies cluster-wide manifests (ClusterIssuer)
   ├── monitoring    – installs grafana/k8s-monitoring (Alloy → Grafana Cloud)
   └── k8s_env (loop)– for each environment in `environments`:
                       namespace, mysql-credentials Secret, services,
                       nginx/php deployments, ingress
```

### Continuous delivery pipeline (`.github/workflows/build.yml`)

```
push to main
   ├── phpunit_test          – PHPUnit on ./php-fpm (PHP 8.2)
   ├── images_build          – build & push web + php images to GHCR
   │                           (tags: <short-sha> and latest; refreshes GeoIP DB)
   ├── deploy-staging        – SSH: kubectl set image on staging namespace
   ├── smoke-test-staging    – GET /api/healthz.php must return 200 + healthy payload
   ├── deploy-production     – SSH: kubectl set image + change-cause annotation
   ├── smoke-test-production – GET /api/healthz.php; on failure → kubectl rollout undo
   └── turn-off-staging      – scale staging deployments back to 0 replicas
```

> A manual-only `test.yml` workflow (`workflow_dispatch`) is also provided to build and push images on demand without deploying.

---

## Repository Structure

```
.
├── terraform/                           # AWS provisioning (IaC)
│   ├── main.tf                          # EC2 + Security Group + Key Pair + inventory generation
│   ├── variables.tf
│   ├── outputs.tf
│   └── terraform.example.tfvars         # ← copy to terraform.tfvars and edit
│
├── ansible/                             # Server configuration
│   ├── playbook.yml                     # Host-wide roles + per-environment k8s_env loop
│   ├── group_vars/
│   │   └── all.example/                 # ← copy to all/ and edit, then encrypt vault.yml
│   │       ├── all.example.yml          # global + `environments` list + Grafana Cloud refs
│   │       └── vault.example.yml        # prod/staging secrets + Grafana Cloud credentials
│   └── roles/
│       ├── swap/                        # Swapfile (RAM-aware sizing)
│       ├── k3s/                         # k3s install + kubeconfig
│       ├── helm/                        # Helm + ingress-nginx + cert-manager
│       ├── mysql/                       # MySQL server, per-env users, schema, config
│       ├── k8s_cluster/                 # Cluster-wide manifests (ClusterIssuer)
│       ├── k8s_env/                     # Per-environment app resources (looped)
│       └── monitoring/                  # grafana/k8s-monitoring (Alloy → Grafana Cloud)
│
├── k8s/manifests/                       # Jinja2-templated Kubernetes manifests
│   ├── cluster/
│   │   └── clusterissuer.yml.j2         # Let's Encrypt staging + prod issuers
│   └── env/                             # Rendered once per environment
│       ├── namespace.yml.j2
│       ├── deployment-nginx.yml.j2      # nginx-web (static frontend)
│       ├── deployment-php.yml.j2        # php-fpm (backend)
│       ├── service-nginx.yml.j2
│       ├── service-php.yml.j2
│       ├── service-mysql.yml.j2         # ClusterIP + Endpoints pointing at host MySQL
│       └── ingress.yml.j2               # TLS-terminated routing
│
├── .github/workflows/
│   ├── build.yml                        # Full CI/CD: test → build → staging → prod → rollback
│   └── test.yml                         # Manual build & push to GHCR (workflow_dispatch)
│
├── scripts/
│   └── smoke-check.sh                   # Health check against /api/healthz.php
│
├── docs/
│   └── privacy-analytics.md             # Privacy-policy copy for the first-party analytics
│
├── website/                             # Frontend container source (nginx:alpine)
│   ├── Dockerfile
│   ├── nginx.conf
│   └── web/                             # HTML, CSS, JS, images
│
├── php-fpm/                             # Backend container source (php-fpm 8.2)
│   ├── Dockerfile
│   ├── geoip/                           # DB-IP IP-to-Country Lite MMDB (fetched in CI)
│   └── web/
│
└── README.md
```

---

## Setup &amp; Deployment

### Prerequisites

- Terraform &ge; 1.0
- Ansible &ge; 2.12
- AWS CLI configured with credentials that can create EC2 / Security Groups / Key Pairs
- An SSH key pair on your local machine
- DNS A records for **both** the production and staging domains pointing at the EC2 public IP after provisioning
- A Grafana Cloud stack (free tier is enough) for metrics + logs
- `docker` only if you want to build and push the application images manually (CI does this for you)

### 1. Clone the repository

```bash
git clone https://github.com/Reksaku/clickykeys-infra.git
cd clickykeys-infra
```

### 2. Configure Terraform variables

All `.example` files in this repo are templates &mdash; copy them and fill in real values. The actual files (without `.example`) are gitignored.

```bash
cp terraform/terraform.example.tfvars terraform/terraform.tfvars
```

Edit `terraform/terraform.tfvars`:

```hcl
aws_region           = "eu-central-1"
project_name         = "clickykeys"
ami_id               = "ami-xxxxxxxxxxxxxxxxx"   # Ubuntu 24.04 LTS in your region
instance_type        = "t3.medium"               # optional, this is the default
ssh_public_key_path  = "~/.ssh/id_rsa.pub"
ssh_private_key_path = "~/.ssh/id_rsa"
```

### 3. Configure Ansible variables (non-secret)

Copy the entire example group_vars directory, then edit the files in the new `all/` folder:

```bash
cp -r ansible/group_vars/all.example ansible/group_vars/all
```

Edit `ansible/group_vars/all/all.yml`. Global settings, the GHCR image source, the per-environment list, and the Grafana Cloud references all live here:

```yaml
project_name: clickykeys
letsencrypt_env: staging          # switch to "prod" once everything works
nginx_replicas: 1

# GHCR image source (CI pushes here as ghcr.io/<owner>/<image>:<short-sha>)
ghcr_owner: <your-github-user>
image_web_name: clickykeys-web
image_php_name: clickykeys-php

mysql_root_password: "{{ vault_mysql_root_password }}"

environments:
  - name: production
    namespace: "{{ vault_prod_namespace }}"
    domain:    "{{ vault_prod_domain }}"
    email:     "{{ vault_prod_email }}"
    mysql_database:      "{{ vault_prod_mysql_database }}"
    mysql_user_name:     "{{ vault_prod_mysql_user_name }}"
    mysql_user_password: "{{ vault_prod_mysql_user_password }}"
    web_image_tag: latest
    php_image_tag: latest
    php_replicas: 2

  - name: staging
    namespace: "{{ vault_staging_namespace }}"
    domain:    "{{ vault_staging_domain }}"
    email:     "{{ vault_staging_email }}"
    mysql_database:      "{{ vault_staging_mysql_database }}"
    mysql_user_name:     "{{ vault_staging_mysql_user_name }}"
    mysql_user_password: "{{ vault_staging_mysql_user_password }}"
    web_image_tag: latest
    php_image_tag: latest
    php_replicas: 1

# Grafana Cloud (values pulled from the encrypted vault)
grafana_cloud_metrics_url:      "{{ vault_grafana_cloud_metrics_url }}"
grafana_cloud_metrics_username: "{{ vault_grafana_cloud_metrics_username }}"
grafana_cloud_logs_url:         "{{ vault_grafana_cloud_logs_url }}"
grafana_cloud_logs_username:    "{{ vault_grafana_cloud_logs_username }}"
grafana_cloud_api_key:          "{{ vault_grafana_cloud_api_key }}"
```

### 4. Fill in and encrypt the Ansible Vault

The vault stores everything sensitive: per-environment domains, emails, MySQL credentials, and Grafana Cloud keys.

Edit `ansible/group_vars/all/vault.yml` with your real values:

```yaml
vault_mysql_root_password: <strong-root-password>

# ── Production ──
vault_prod_namespace: clickykeys
vault_prod_domain:    clickykeys.example.com
vault_prod_email:     you@example.com
vault_prod_mysql_database:      clickykeys
vault_prod_mysql_user_name:     clickykeys_app
vault_prod_mysql_user_password: <strong-prod-password>

# ── Staging (must differ from production) ──
vault_staging_namespace: staging
vault_staging_domain:    staging.example.com
vault_staging_email:     you@example.com
vault_staging_mysql_database:      clickykeys_staging
vault_staging_mysql_user_name:     clickykeys_staging
vault_staging_mysql_user_password: <strong-staging-password>

# ── Grafana Cloud ──
vault_grafana_cloud_metrics_url:      <prometheus-remote-write-url>
vault_grafana_cloud_metrics_username: <metrics-user-id>
vault_grafana_cloud_logs_url:         <loki-push-url>
vault_grafana_cloud_logs_username:    <logs-user-id>
vault_grafana_cloud_api_key:          <grafana-cloud-api-key>
```

```bash
# Encrypt the file with Ansible Vault
ansible-vault encrypt ansible/group_vars/all/vault.yml
```

You will be prompted for a vault password &mdash; remember it; you will need it every time you run the playbook. To edit the encrypted file later use `ansible-vault edit ansible/group_vars/all/vault.yml`.

### 5. Provision the EC2 instance with Terraform

```bash
cd terraform
terraform init
terraform apply
```

Terraform creates the EC2 instance (20 GB gp3 root volume), security group, and SSH key pair, and writes `ansible/inventory.ini` with the new public IP. Point your production and staging DNS A records at this IP before switching to `prod` Let's Encrypt.

### 6. Run the Ansible playbook

```bash
cd ../ansible
ansible-playbook -i inventory.ini playbook.yml --ask-vault-pass
```

The host-wide roles run once (`swap` &rarr; `k3s` &rarr; `helm` &rarr; `mysql` &rarr; `k8s_cluster` &rarr; `monitoring`), then the `k8s_env` role is applied once per environment in the `environments` list. To limit the run to a subset of environments, override `target_envs`:

```bash
ansible-playbook -i inventory.ini playbook.yml --ask-vault-pass \
  -e 'target_envs=["staging"]'
```

### 7. Build &amp; push the application images

In normal operation **CI does this for you** &mdash; every push to `main` builds and pushes both images to GHCR (see [CI/CD](#cicd) below). To build them manually:

```bash
# Frontend (nginx:alpine + static site)
docker build -t ghcr.io/<your-user>/clickykeys-web:latest ./website
docker push  ghcr.io/<your-user>/clickykeys-web:latest

# Backend (php-fpm)
docker build -t ghcr.io/<your-user>/clickykeys-php:latest ./php-fpm
docker push  ghcr.io/<your-user>/clickykeys-php:latest
```

### 8. Verify the deployment

```bash
ssh ubuntu@<public_ip>

sudo kubectl get pods --all-namespaces
sudo kubectl get ingress  -A
sudo kubectl get certificate -A
```

Each environment's site goes live at its configured domain once cert-manager finishes the ACME HTTP-01 challenge (typically 1&ndash;2 minutes on `prod`). You can also run the smoke check locally:

```bash
BASE_URL=https://clickykeys.example.com ./scripts/smoke-check.sh
```

---

## CI/CD

The pipeline lives in `.github/workflows/build.yml` and runs on every push to `main` (and via `workflow_dispatch`).

1. **`phpunit_test`** &mdash; runs PHPUnit against `./php-fpm` on PHP 8.2 with cached Composer deps.
2. **`images_build`** &mdash; builds and pushes the `web` and `php` images to GHCR, tagged with both the **short commit SHA** and `latest`, using GitHub Actions layer caching. The php build step refreshes the **DB-IP IP-to-Country Lite** GeoIP database before building.
3. **`deploy-staging`** &mdash; SSHes to the host and `kubectl set image` on the staging namespace (scaling it up from 0 first).
4. **`smoke-test-staging`** &mdash; `GET /api/healthz.php`; must return HTTP 200 with `{"status":"ok"}`.
5. **`deploy-production`** &mdash; `kubectl set image` on the production namespace and records a `kubernetes.io/change-cause` annotation (tag, commit, run URL) for auditable rollout history.
6. **`smoke-test-production`** &mdash; same health check; **on failure it automatically runs `kubectl rollout undo`** on both deployments to restore the previous ReplicaSet.
7. **`turn-off-staging`** &mdash; scales the staging deployments back to 0 replicas to save resources.

Required GitHub Actions secrets: `PROD_HOST`, `PROD_SSH_KEY`, `PROD_NAMESPACE`, `STAGING_URL`, `PROD_URL` (plus the built-in `GITHUB_TOKEN` for GHCR).

---

## Observability

The `monitoring` Ansible role installs the **grafana/k8s-monitoring** Helm chart (chart major `^4`) into a dedicated `monitoring` namespace. It deploys **Grafana Alloy** to collect cluster metrics and pod logs and remote-writes them to **Grafana Cloud**. All endpoints, usernames, and the API key are templated from Ansible Vault (`grafana_cloud_*`), so no credentials are stored in plaintext. The role waits for the `alloy-metrics` StatefulSet to become ready before completing.

---

## Configuration Reference

### Terraform &mdash; `terraform/terraform.tfvars`

| Variable | Description |
|---|---|
| `aws_region` | AWS region to deploy into |
| `project_name` | Name tag / prefix applied to AWS resources (SG, key pair) |
| `ami_id` | AMI ID (Ubuntu 24.04 LTS recommended) |
| `instance_type` | EC2 instance type (default `t3.medium`) |
| `ssh_public_key_path` | Path to your SSH public key |
| `ssh_private_key_path` | Path to your SSH private key (used by Ansible) |

### Ansible &mdash; `ansible/group_vars/all/all.yml`

| Variable | Description |
|---|---|
| `project_name` | Used in resource naming and tags |
| `letsencrypt_env` | `staging` (default) or `prod` |
| `nginx_replicas` | Number of `nginx-web` pod replicas |
| `ghcr_owner` | GHCR owner/namespace that images are pushed under |
| `image_web_name` / `image_php_name` | Image names for the frontend / backend |
| `mysql_root_password` | Provisioning-only root password (from vault) |
| `environments[]` | List of environments; each entry defines `name`, `namespace`, `domain`, `email`, `mysql_*`, `web_image_tag`, `php_image_tag`, `php_replicas` (vault-backed) |
| `grafana_cloud_*` | Grafana Cloud metrics/logs endpoints, usernames, API key (from vault) |

### Ansible Vault &mdash; `ansible/group_vars/all/vault.yml` (encrypted)

| Variable | Description |
|---|---|
| `vault_mysql_root_password` | Shared MySQL root password (provisioning only) |
| `vault_prod_*` | Production namespace, domain, email, and MySQL database/user/password |
| `vault_staging_*` | Staging namespace, domain, email, and MySQL database/user/password |
| `vault_grafana_cloud_metrics_url` / `_username` | Grafana Cloud Prometheus remote-write endpoint + user |
| `vault_grafana_cloud_logs_url` / `_username` | Grafana Cloud Loki push endpoint + user |
| `vault_grafana_cloud_api_key` | Grafana Cloud API key |

---

## Security

- **TLS everywhere.** Public traffic is terminated at ingress-nginx and certificates are issued and renewed automatically by cert-manager via Let's Encrypt.
- **Locked-down security group.** Only ports 22, 80, and 443 are exposed publicly.
- **Key-based SSH only.** No password authentication.
- **Secrets are never committed.** `.gitignore` covers `*.tfvars`, `inventory.ini`, the real `all/` group_vars, `vault.yml`, `*.pem`, `*.key`, and `.env`. Sensitive Ansible variables live in an Ansible Vault encrypted file.
- **Kubernetes Secrets** are created at deploy time with `kubectl create secret … --dry-run=client -o yaml | kubectl apply -f -` (with `no_log: true`) so plaintext values are never written to disk.
- **Environment isolation.** Production and staging run in separate namespaces with distinct databases, users, and credentials.
- **Auditable rollouts.** Production deployments are annotated with the image tag, commit, and CI run URL, and an automatic rollback restores the previous ReplicaSet if the post-deploy smoke test fails.
- **Privacy-respecting analytics.** First-party, cookieless visit stats with IP anonymization and GeoIP country lookup (see `docs/privacy-analytics.md`).

---

## Branch Status &mdash; `main` (current) vs `docker-legacy`

| Aspect | `main` (k3s, **current production** &mdash; this branch) | `docker-legacy` (Docker Compose, **legacy**) |
|---|---|---|
| Orchestration | Kubernetes (k3s) | Docker Compose |
| Provisioning | Terraform (AWS EC2) | Manual |
| Configuration | Ansible (fully automated, idempotent roles) | `.env` file, manual `docker compose up` |
| Environments | Production + staging (isolated namespaces) | Single |
| CI/CD | GitHub Actions (test → build → staging → prod → auto-rollback) | None |
| Ingress / proxy | `ingress-nginx` via Helm | `nginx-proxy` container |
| TLS | `cert-manager` (automatic renewal) | Let's Encrypt via `acme-companion` |
| Scaling | `kubectl scale` / replica count | `docker compose scale` |
| Secrets | Ansible Vault + Kubernetes `Secret` resources | `.env` files |
| Observability | Grafana Cloud (Alloy: metrics + logs) | Prometheus + cAdvisor + Grafana (in-stack) |
| Portability | Any Kubernetes / k3s cluster | Any Docker host |

> The `main` branch (this one) is the active production deployment. The `docker-legacy` branch remains in the repository as a **legacy** reference of the original Docker Compose setup that powered ClickyKeys.

---

## Related Projects

- **ClickyKeys production page** &mdash; [clickykeys.fun](https://clickykeys.fun)
- **ClickyKeys application** &mdash; [GitHub](https://github.com/Reksaku/ClickyKeys) &middot; [Microsoft Store](https://apps.microsoft.com/store/detail/9PJT83WPC06K?cid=DevShareMCLPCS)
- **ClickyKeys infrastructure (legacy, Docker Compose)** &mdash; [`docker-legacy` branch](https://github.com/Reksaku/clickykeys-infra/tree/docker-legacy)

---

## Author

**Mateusz Wyrzykowski** &mdash; [github.com/Reksaku](https://github.com/Reksaku)

---

## License

This project is part of a personal portfolio.

*This README was co-authored with Claude (Anthropic).*
