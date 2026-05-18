# Uptime Monitor API

A Laravel 13 / PHP 8.4 REST API that monitors website availability, stores check history, and sends email alerts on status changes.

---

## Requirements

| Tool       | Version    |
|------------|------------|
| PHP        | ≥ 8.4      |
| Composer   | ≥ 2.x      |
| MySQL      | ≥ 8.0 (or MariaDB ≥ 10.6) |

---

## Quick Start

```bash
# 1. Clone and install dependencies
git clone https://github.com/Roymaniac/uptime-monitor.git
cd uptime-monitor
composer install

# 2. Configure environment
cp .env.example .env
php artisan key:generate

# 3. Edit .env – set DB_* and MAIL_* values, then:
php artisan migrate

# 4. (Optional) Seed with sample data
php artisan db:seed

# 5. Start the development server
composer run dev

# 6. Run schedules (in another terminal)
php artisan schedule:work

# 7. Start a queue worker (required for checks and notifications)
php artisan queue:work
```

---

## Environment Variables

| Variable               | Default                      | Description                                      |
|------------------------|------------------------------|--------------------------------------------------|
| `MONITOR_ALERT_EMAIL`  | falls back to `MAIL_FROM_ADDRESS` | Recipient for up/down alerts               |

## Running Tests

```bash
php artisan test
# or with coverage
php artisan test --coverage
```

Tests use an in-memory SQLite database by default (`phpunit.xml` sets `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`).

---

## API Reference

### `POST /api/monitors`

Register a URL to monitor.

**Request body**

```json
{
  "url": "https://example.com",
  "check_interval": 5,
  "threshold": 3
}
```

| Field            | Type    | Required | Description                                         |
|------------------|---------|----------|-----------------------------------------------------|
| `url`            | string  | Yes      | Valid, unique HTTPS/HTTP URL                        |
| `check_interval` | integer | No       | Minutes between checks. Default `5`, min `1`, max `60` |
| `threshold`      | integer | No       | Consecutive failures before marking down. Default `3`, min `1` |

**201 Created**

```json
{
  "data": {
    "id": 1,
    "url": "https://example.com",
    "check_interval": 5,
    "threshold": 3,
    "status": "pending",
    "last_checked_at": null,
    "uptime_percentage": null,
    "created_at": "2026-05-13T10:00:00.000000Z"
  }
}
```

**422 Unprocessable Entity** (validation failure or duplicate URL)

```json
{
  "message": "The url field is required.",
  "errors": {
    "url": ["The url field is required."]
  }
}
```

---

### `GET /api/monitors`

List all registered monitors with their current status.

**200 OK**

```json
{
  "data": [
    {
      "id": 1,
      "url": "https://example.com",
      "check_interval": 5,
      "threshold": 3,
      "status": "up",
      "last_checked_at": "2026-05-13T10:05:00.000000Z",
      "uptime_percentage": 99.5,
      "created_at": "2026-05-13T10:00:00.000000Z"
    }
  ]
}
```

---

### `GET /api/monitors/{id}/history`

Paginated check history for a monitor, newest first.

**Query parameters**

| Param      | Type    | Default | Max |
|------------|---------|---------|-----|
| `page`     | integer | 1       | —   |
| `per_page` | integer | 15      | 100 |

**200 OK**

```json
{
  "data": [
    {
      "id": 1,
      "monitor_id": 1,
      "status_code": 200,
      "response_time_ms": 245,
      "is_up": true,
      "checked_at": "2026-05-13T10:05:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 50
  }
}
```

**404 Not Found**

```json
{ "message": "Monitor not found." }
```

---

## License MIT License

Copyright (c) 2026 Roymaniac
