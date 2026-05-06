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
![GitHub Actions](https://img.shields.io/badge/GHCR-181717?style=for-the-badge&logo=github&logoColor=white)

**Production-grade, fully automated cloud-native infrastructure for the [ClickyKeys](https://github.com/Reksaku/ClickyKeys) web platform.**

*Provisioned with Terraform &middot; Configured with Ansible &middot; Orchestrated with Kubernetes (k3s)*

</div>

> [!NOTE]
> **This is the current production branch** &mdash; a fully automated, IaC-driven Kubernetes (k3s) stack provisioned with Terraform and Ansible.
> The earlier Docker Compose deployment is preserved on the [`docker-legacy` branch](https://github.com/Reksaku/clickykeys-infra/tree/docker-legacy) as a historical reference of the original setup that powered the ClickyKeys platform.

---

## DevOps Portfolio &mdash; At a Glance

This repository is a working DevOps portfolio piece and the live infrastructure backing the **ClickyKeys** project. It demonstrates an end-to-end, declarative pipeline that takes an empty AWS account and produces a running, TLS-secured, Kubernetes-hosted web application &mdash; with **zero manual steps** after the variables are filled in.

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
<td><b>Package Management</b></td>
<td><b>Helm 3</b> (ingress-nginx, cert-manager)</td>
</tr>
<tr>
<td><b>Ingress &amp; TLS</b></td>
<td><b>ingress-nginx</b> (DaemonSet, hostNetwork) &middot; <b>cert-manager</b> + <b>Let's Encrypt</b> (HTTP-01 ACME)</td>
</tr>
<tr>
<td><b>Containers</b></td>
<td><b>Docker</b> images published to <b>GitHub Container Registry (ghcr.io)</b> &middot; <code>nginx:alpine</code> &middot; <code>php-fpm</code></td>
</tr>
<tr>
<td><b>Database</b></td>
<td><b>MySQL</b> (provisioned on the host by Ansible, exposed to the cluster via <code>Service</code> + <code>Endpoints</code>)</td>
</tr>
<tr>
<td><b>Templating</b></td>
<td><b>Jinja2</b> for parameterised Kubernetes manifests</td>
</tr>
<tr>
<td><b>Secrets</b></td>
<td><b>Ansible Vault</b> on disk &middot; Kubernetes <b>Secret</b> resources at runtime</td>
</tr>
</table>

### Highlights for Recruiters

- **Infrastructure as Code, end to end.** A single `terraform apply` followed by a single `ansible-playbook` produces a fully working, TLS-terminated production stack &mdash; no clicks, no copy-paste.
- **Modular Ansible roles** (`swap` &rarr; `k3s` &rarr; `helm` &rarr; `k8s` &rarr; `mysql`) with idempotent tasks, handlers, and templated configuration.
- **Cloud-native networking and security**: ingress-nginx as a DaemonSet on host network, Let's Encrypt automation through cert-manager, namespace isolation, secrets injected from Kubernetes `Secret` objects.
- **Templated Kubernetes manifests** rendered with Jinja2 so the same codebase deploys to any environment by changing variables.
- **Secrets hygiene**: every sensitive value is stored encrypted with Ansible Vault and is excluded from version control via `.gitignore`.
- **Real running site.** This infrastructure powers the public ClickyKeys website &mdash; it is not a toy demo.

---

## Architecture

```
                              ┌─────────────────────────┐
                              │   Client (browser)      │
                              └────────────┬────────────┘
                                           │ HTTPS / 443
                                           ▼
                  ┌────────────────────────────────────────────┐
                  │  AWS EC2 instance  (Ubuntu, t3.medium)     │
                  │                                            │
                  │   ┌────────────────────────────────────┐   │
                  │   │ ingress-nginx (DaemonSet, hostNet) │   │
                  │   │   TLS terminated by cert-manager   │   │
                  │   └──────────────┬─────────────────────┘   │
                  │                  │                         │
                  │   ┌──────────────▼─────────────────────┐   │
                  │   │ k3s cluster                        │   │
                  │   │  ├─ nginx-web   (Deployment + Svc) │   │
                  │   │  ├─ php-fpm     (Deployment + Svc) │   │
                  │   │  └─ mysql-svc   (Service + Endpts) │◄──┼── MySQL on host
                  │   └────────────────────────────────────┘   │     (Ansible-managed)
                  └────────────────────────────────────────────┘
```

### Automation pipeline

```
terraform apply
   │  provisions EC2, Security Group, Key Pair
   │  writes ansible/inventory.ini with the new public IP
   ▼
ansible-playbook playbook.yml --ask-vault-pass
   ├── swap   – RAM-aware swapfile
   ├── k3s    – installs k3s, sets up kubeconfig
   ├── helm   – installs ingress-nginx + cert-manager
   ├── k8s    – renders Jinja2 manifests, applies all K8s resources
   └── mysql  – installs MySQL, creates DB & user, imports schema
```

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
│   ├── playbook.yml                     # Main playbook (runs all roles in order)
│   ├── group_vars/
│   │   └── all/
│   │       ├── all.example.yml          # ← copy to all.yml and edit
│   │       └── vault.example.yml        # ← copy to vault.yml, fill in, then encrypt with ansible-vault
│   └── roles/
│       ├── swap/                        # Swapfile (RAM-aware sizing)
│       ├── k3s/                         # k3s install + kubeconfig
│       ├── helm/                        # Helm + ingress-nginx + cert-manager
│       ├── k8s/                         # Render Jinja2 manifests, kubectl apply
│       └── mysql/                       # MySQL server, users, schema, config
│
├── k8s/manifests/                       # Jinja2-templated Kubernetes manifests
│   ├── namespace.yml.j2
│   ├── clusterissuer.yml.j2             # Let's Encrypt staging + prod issuers
│   ├── deployment-nginx.yml.j2          # nginx-web (static frontend)
│   ├── deployment-php.yml.j2            # php-fpm (backend)
│   ├── service-nginx.yml.j2
│   ├── service-php.yml.j2
│   ├── service-mysql.yml.j2             # ClusterIP + Endpoints pointing at host MySQL
│   └── ingress.yml.j2                   # TLS-terminated routing
│
├── website/                             # Frontend container source (nginx:alpine)
│   ├── Dockerfile
│   ├── nginx.conf
│   └── web/                             # HTML, CSS, JS, images
│
├── php-fpm/                             # Backend container source (php-fpm)
│   ├── Dockerfile
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
- A domain name pointing (A record) at the EC2 public IP after provisioning
- `docker` if you want to build and push the application images yourself

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
ssh_public_key_path  = "~/.ssh/id_rsa.pub"
ssh_private_key_path = "~/.ssh/id_rsa"
```

### 3. Configure Ansible variables (non-secret)

```bash
cp ansible/group_vars/all/all.example.yml ansible/group_vars/all/all.yml
```

Edit `ansible/group_vars/all/all.yml` with your non-secret settings:

```yaml
project_name: clickykeys
letsencrypt_env: staging          # switch to "prod" once everything works
nginx_replicas: 1
web_image_registry: ghcr.io/<your-user>/clickykeys-web:latest
php_image_registry: ghcr.io/<your-user>/clickykeys-php:latest

# These reference the encrypted vault &mdash; do NOT change unless you also rename in vault.yml
namespace: "{{ vault_namespace }}"
domain:    "{{ vault_domain }}"
email:     "{{ vault_email }}"

mysql_root_password: "{{ vault_mysql_root_password }}"
mysql_user_password: "{{ vault_mysql_user_password }}"
mysql_user_name:     "{{ vault_mysql_user_name }}"
mysql_database:      "{{ vault_mysql_database }}"
```

### 4. Create and encrypt the Ansible Vault

The vault stores everything sensitive: domain, contact email, MySQL credentials.

```bash
# 4a. Copy the template
cp ansible/group_vars/all/vault.example.yml ansible/group_vars/all/vault.yml
```

Edit `ansible/group_vars/all/vault.yml` with your real values:

```yaml
vault_namespace: clickykeys
vault_domain:    clickykeys.example.com
vault_email:     you@example.com

vault_mysql_root_password: <strong-root-password>
vault_mysql_user_password: <strong-app-password>
vault_mysql_user_name:     clickykeys
vault_mysql_database:      clickykeys
```

```bash
# 4b. Encrypt the file with Ansible Vault
ansible-vault encrypt ansible/group_vars/all/vault.yml
```

You will be prompted for a vault password &mdash; remember it; you will need it every time you run the playbook. To edit the encrypted file later use `ansible-vault edit ansible/group_vars/all/vault.yml`.

### 5. Build &amp; push the application images (optional)

If you want to deploy your own build of the website / backend, build and push the images to GHCR before running Ansible. Otherwise, point `web_image_registry` / `php_image_registry` in `all.yml` at any existing images.

```bash
# Frontend (nginx:alpine + static site)
cd website
docker build -t ghcr.io/<your-user>/clickykeys-web:latest .
docker push  ghcr.io/<your-user>/clickykeys-web:latest

# Backend (php-fpm)
cd ../php-fpm
docker build -t ghcr.io/<your-user>/clickykeys-php:latest .
docker push  ghcr.io/<your-user>/clickykeys-php:latest
cd ..
```

### 6. Provision the EC2 instance with Terraform

```bash
cd terraform
terraform init
terraform apply
```

Terraform creates the EC2 instance, security group, and SSH key pair, and writes `ansible/inventory.ini` with the new public IP. Point your DNS A record at this IP before going to `prod` Let's Encrypt.

### 7. Run the Ansible playbook

```bash
cd ../ansible
ansible-playbook -i inventory.ini playbook.yml --ask-vault-pass
```

The playbook will run all five roles in order: `swap` &rarr; `k3s` &rarr; `helm` &rarr; `k8s` &rarr; `mysql`.

### 8. Verify the deployment

```bash
# SSH into the EC2 host (or copy the kubeconfig locally)
ssh ubuntu@<public_ip>

sudo kubectl get pods --all-namespaces
sudo kubectl get ingress -n <namespace>
sudo kubectl get certificate -n <namespace>
```

The site will be live at `https://<your-domain>` once cert-manager finishes the ACME HTTP-01 challenge (typically 1&ndash;2 minutes on `prod`).

---

## Configuration Reference

### Terraform &mdash; `terraform/terraform.tfvars`

| Variable | Description |
|---|---|
| `aws_region` | AWS region to deploy into |
| `project_name` | Name tag applied to all AWS resources |
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
| `web_image_registry` | Full image reference for the frontend container |
| `php_image_registry` | Full image reference for the backend container |
| `namespace`, `domain`, `email` | Pulled from the encrypted vault |
| `mysql_*` | Pulled from the encrypted vault |

### Ansible Vault &mdash; `ansible/group_vars/all/vault.yml` (encrypted)

| Variable | Description |
|---|---|
| `vault_namespace` | Kubernetes namespace for the application |
| `vault_domain` | Public domain name (must point at the EC2 public IP) |
| `vault_email` | Email used for Let's Encrypt registration |
| `vault_mysql_root_password` | MySQL root password |
| `vault_mysql_user_name` | Application database user |
| `vault_mysql_user_password` | Application database password |
| `vault_mysql_database` | Application database name |

---

## Security

- **TLS everywhere.** Public traffic is terminated at ingress-nginx and certificates are issued and renewed automatically by cert-manager via Let's Encrypt.
- **Locked-down security group.** Only ports 22, 80, and 443 are exposed publicly.
- **Key-based SSH only.** No password authentication.
- **Secrets are never committed.** `.gitignore` covers `*.tfvars`, `inventory.ini`, `all.yml`, `vault.yml`, `*.pem`, `*.key`, and `.env`. Sensitive Ansible variables live in an Ansible Vault encrypted file.
- **Kubernetes Secrets** are created at deploy time with `kubectl create secret &hellip; --dry-run=client -o yaml | kubectl apply -f -` so plaintext values are never written to disk.
- **Namespace isolation** between application and platform components (ingress-nginx, cert-manager).

---

## Branch Status &mdash; `main` (current) vs `docker-legacy`

| Aspect | `main` (k3s, **current production** &mdash; this branch) | `docker-legacy` (Docker Compose, **legacy**) |
|---|---|---|
| Orchestration | Kubernetes (k3s) | Docker Compose |
| Provisioning | Terraform (AWS EC2) | Manual |
| Configuration | Ansible (fully automated, idempotent roles) | `.env` file, manual `docker compose up` |
| Ingress / proxy | `ingress-nginx` via Helm | `nginx-proxy` container |
| TLS | `cert-manager` (automatic renewal) | Let's Encrypt via `acme-companion` |
| Scaling | `kubectl scale` / replica count | `docker compose scale` |
| Secrets | Ansible Vault + Kubernetes `Secret` resources | `.env` files |
| Observability | Cluster-native (k8s metrics, future Prometheus Operator) | Prometheus + cAdvisor + Grafana (in-stack) |
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
