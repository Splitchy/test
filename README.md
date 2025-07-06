# Logistics & Delivery Management System

A full-stack Django + PostgreSQL solution implementing the workflow described in the specification (authentication for Admin/Client/Livreur, stock & pickup modules, BL/BD scanning flow, invoicing, dashboards, PDF/Barcode generation).

---

## Features Implemented

* Role-based authentication with JWT.
* Admin approval workflow for new Clients & Livreurs.
* City/Zone management and tariff tables.
* Optional stock management per client.
* Colis de Ramassage & Colis de Stock modules.
* Bon de Livraison & Bon d'Envoi creation + status transitions.
* Invoicing engine for Clients & Livreurs.
* REST API documented with Swagger/OpenAPI.
* Dockerised Postgres + Django for easy local setup.

> NOTE: The initial version focuses on back-end functionality. Front-end screens (React/Next.js) and PDF/barcode rendering will be delivered in a follow-up milestone.

---

## Quick Start

```bash
# 1. Clone repository & enter workspace
$ git clone <repo-url> && cd logistics-system

# 2. Copy environment variables template & review settings
$ cp .env.example .env

# 3. Launch containers (Django + PostgreSQL)
$ docker compose up --build -d

# 4. Apply migrations & create a superuser
$ docker compose exec web python manage.py migrate
$ docker compose exec web python manage.py createsuperuser

# 5. Browse API docs
http://localhost:8000/api/schema/swagger/
```

### Without Docker

```bash
python -m venv venv && source venv/bin/activate
pip install -r requirements.txt
export $(cat .env.example | xargs)  # then override variables as needed
python manage.py migrate
python manage.py runserver
```

---

## Project Structure

```
.
├── core/                 # Django project root (settings, urls)
├── apps/
│   ├── users/            # CustomUser, ClientProfile, LivreurProfile
│   ├── geo/              # City, Zone, Tariff
│   ├── stock/            # Colis de Stock
│   ├── pickup/           # Colis de Ramassage
│   ├── delivery/         # BL, BD, tracking, status logs
│   └── invoicing/        # Client & Livreur invoices
├── docker-compose.yml
├── requirements.txt
└── README.md
```

---

## API Endpoints (excerpt)

| Method | URL                                     | Description                         |
|--------|-----------------------------------------|-------------------------------------|
| POST   | /api/auth/register/                     | Register new user (role param)      |
| POST   | /api/auth/login/                        | Obtain JWT tokens                   |
| POST   | /api/auth/approve-user/<id>/            | Admin approves/refuses user         |
| GET    | /api/stock/items/                       | List stock colis (client)           |
| POST   | /api/stock/items/                       | Create new stock colis              |
| POST   | /api/delivery/bon-livraison/            | Create BL from eligible orders      |
| POST   | /api/delivery/scan/                     | Scan BL / BD / order tracking code  |

See the full interactive documentation at /api/schema/swagger/.

---

## Running Tests

```bash
docker compose exec web pytest -q
```

---

## License

MIT