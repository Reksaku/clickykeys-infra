
<div align="center">
<img src="./web/img/Icon.png" width="10%" > 

# [ClickyKeys](https://github.com/Reksaku/ClickyKeys) Infrastructure
</div>

Infrastructure repository for the **ClickyKeys** web platform.

This project contains the complete Dockerized environment used to run and deploy the ClickyKeys website.  

> ⚙️ Portfolio Project – DevOps / Full Stack Infrastructure

---

## 📌 Overview

`clickykeys-infra` provides a fully containerized web stack including:

- 🐳 Docker-based environment
- 🔄 Docker Compose orchestration
- 🌐 Nginx reverse proxy
- 🐘 PHP-FPM backend service
- 📦 Environment-based configuration
- 🚀 Production-ready deployment structure

The goal of this repository is to maintain a clean, reproducible, and portable infrastructure setup for the ClickyKeys ecosystem.

---

## 🏛 Architecture

The stack consists of:

- **Nginx** – reverse proxy and static file server
- **PHP-FPM** – processes dynamic PHP requests
- **Web container** – application source code
- **Docker Compose** – orchestrates all services

All services communicate internally via Docker networking.

Architecture flow:

Client → Nginx → PHP-FPM → Application

---

## 📁 Repository Structure

```
.
├── web/                # Application source files
├── nginx/              # Nginx configuration
├── php-fpm/            # PHP-FPM configuration
├── Dockerfile          # Application container definition
├── docker-compose.yml  # Multi-container orchestration
├── .env.example        # Example environment variables
└── README.md
```

---

## 🛠 Technologies Used

- Docker
- Docker Compose
- Nginx
- PHP-FPM
- Environment configuration via `.env`
- Linux-based container runtime

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

Edit the `.env` file.

### 3. Build and start containers

```bash
docker-compose up --build -d
```



---

## 🔧 Environment Configuration

Environment variables are managed through the `.env` file.

Example configuration:

```env
MYSQL_HOST=database
MYSQL_DATABASE=my_stats.org

MYSQL_ROOT_PASSWORD=s%hTs!#iUA21
MYSQL_USER=admin
MYSQL_PASSWORD=Apples#22!
```

This ensures:

- Separation of configuration from code
- Easy environment switching (development / production)
- Secure configuration management

---

## 📦 Deployment

The project can be deployed on any Docker-compatible host:

- VPS (Hetzner, DigitalOcean, AWS EC2)
- Dedicated Linux server
- Self-hosted infrastructure

Deployment steps:

```bash
git clone <repository-url>
cd clickykeys-infra
cp .env.example .env
docker-compose up -d --build
```

---

## 🔐 Security Considerations

- Reverse proxy isolation via Nginx
- Service-level container separation
- Environment-based configuration management
- Production-oriented container structure
- SSL integrated by using Let's Encrypt

---

## 💼 Portfolio Value

This repository demonstrates:

- Infrastructure design from scratch
- Docker containerization best practices
- Multi-service orchestration
- Reverse proxy configuration
- Clean environment separation
- Production deployment readiness

It reflects my ability to manage both application development and infrastructure engineering.

---

## 🔗 Related Project

ClickyKeys Application Repository  
[GitHub](https://github.com/Reksaku/ClickyKeys),
[Windows Store](https://apps.microsoft.com/store/detail/9PJT83WPC06K?cid=DevShareMCLPCS)


---

## 👤 Author

**Mateusz Wyrzykowski**

GitHub: https://github.com/Reksaku

---

## 📄 License

This project is part of a personal portfolio.

README file was co-created with ChatGPT LLM.

