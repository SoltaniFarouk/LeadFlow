# LeadFlow

PHP 8 lead management system — batch processing, Vicidial CRM integration, multi-database architecture.

## Architecture
LeadFlow/

├── app/
│   ├── Connection/         # PDO connection classes (env-based)
│   ├── Controller/         # Controllers
│   ├── Model/              # Data models
│   ├── Repository/         # SQL queries (one class per domain)
│   └── Service/            # Business logic
├── config/
│   ├── autoload.php        # PSR-4 autoloader + dotenv loader
│   └── config_conn.php     # Connection functions
├── cron/                   # CLI scripts (scheduled jobs)
├── public/                 # Web entry points
├── scripts/                # Utility scripts
├── vendor/                 # Composer dependencies (not committed)
├── .env                    # Environment variables (not committed)
├── .env.example            # Template for .env
└── composer.json

## Requirements

- PHP 8.3+
- MySQL 5.7+ / MariaDB
- Composer 2.x
- PHP extensions: `pdo_mysql`, `zip`, `mbstring`
