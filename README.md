
<div align="center">
<img src="./web/img/Icon.png" width="10%" > 

# [ClickyKeys](https://github.com/Reksaku/ClickyKeys) Infrastructure — k8s branch
</div>

Infrastructure repository for the **ClickyKeys** web platform — Kubernetes edition.

This branch migrates the ClickyKeys infrastructure from Docker Compose to a fully automated Kubernetes environment, provisioned and configured end-to-end with Terraform and Ansible.

> ⚙️ Portfolio Project – DevOps / Cloud Infrastructure / Kubernetes

---

## 📌 Overview

`clickykeys-infra` (k8s branch) provides a fully automated cloud-native stack including:

- ☁️ Terraform-provisioned AWS EC2 instance
- 🤖 Ansible-driven server configuration (zero manual steps after `terraform apply`)
- ☸️ k3s — lightweight Kubernetes runtime
- 🪖 Helm — package manager for Kubernetes
- 🌐 ingress-nginx — reverse proxy and load balancer
- 🔐 cert-manager — automatic TLS via Let's Encrypt
- 📄 Jinja2-templated Kubernetes manifests

The goal of this branch is to replace the Docker Compose setup with a production-grade, declarative, and fully reproducible Kubernetes infrastructure that can be spun up from scratch with a single command pipeline.

---

## 🏛 Architecture

The stack consists of:

- **Terraform** – provisions the AWS EC2 instance, security groups, SSH key pair, and auto-generates the Ansible inventory
- **Ansible** – configures the server end-to-end via four ordered roles: `swap` → `k3s` → `helm` → `k8s`
- **k3s** – lightweight single-node Kubernetes cluster (Traefik disabled in favour of ingress-nginx)
- **ingress-nginx** – handles inbound HTTP/HTTPS traffic and routes it to services (deployed via Helm as a DaemonSet with host networking)
- **cert-manager** – automatically provisions and renews Let's Encrypt TLS certificates via HTTP-01 ACME challenge
- **nginx-web** – Kubernetes Deployment serving the ClickyKeys web application

Architecture flow:

```
Client
  │
  ▼
AWS EC2 (port 80 / 443)
  │
  ▼
ingress-nginx (DaemonSet, hostNetwork)
  │  TLS terminated by cert-manager / Let's Encrypt
  ▼
nginx-web Service (ClusterIP)
  │
  ▼
nginx-web Pod(s)
```

Automation pipeline:

```
terraform apply
  │  provisions EC2 + writes ansible/inventory.ini
  ▼
ansible-playbook playbook.yml
  ├── swap    – configures swap (RAM-aware size)
  ├── k3s     – installs k3s, sets up kubeconfig
  ├── helm    – installs ingress-nginx + cert-manager
  └── k8s     – renders Jinja2 manifests → kubectl apply
```

---

## 📁 Repository Structure

```
.
├── terraform/                  # Cloud provisioning (AWS EC2)
│   ├── main.tf                 # EC2 instance, security group, key pair, inventory generation
│   ├── variables.tf            # Input variable declarations
│   ├── outputs.tf              # Public IP output
│   ├── terraform.example.tfvars
│   └── terraform.tfvars        # (gitignored) actual values
│
├── ansible/                    # Server configuration automation
│   ├── playbook.yml            # Main playbook (runs all roles)
│   ├── group_vars/
│   │   ├── all.example.yml     # Template for group variables
│   │   └── all.yml             # (gitignored) actual variables
│   └── roles/
│       ├── swap/               # Swap space configuration
│       ├── k3s/                # k3s installation & kubeconfig setup
│       ├── helm/               # Helm + ingress-nginx + cert-manager
│       └── k8s/                # Render & apply Kubernetes manifests
│
├── k8s/
│   └── manifests/              # Jinja2-templated Kubernetes manifests
│       ├── namespace.yml.j2
│       ├── clusterissuer.yml.j2
│       ├── deployment.yml.j2
│       ├── service.yml.j2
│       └── ingress.yml.j2
│
├── php-fpm/                    # PHP-FPM container definition
│   ├── dockerfile
│   └── zz-healthcheck.conf
├── mysql-init/                 # Database initialisation scripts
│   └── 01-schema.sql
├── web/                        # Application source files
├── .env.example                # Example environment variables
└── README.md
```

---

## 🛠 Technologies Used

| Layer | Technology |
|---|---|
| Cloud provisioning | Terraform (AWS provider ~5.0) |
| Server configuration | Ansible |
| Kubernetes runtime | k3s |
| Package management | Helm 3 |
| Ingress controller | ingress-nginx |
| Certificate management | cert-manager + Let's Encrypt |
| Container runtime | containerd (bundled with k3s) |
| Application server | nginx:alpine |
| Backend | PHP-FPM |
| Database | MySQL |
| Templating | Jinja2 |

---

## 🚀 Deployment Guide

### Prerequisites

- Terraform ≥ 1.0
- Ansible ≥ 2.12
- AWS CLI configured with appropriate credentials
- An SSH key pair

### 1. Clone the repository

```bash
git clone -b k8s https://github.com/Reksaku/clickykeys-infra.git
cd clickykeys-infra
```

### 2. Configure Terraform variables

```bash
cp terraform/terraform.example.tfvars terraform/terraform.tfvars
```

Edit `terraform/terraform.tfvars`:

```hcl
aws_region           = "CHANGE_ME"
project_name         = "CHANGE_ME"
ami_id               = "ami-CHANGE_ME"
ssh_public_key_path  = "~/.ssh/id_rsa.pub"
ssh_private_key_path = "~/.ssh/id_rsa"
```

### 3. Configure Ansible variables

```bash
cp ansible/group_vars/all.example.yml ansible/group_vars/all.yml
```

Edit `ansible/group_vars/all.yml`:

```yaml
namespace: "CHANGE_ME"
domain: "CHANGE_ME"
email: "CHANGE_ME"
project_name: "CHANGE_ME"
letsencrypt_env: "staging"   # switch to "prod" once verified
nginx_replicas: 1
```

### 4. Provision the infrastructure

```bash
cd terraform
terraform init
terraform apply
```

Terraform will provision the EC2 instance and automatically generate `ansible/inventory.ini` with the server's public IP.

### 5. Run the Ansible playbook

```bash
cd ../ansible
ansible-playbook -i inventory.ini playbook.yml
```

Ansible will sequentially:
1. Configure swap space
2. Install and start k3s
3. Install Helm, ingress-nginx, and cert-manager
4. Render Jinja2 manifests and apply all Kubernetes resources

### 6. Verify the deployment

```bash
# On the remote server (SSH in) or locally with a copied kubeconfig:
kubectl get pods --all-namespaces
kubectl get ingress -n <namespace>
kubectl get certificate -n <namespace>
```

The site will be live at `https://<domain>` once cert-manager provisions the TLS certificate (typically within 1–2 minutes on `prod`).

---

## 🔧 Configuration Reference

### Terraform variables (`terraform/terraform.tfvars`)

| Variable | Description |
|---|---|
| `aws_region` | AWS region to deploy into |
| `project_name` | Name tag applied to all AWS resources |
| `ami_id` | AMI ID |
| `instance_type` | EC2 instance type (default: `t3.medium`) |
| `ssh_public_key_path` | Path to your SSH public key |
| `ssh_private_key_path` | Path to your SSH private key (used by Ansible) |

### Ansible group variables (`ansible/group_vars/all.yml`)

| Variable | Description |
|---|---|
| `namespace` | Kubernetes namespace for the application |
| `domain` | Public domain name for the site |
| `email` | Email used for Let's Encrypt registration |
| `project_name` | Used in resource naming |
| `letsencrypt_env` | `staging` or `prod` |
| `nginx_replicas` | Number of nginx-web pod replicas |


---

## 🔐 Security Considerations

- All traffic encrypted via TLS (Let's Encrypt, automated renewal by cert-manager)
- Security group restricts inbound traffic to ports 22, 80, and 443 only
- SSH key-pair authentication — no password login
- Service isolation via Kubernetes namespaces
- ingress-nginx terminates SSL before traffic reaches application pods
- Sensitive values kept out of version control (`.gitignore` covers `*.tfvars`, `all.yml`, `inventory.ini`, `.env`)

---

## 🔄 Comparison: main branch vs k8s branch

| Aspect | main (Docker) | k8s (this branch) |
|---|---|---|
| Orchestration | Docker Compose | Kubernetes (k3s) |
| Provisioning | Manual | Terraform (AWS EC2) |
| Configuration | Manual | Ansible (fully automated) |
| Ingress / proxy | Nginx container | ingress-nginx (Helm) |
| TLS | Let's Encrypt via env proxy | cert-manager (automatic) |
| Scaling | Manual (`docker-compose scale`) | `kubectl scale` / replica count |
| Portability | Any Docker host | Any k3s / Kubernetes cluster |

---

## 💼 Portfolio Value

This branch demonstrates:

- Cloud infrastructure provisioning with Terraform (AWS)
- Configuration management and automation with Ansible
- Kubernetes cluster setup (k3s) from scratch
- Helm chart deployment and management
- Automatic TLS certificate lifecycle with cert-manager
- Kubernetes manifest design (Deployment, Service, Ingress, Namespace, ClusterIssuer)
- Jinja2 templating for environment-specific manifest rendering
- End-to-end Infrastructure as Code — zero manual steps after initial variable configuration

It reflects the ability to design, automate, and operate a production-grade cloud-native infrastructure.

---

## 🔗 Related Projects

ClickyKeys Application Repository  
[GitHub](https://github.com/Reksaku/ClickyKeys),
[Windows Store](https://apps.microsoft.com/store/detail/9PJT83WPC06K?cid=DevShareMCLPCS)

ClickyKeys Infrastructure — main branch (Docker)  
[GitHub](https://github.com/Reksaku/clickykeys-infra)

---

## 👤 Author

**Mateusz Wyrzykowski**

GitHub: https://github.com/Reksaku

---

## 📄 License

This project is part of a personal portfolio.

README file was co-created with Claude (Anthropic).
