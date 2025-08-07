# IT Management System (ITMS) v1.5.1

## Overview
ITMS is a web-based application for managing IT assets, consumables, employees, and related transactions. It provides robust tracking, history, and reporting features for IT departments.

## Features
- Asset management with assignment and history tracking
- Consumables management with transaction history
- Employee and vendor management
- Pagination for all major lists (assets, consumables, history, employees)
- Modern UI with Tailwind CSS
- MySQL database with migration scripts
- Setup wizard for easy installation

## Requirements
- PHP 7.4+
- MySQL 5.7+
- XAMPP (recommended for local development)

## Installation
1. Clone the repository
2. Copy to your web server (e.g., `c:/xampp/htdocs/itms`)
3. Run `setup.php` in your browser to initialize the database and configuration
4. Login with default admin credentials: `admin` / `admin123`

## Upgrade to v1.5.1
- Run the migration script in `db/migrations/2025-08-07-itms-schema.sql` on your production database
 - All documentation and UI now reference version 1.5.1

## Version 1.5.1 (2025-08-07)
- Asset list now displays Model and Notes columns
- Asset import and modal display improvements
- Migration script for v1.5.1 included

## License
MIT License

---
Developed for efficient IT asset management.
