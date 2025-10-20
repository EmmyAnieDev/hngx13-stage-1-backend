# HNGX13 Stage 1 — String Analyzer Service (PHP)

A **RESTful API** built for **HNGX13 Backend Wizards — Stage 1 Task**.  
It analyzes strings and stores their computed properties including length, palindrome detection, character frequency, and more.

---

## 🚀 Features

- **POST /strings** — Analyze and store string properties
- **GET /strings/{string_value}** — Retrieve a specific analyzed string
- **GET /strings** — Get all strings with advanced filtering options
- **GET /strings/filter-by-natural-language** — Natural language query support
- **DELETE /strings/{string_value}** — Remove a string from the system
- Computes:
  - String length
  - Palindrome detection (case-insensitive)
  - Unique character count
  - Word count
  - SHA-256 hash for unique identification
  - Character frequency mapping
- Proper error handling with appropriate HTTP status codes
- Clean, modular PHP structure with Docker support
- Deployed to **EC2 Instance**

---

## 🧠 Tech Stack

- **Language:** PHP 8+
- **Database:** PostgreSQL
- **Web Server:** Nginx
- **Containerization:** Docker
- **Hosting:** EC2 Instance

---

## 📁 Project Structure

| File / Folder | Description |
|----------------|-------------|
| `nginx/` | Nginx configuration directory |
| `default.conf` | Nginx server configuration |
| `public/` | Public web directory |
| `index.php` | Main entry point — handles routing |
| `scripts/` | Database initialization scripts |
| `db_init.php` | Database setup and table creation |
| `src/` | Source code directory |
| `DB.php` | Database connection and operations |
| `StringAnalyzer.php` | Core string analysis logic |
| `vendor/` | Composer dependencies (auto-generated) |
| `.env` | Environment variables (not tracked) |
| `.env.sample` | Sample environment configuration |
| `.gitignore` | Git ignore file |
| `composer.json` | Composer dependencies and autoload config |
| `composer.lock` | Composer lock file (dependency versions) |
| `config.php` | Application configuration |
| `docker-compose.yml` | Docker services orchestration |
| `Dockerfile` | Docker image configuration |
| `README.md` | Documentation file (this one) |

---

## ⚙️ Installation & Setup

Follow the steps below to set up and run this project locally.

### 1. Clone the repository
```bash
git clone https://github.com/EmmyAnieDev/hngx13-stage-1-backend.git
cd hngx13-stage-1-backend
```

### 2. Install dependencies

Make sure you have Composer installed.
Then install required PHP packages:
```bash
composer install
```

### 3. Configure environment variables

Copy the sample environment file:
```bash
cp .env.sample .env
```

Edit `.env` with your database credentials:
```env
DB_HOST=db
DB_PORT=5432
DB_NAME=string_analyzer
DB_USER=root
DB_PASSWORD=your_password
```

---

## 🐳 Running with Docker

### Prerequisites
- Docker
- Docker Compose

### Start the application
```bash
docker-compose up --build -d
```

This will start:
- **PHP-FPM** container
- **PostgreSQL** database container
- **Nginx** web server

The API will be available at `http://localhost`

---

## ▶️ Running Locally (Without Docker)

### Prerequisites
- PHP 8.0+
- PostgreSQL v12+
- Composer

### 1. Start PostgreSQL
Ensure your PostgreSQL service is running.

### 2. Update environment variables
Edit `.env` with your local PostgreSQL credentials.


### 3. Start PHP development server
```bash
php -S localhost:8000 -t public
```

---

## 🧪 API Endpoints & Testing

### 1. Create/Analyze String
```bash
curl -X POST http://localhost/strings \
  -H "Content-Type: application/json" \
  -d '{"value": "hello world"}' | jq
```

**Expected Response (201 Created):**
```json
{
  "id": "b94d27b9934d3e08a52e52d7da7dabfac484efe37a5380ee9088f7ace2efcde9",
  "value": "hello world",
  "properties": {
    "length": 11,
    "is_palindrome": false,
    "unique_characters": 8,
    "word_count": 2,
    "sha256_hash": "b94d27b9934d3e08a52e52d7da7dabfac484efe37a5380ee9088f7ace2efcde9",
    "character_frequency_map": {
      "h": 1,
      "e": 1,
      "l": 3,
      "o": 2,
      " ": 1,
      "w": 1,
      "r": 1,
      "d": 1
    }
  },
  "created_at": "2025-10-20T10:00:00Z"
}
```

### 2. Get Specific String
```bash
curl -X GET http://localhost/strings/hello%20world | jq
```

### 3. Get All Strings with Filters
```bash
curl -X GET "http://localhost/strings?is_palindrome=true&min_length=3&max_length=10" | jq
```

**Expected Response (200 OK):**
```json
{
  "data": [
    {
      "id": "hash_value",
      "value": "racecar",
      "properties": { ... },
      "created_at": "2025-10-20T10:00:00Z"
    }
  ],
  "count": 1,
  "filters_applied": {
    "is_palindrome": true,
    "min_length": 3,
    "max_length": 10
  }
}
```

### 4. Natural Language Filtering
```bash
curl -X GET "http://localhost/strings/filter-by-natural-language?query=all%20single%20word%20palindromic%20strings" | jq
```

**Expected Response (200 OK):**
```json
{
  "data": [ ... ],
  "count": 3,
  "interpreted_query": {
    "original": "all single word palindromic strings",
    "parsed_filters": {
      "word_count": 1,
      "is_palindrome": true
    }
  }
}
```

### 5. Delete String
```bash
curl -X DELETE http://localhost/strings/hello%20world
```

**Expected Response:** 204 No Content

---

## 📋 Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `is_palindrome` | boolean | Filter palindromic strings (true/false) |
| `min_length` | integer | Minimum string length |
| `max_length` | integer | Maximum string length |
| `word_count` | integer | Exact word count |
| `contains_character` | string | Single character to search for |

---

## 🚨 Error Responses

| Status Code | Description |
|-------------|-------------|
| 400 | Bad Request — Invalid request body or query parameters |
| 404 | Not Found — String does not exist |
| 409 | Conflict — String already exists |
| 422 | Unprocessable Entity — Invalid data type or conflicting filters |

---

## 🧰 Dependencies

| Package | Description |
|---------|-------------|
| php | PHP runtime (>= 8.0) |
| PostgreSQL | PostgreSQL database |
| nginx | Web server |
| docker | Containerization platform |

Install PHP dependencies via Composer:
```bash
composer install
```

---

## 🔐 Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_HOST` | Database host | `db` |
| `DB_PORT` | Database port | `5432` |
| `DB_NAME` | Database name | `string_analyzer` |
| `DB_USER` | Database user | `root` |
| `DB_PASSWORD` | Database password | Required |

---

## 🧹 Stopping the Application
```bash
docker-compose down
```

To remove volumes as well:
```bash
docker-compose down -v
```

---

## 💬 Author

**Emmy Anie**  
📧 emmanuelekwere19@gmail.com  
🐙 GitHub: [EmmyAnieDev](https://github.com/EmmyAnieDev)
