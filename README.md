# IT Management System (ITMS) v1.6.1

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



## Upgrade to v1.6.1
- All documentation and UI now reference version 1.6.1

## Version 1.6.1 (2025-08-11)
- Search module UI improved: now 2 rows, left/right aligned, more compact
- Serial number textbox removed from search
- All dropdowns and text boxes resized for consistency
- Reset button moved to the right of Search button
- Bug fixes and usability improvements

## Version 1.6 (2025-08-09)
- Modernized and compact UI for all management pages
- Consistent horizontal scroll for all main data tables
- Asset management actions are now modal-only (no inline Edit/Delete/Assignment)
- Improved modal status badges and button placement
- Font size reduced for a more compact look
- Bug fixes and usability improvements

## License
MIT License

---

**Author:** Jasper Sevilla  
**AI Assistant:** GitHub Copilot  
Developed for efficient IT asset management.
