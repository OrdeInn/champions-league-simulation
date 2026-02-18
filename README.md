# Champions League Simulation

A football league simulation app built with Laravel, Vue.js, and Inertia.js. Simulate a round-robin group stage with four teams, track standings, view match results week by week, and get championship predictions.

## Features

- **Tournament Teams** — View the four competing teams and generate fixtures
- **Fixtures** — Round-robin schedule generated automatically
- **Simulation** — Play matches week by week or all at once
- **League Table** — Live standings with points, wins, draws, losses, and goal difference
- **Championship Predictions** — Probability estimates based on current standings and remaining matches
- **Editable Results** — Modify match scores and recalculate standings

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2+, Laravel 12 |
| Frontend | Vue 3, Inertia.js, Tailwind CSS |
| Database | MySQL 8.0 |
| Build | Vite |
| Testing | PHPUnit, Vitest, Playwright |
| Infrastructure | Docker, Nginx |

---

## Installation

### Prerequisites

- [Docker](https://www.docker.com/get-started) and Docker Compose

### Quick Start

```bash
# 1. Clone the repository
git clone <repo-url>
cd champions-league-simulation

# 2. Copy environment file
cp .env.example .env

# 3. Build and start all containers, install dependencies, run migrations and seeders
make install

# 4. Open the app
open http://localhost:8080
```

The `make install` command handles everything: building Docker images, waiting for the database to be ready, installing Composer and npm dependencies, and running migrations with seed data.

### Environment Variables

The `.env.example` file contains sensible defaults that work with the Docker setup out of the box. The key values are:

```env
APP_URL=http://localhost:8080

DB_HOST=db
DB_PORT=3306
DB_DATABASE=champions_league
DB_USERNAME=sail
DB_PASSWORD=password
```

---

## Usage

### Docker Commands

```bash
make up          # Start all containers
make down        # Stop all containers
make build       # Rebuild containers from scratch (no cache)
make shell       # Open a bash shell inside the app container
make logs        # Tail logs from all containers
```

### Database

```bash
make migrate     # Run pending migrations
make seed        # Run seeders
make fresh       # Drop all tables, re-migrate, and re-seed
```

### Running Tests

```bash
# Backend (PHPUnit)
make test

# Frontend unit tests (Vitest)
make test-frontend

# End-to-end tests (Playwright)
make test-e2e
```

---

## Project Structure

```
.
├── app/
│   ├── Http/Controllers/   # Inertia controllers
│   ├── Models/             # Eloquent models (Team, Fixture, Match, etc.)
│   └── Services/           # Simulation & prediction logic
├── resources/
│   └── js/
│       ├── Pages/          # Vue page components
│       └── Components/     # Shared UI components
├── tests/
│   ├── Unit/               # PHPUnit unit tests
│   ├── Feature/            # PHPUnit feature/integration tests
│   └── e2e/                # Playwright end-to-end tests
├── docker/                 # Nginx config and Docker assets
├── docker-compose.yml
├── Dockerfile
└── Makefile
```

## License

MIT
