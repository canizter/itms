# IT Management System (ITMS)

A comprehensive web-based solution for managing IT assets, built with PHP, MySQL, HTML, CSS, and JavaScript for use with XAMPP.

## Features

### 🔐 User Management
- **Role-based access control** (Admin, Manager, User)
- **Secure authentication** with password hashing
- **Session management** with auto-logout
- **User activity tracking**

### 📋 Asset Management
- **Complete asset lifecycle** tracking
- **Asset categories** and classifications
- **Vendor management** and relationships
- **Location tracking** and assignments
- **Status management** (Active, Inactive, Maintenance, Disposed)
- **Purchase information** and warranty tracking
- **Asset history** and audit trails

### 💾 Software License Management
- **Software license tracking**
- **License type management** (Perpetual, Subscription, OEM)
- **Seat allocation** and usage monitoring
- **Expiry date tracking**

### 📊 Reporting & Analytics
- **Dashboard overview** with key metrics
- **Asset reports** by category, status, location
- **Warranty expiry alerts**
- **Export functionality** (CSV)
- **Print-friendly** reports

### 🔍 Advanced Features
- **Powerful search** and filtering
- **Pagination** for large datasets
- **Responsive design** for mobile devices
- **Real-time notifications**
- **Data validation** and error handling

## System Requirements

- **XAMPP** (Apache + MySQL + PHP 7.4+)
- **Web Browser** (Chrome, Firefox, Safari, Edge)
- **Minimum 100MB** disk space
- **1GB RAM** recommended

## Installation Instructions

### 1. Setup XAMPP
1. Download and install [XAMPP](https://www.apachefriends.org/download.html)
2. Start **Apache** and **MySQL** services from XAMPP Control Panel

### 2. Database Setup
1. Open **phpMyAdmin** in your browser: `http://localhost/phpmyadmin`
2. Create a new database or import the provided SQL file:
   - Navigate to `c:\xampp\htdocs\itms\database\itms_database.sql`
   - Import this file into phpMyAdmin to create the database and tables
   - Alternatively, copy the SQL content and execute it in phpMyAdmin

### 3. File Setup
1. Extract/copy all ITMS files to: `c:\xampp\htdocs\itms\`
2. Ensure the following folder structure exists:
   ```
   c:\xampp\htdocs\itms\
   ├── config/
   ├── includes/
   ├── assets/css/
   ├── assets/js/
   ├── database/
   ├── uploads/ (create this folder)
   └── *.php files
   ```

### 4. Configuration
1. Open `config/config.php`
2. Verify database settings:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USERNAME', 'root');
   define('DB_PASSWORD', ''); // Default XAMPP password is empty
   define('DB_NAME', 'itms_db');
   ```
3. Create the `uploads` folder for file attachments:
   ```
   mkdir c:\xampp\htdocs\itms\uploads
   ```

### 5. Access the System
1. Open your web browser
2. Navigate to: `http://localhost/itms`
3. Login with default credentials:
   - **Username:** `admin`
   - **Password:** `admin123`

## Default Data

The system comes pre-loaded with:
- **Sample categories:** Desktop Computers, Laptops, Servers, etc.
- **Sample vendors:** Dell, HP, Lenovo, Cisco, Microsoft, Apple
- **Sample locations:** IT Department, Server Room, Reception, etc.
- **Admin user:** username `admin`, password `admin123`

## User Roles

### 👑 Admin
- Full system access
- User management
- System configuration
- Delete assets and data

### 👔 Manager  
- Asset management (add, edit)
- Generate reports
- Manage categories, vendors, locations
- View all data

### 👤 User
- View assets
- Search and filter
- Generate basic reports
- Read-only access

## Usage Guide

### Adding Your First Asset
1. Login to the system
2. Navigate to **Assets** → **Add New Asset**
3. Fill in the required information:
   - Asset Tag (unique identifier)
   - Name and Description
   - Category, Vendor, Location
   - Purchase details
   - Warranty information
4. Click **Save Asset**

### Managing Categories
1. Go to **Categories** in the navigation
2. Add new categories for your organization
3. Edit existing categories as needed
4. Categories help organize and filter assets

### Setting Up Locations
1. Navigate to **Locations**
2. Add your organization's locations
3. Include building, floor, and room details
4. Assign assets to specific locations

### Generating Reports
1. Go to **Reports** section
2. Select report type and filters
3. Choose date ranges and criteria
4. Export to CSV or print

## Security Features

- **Password hashing** using PHP's secure algorithms
- **CSRF protection** on all forms
- **Input validation** and sanitization
- **Session timeout** after inactivity
- **SQL injection** protection using prepared statements
- **XSS protection** with output escaping

## Troubleshooting

### Common Issues

**1. Can't access the system**
- Ensure XAMPP Apache and MySQL are running
- Check if the URL is correct: `http://localhost/itms`
- Verify files are in the correct directory

**2. Database connection errors**
- Check MySQL service is running in XAMPP
- Verify database credentials in `config/config.php`
- Ensure `itms_db` database exists

**3. Login issues**
- Use default credentials: `admin` / `admin123`
- Check if users table was created properly
- Clear browser cache and cookies

**4. Permission errors**
- Ensure web server has read/write access to files
- Check `uploads` folder exists and is writable

### Error Logging
- PHP errors are logged in XAMPP logs folder
- Check `c:\xampp\apache\logs\error.log`
- Enable error reporting in development

## Customization

### Adding New Fields
1. Modify database tables in `database/itms_database.sql`
2. Update corresponding PHP forms
3. Adjust CSS styling as needed

### Changing Appearance
1. Edit `assets/css/style.css`
2. Modify color schemes, fonts, layout
3. Update logo and branding elements

### Adding Features
1. Create new PHP pages following existing patterns
2. Add navigation links in `includes/header.php`
3. Implement proper access controls

## Support

For technical support or questions:
- Check the troubleshooting section above
- Review PHP error logs
- Ensure all requirements are met
- Verify database structure is correct


## Version History


**v1.4.0** - August 2025
- Model selection (dependent on vendor) added to Add/Edit Asset
- Note/Remarks field added to Edit Asset
- Removed asset import popup modal; import now redirects to a dedicated page
- Updated status mapping and UI for asset addition
- Bugfixes for import/export and deployment
- UI/UX improvements to asset forms
- Version bump to 1.4.0

**v1.2.0** - Asset import status fix, asset_tag uniqueness enforced, and other improvements
**v1.0.0** - Initial Release
- Core asset management functionality
- User authentication and roles
- Basic reporting capabilities
- Responsive web interface

---

**© 2025 IT Management System**  
*Built for efficient IT asset tracking and management*
