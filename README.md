# Aether Social PWA

A high-performance, real-time Social PWA built for zero-budget, shared-hosting environments.

## Architecture: The 'Burst-Pulse' System

Aether uses a hybrid Long-Polling architecture to simulate real-time behavior without WebSockets (which are often blocked on shared hosts).
- **Pulse**: The frontend sends a `POST /api/sync/heartbeat` request that stays open for up to 25 seconds.
- **Burst**: When a message is sent, the backend "touches" `storage/chat_lock.meta`. The open heartbeat pulse detects this change in file modification time and immediately queries the database for new messages.

## Tech Stack

- **Backend**: PHP 8.2+ (PSR-4 Autoloading)
- **Database**: MySQL/MariaDB (PDO)
- **Authentication**: Stateless JWT (`firebase/php-jwt`)
- **Frontend**: jQuery 3.7, Bootstrap 5.3, TailwindCSS (Play CDN)
- **PWA**: Service Worker with Cache-First strategy for assets.

## Build & Setup Settings

### 1. Environment Configuration
Create `app/config/env.php` (if not present):
```php
return [
    'DB_HOST' => '127.0.0.1',
    'DB_NAME' => 'your_db',
    'DB_USER' => 'root',
    'DB_PASS' => 'your_pass',
    'DB_CHARSET' => 'utf8mb4',
    'APP_KEY' => 'your_32_byte_hex_key',
    'APP_URL' => 'http://localhost',
    'DEBUG'   => true
];
```

### 2. Dependencies
Install PHP dependencies via Composer:
```bash
composer install
```
*Note: If `php-jwt` is blocked by security advisories in your environment, use:*
`composer config audit.ignore PKSA-y2cr-5h3j-g3ys && composer install`

### 3. Database Initialization
Execute the schema provided in `setup_aether.sql` against your MySQL database.

### 4. Styling (SCSS)
If you modify the SCSS files in `assets/scss/`, compile them using:
```bash
npx sass assets/scss/style.scss assets/css/style.css
```

### 5. URL Rewriting
Ensure your Apache server has `mod_rewrite` enabled. The `.htaccess` file handles routing all non-file requests to `index.php`.

### 6. Permissions
The `storage/` directory must be writable by the web server:
```bash
chmod 777 storage
```

## Directory Structure

- `app/config/`: Sensitive configuration.
- `src/Core/`: MVC core (Application, Router, Database).
- `src/Controllers/`: Request handlers.
- `src/Utilities/`: Security and helper methods.
- `storage/`: Lockfiles and persistent temporary data.
- `assets/`: Frontend CSS, JS, and Icons.
- `api/`: (Legacy) Compatibility endpoints.
