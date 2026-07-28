# Landing Presentation AI

Backend API и Vue-фронтенд формы обратной связи: анализ комментария через AI (Groq), сохранение обращения, email-уведомления, метрики и health-check.

---

## 1. Как запустить проект

### Требования

- Docker + Docker Compose
- PHP 8.4+ и Composer (если запуск без Docker; lock требует PHP ≥ 8.4.1)
- Ключ [Groq API](https://console.groq.com/)

### Быстрый старт (Docker)

```bash
cp .env.example .env
# Укажите GROQ_API_KEY и CONTACT_OWNER_EMAIL в .env

docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
npm install && npm run build
```

Сервисы:

| Сервис | URL |
|---|---|
| Фронтенд (форма) | http://localhost |
| API (nginx) | http://localhost |
| Swagger UI | http://localhost/docs |
| OpenAPI spec | http://localhost/docs/openapi.yaml |
| Mailpit (письма) | http://localhost:8025 |
| Redis | `localhost:6379` |

Проверка:

```bash
curl http://localhost/api/health
```

### Локальный запуск без Docker

```bash
cp .env.example .env
composer install
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install && npm run build
php artisan serve
```

Для почты и Redis в этом случае нужны свои SMTP/Redis или Docker только для `mailpit` и `redis`.  
При запуске API вне Docker-сети задайте `MAIL_HOST=127.0.0.1` и `REDIS_HOST=127.0.0.1`.

### Основные переменные окружения

```env
GROQ_API_KEY=...
AI_PROVIDER=groq
AI_MODEL=llama3-8b-8192

CONTACT_OWNER_EMAIL=owner@localhost
CONTACT_RATE_LIMIT_MAX=5
CONTACT_RATE_LIMIT_WINDOW=600

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025

CACHE_STORE=redis
REDIS_HOST=redis
DB_CONNECTION=sqlite
```

### Production Docker (один образ)

Корневой `Dockerfile` — multi-stage: Vite build → `composer install --no-dev` на PHP 8.4 → runtime с `pdo_sqlite` / `pdo_pgsql` / `pdo_mysql` / redis. Entrypoint гоняет миграции и слушает `$PORT` (по умолчанию 8080).

Локально (prod-like, без bind-mount исходников):

```bash
export APP_KEY="$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")"
export GROQ_API_KEY=your_key
export CONTACT_OWNER_EMAIL=you@example.com

docker compose -f docker-compose.prod.yml up --build
```

| Сервис | URL |
|---|---|
| Приложение | http://localhost:8080 |
| Health | http://localhost:8080/api/health |
| Mailpit UI | http://localhost:8025 |

Только образ:

```bash
docker build -t landing-presentation-ai:prod .
docker run --rm -p 8080:8080 \
  -e APP_KEY="$APP_KEY" \
  -e APP_ENV=production \
  -e APP_DEBUG=false \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=/var/www/html/database/database.sqlite \
  -e SESSION_DRIVER=file \
  -e CACHE_STORE=file \
  -e QUEUE_CONNECTION=sync \
  -e MAIL_MAILER=log \
  -e GROQ_API_KEY="$GROQ_API_KEY" \
  -e CONTACT_OWNER_EMAIL=you@example.com \
  landing-presentation-ai:prod
```

### Railway

В репозитории есть `railway.toml` (`builder = DOCKERFILE`). Не используйте Nixpacks с PHP 8.3 — lock тянет Symfony 8.1 (нужен PHP ≥ 8.4.1).

Обязательные переменные на сервисе:

```env
APP_KEY=base64:...
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.up.railway.app
LOG_CHANNEL=stderr
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
GROQ_API_KEY=...
CONTACT_OWNER_EMAIL=you@example.com
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=noreply@yourdomain.com
```

Для PostgreSQL на Railway замените `DB_*` на значения из плагина Postgres (`pdo_pgsql` уже в образе). При отдельном Redis — `CACHE_STORE=redis` и `REDIS_HOST`.

Healthcheck: `GET /api/health`.

---

## 2. Стек технологий и библиотек

- **Vue 3 + Vite + Tailwind CSS 4** — фронтенд формы, сборка в Blade (`@vite`)
- **PHP 8.4+ / Laravel 13**
- **Docker** — dev: `docker-compose.yml` (PHP-FPM + nginx); prod: корневой `Dockerfile` / `docker-compose.prod.yml`
- **SQLite** — легко заменить на MySQL/PostgreSQL
- **Redis**
- **Groq API** — LLM-анализ комментариев
- **justinrainbow/json-schema** — валидация JSON-ответа AI
- **propaganistas/laravel-phone** — валидация номера телефона
- **Guzzle** — HTTP-клиент к Groq
- **Mailpit** — локальный SMTP + UI писем
- **OpenAPI 3 / Swagger UI** — документация API

---

## 3. Архитектура

Модули разделены, связь Contact → AI только через anti-corruption layer.

```text
HTTP (Controller + FormRequest)
        │
        ▼
Contact module
  ├─ ContactService          — оркестрация сценария
  ├─ RateLimiter             — лимит по email
  ├─ CommentAnalyzer (port)  — анализ комментария
  │     └─ AiCommentAnalyzer — единственная точка вызова AI
  ├─ ContactRepository       — сохранение в БД
  └─ ContactNotifier         — письма владельцу и пользователю
        │
        ▼
AI module
  ├─ CommentAnalyzerInterface (port)
  ├─ GroqCommentAnalyzer (adapter)
  ├─ GroqApiClient / Prompt / Parser
  └─ JsonSchemaResponseValidator
```

### Паттерны

| Паттерн | Где |
|---|---|
| Port / Strategy | `CommentAnalyzerInterface`, `ContactHandlerInterface`, `RateLimiterInterface` |
| Adapter | `GroqCommentAnalyzer`, `AiCommentAnalyzer`, `LaravelContactNotifier` |
| Repository | `ContactRepositoryInterface` + `EloquentContactRepository` |
| Factory | выбор AI-провайдера в `AIServiceProvider` |
| DTO | `ContactDTO`, `CommentAnalysis`, `ContactMetricsDTO` |
| Dependency Injection | `AIServiceProvider`, `ContactServiceProvider` |

Pipeline запроса `POST /api/contact`:

1. Валидация запроса  
2. Rate limit  
3. AI-анализ комментария  
4. Сохранение в `contacts`  
5. Письмо владельцу + копия пользователю  
6. Ответ с `sentiment` / `type` / `ai_used`

---

## 4. Реализация API

Базовый префикс: `/api`

| Метод | Путь | Описание |
|---|---|---|
| `POST` | `/api/contact` | Принять обращение |
| `GET` | `/api/metrics` | Статистика обращений |
| `GET` | `/api/health` | Статус сервиса и зависимостей |

Документация: **http://localhost/docs**

### Примеры запросов (curl)

Базовый URL: `http://localhost` (nginx).  
`jq` опционален — можно убрать `| jq`, если его нет.

#### Health

```bash
curl -s -X GET http://localhost/api/health \
  -H 'Accept: application/json' | jq
```

Пример ответа:

```json
{
  "status": "ok",
  "service": "Laravel",
  "timestamp": "2026-07-28T16:00:00+00:00",
  "checks": {
    "app": { "status": "ok", "message": "Application is running" },
    "database": { "status": "ok", "message": "Database connection successful" },
    "cache": { "status": "ok", "message": "Cache is available (redis)" },
    "mail": { "status": "ok", "message": "SMTP mailpit:1025 reachable" },
    "ai": { "status": "ok", "message": "Groq provider configured" }
  }
}
```

#### Metrics

```bash
curl -s -X GET http://localhost/api/metrics \
  -H 'Accept: application/json' | jq
```

Пример ответа:

```json
{
  "total": 42,
  "by_sentiment": { "positive": 20, "neutral": 15, "negative": 7 },
  "by_type": { "question": 18, "feedback": 12, "complaint": 7, "general": 5 },
  "ai_used": 40,
  "last_24_hours": 3,
  "last_7_days": 14
}
```

#### Contact — успешное обращение

```bash
curl -s -X POST http://localhost/api/contact \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "name": "Ivan",
    "phone": "+79991234567",
    "email": "ivan@mail.com",
    "comment": "Хочу узнать подробности о вашем продукте."
  }' | jq
```

Пример ответа `201`:

```json
{
  "message": "Contact request accepted.",
  "sentiment": "positive",
  "type": "question",
  "ai_used": true
}
```

#### Contact — ошибка валидации (`422`)

```bash
curl -s -X POST http://localhost/api/contact \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "name": "I",
    "phone": "123",
    "email": "not-an-email",
    "comment": "short"
  }' | jq
```

#### Contact — с кодом HTTP в выводе

```bash
curl -s -w '\nHTTP %{http_code}\n' -X POST http://localhost/api/contact \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "name": "Ivan",
    "phone": "+79991234567",
    "email": "ivan@mail.com",
    "comment": "Хочу узнать подробности о вашем продукте."
  }'
```

#### Contact — rate limit (`429`)

После нескольких запросов с одним и тем же email (лимит: `CONTACT_RATE_LIMIT_MAX`):

```bash
for i in 1 2 3 4 5 6; do
  curl -s -w " #$i HTTP %{http_code}\n" -X POST http://localhost/api/contact \
    -H 'Content-Type: application/json' \
    -H 'Accept: application/json' \
    -d '{
      "name": "Ivan",
      "phone": "+79991234567",
      "email": "ratelimit@mail.com",
      "comment": "Повторный запрос для проверки лимита обращений."
    }'
done
```

Пример ответа `429`:

```json
{
  "message": "Too many requests. Try again later.",
  "retry_after": 600
}
```

> Письма после успешного `POST /api/contact` смотрите в Mailpit: http://localhost:8025

### `POST /api/contact`

Тело:

```json
{
  "name": "Ivan",
  "phone": "+79991234567",
  "email": "ivan@mail.com",
  "comment": "Хочу узнать подробности о вашем продукте."
}
```

Успех `201`:

```json
{
  "message": "Contact request accepted.",
  "sentiment": "positive",
  "type": "question",
  "ai_used": true
}
```

Основные ошибки:

| Код | Когда |
|---|---|
| `422` | Ошибка валидации |
| `429` | Rate limit |
| `401/4xx` | Ошибки AI-клиента / ключа |
| `502` | Невалидный ответ AI или сбой почты |
| `503/504` | Сбой/таймаут AI-провайдера |

### `GET /api/metrics`

```json
{
  "total": 42,
  "by_sentiment": { "positive": 20, "neutral": 15, "negative": 7 },
  "by_type": { "question": 18, "feedback": 12, "complaint": 7, "general": 5 },
  "ai_used": 40,
  "last_24_hours": 3,
  "last_7_days": 14
}
```

### `GET /api/health`

Проверки: `app`, `database`, `cache`, `mail`, `ai`.  
Статус: `ok` | `degraded` | `down` (`503` при `down`).

---

## 5. AI-интеграция

Провайдер по умолчанию — **Groq** (`AI_PROVIDER=groq`).

1. `GroqApiClient` отправляет system + user prompt  
2. Модель возвращает строго JSON  
3. `CommentAnalysisParser` извлекает content и декодирует JSON  
4. `JsonSchemaResponseValidator` проверяет ответ по схеме  
   [`app/Services/AI/Schemas/comment-analysis.json`](app/Services/AI/Schemas/comment-analysis.json)  
5. Результат мапится в DTO / enum (`Sentiment`, `CommentType`)

Ожидаемый формат ответа модели:

```json
{
  "sentiment": "positive | neutral | negative | unknown",
  "type": "question | feedback | complaint | general",
  "auto_reply": "string | null"
}
```

Ошибки HTTP Groq преобразуются в доменные исключения (`AIUnauthorizedException`, `AIServerException`, `AIConnectionException`, `AIInvalidResponseException` и т.д.) и на границе Contact-модуля — в `CommentAnalysisFailedException`.

---

## 6. Что сделано с помощью AI

Часть разработки велась с ассистентом (Cursor):

- проектирование модулей Contact / AI и разделение ответственности;
- рефакторинг к Port/Adapter/Repository и anti-corruption layer;
- валидация ответа LLM через JSON Schema;
- email-уведомления + Mailpit в Docker;
- `/metrics`, `/health`, OpenAPI/Swagger;
- логирование и обработка ошибок по сценариям contact/mail/AI.

Сам продукт тоже использует AI: классификация тональности и типа обращения, опциональный `auto_reply` в письме пользователю.

---

## 7. Хранение данных

### Таблица `contacts`

| Поле | Описание |
|---|---|
| `name`, `phone`, `email`, `comment` | Данные формы |
| `sentiment`, `type` | Результат AI-анализа |
| `auto_reply` | Автоответ (nullable) |
| `ai_used` | Использовался ли AI |
| `created_at`, `updated_at` | Временные метки |

Доступ через Repository:

- `ContactRepositoryInterface`
- `EloquentContactRepository`

Метрики считаются агрегациями по таблице в этом классе (`ContactMetricsService`).

По умолчанию используется **SQLite** (`database/database.sqlite`). Для production достаточно сменить `DB_*` в `.env` и выполнить миграции.

Дополнительно:

- **Redis** — rate limiting (`contact:{email}`) и cache;
- **Mailpit** — локальное хранение/просмотр писем (не продакшен-доставка);
- **Логи:** `storage/logs/contact-*.log`, `storage/logs/ai-*.log`, `storage/logs/laravel.log`.
