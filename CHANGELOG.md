
# Changelog
- Footer and documentation updated to version 1.5

# [1.5.4] - 2025-08-09

### Changed
- Dashboard statistics queries now use correct status values (active/inactive/maintenance).
- Recent assets table now includes model and serial number columns.
- Removed 'Manage your IT inventory assets' text from assets page.
- Added full schema SQL to migrations folder.
### Added
- Asset import now records assignment history in asset_assignments when assigning via CSV

## [1.5.2] - 2025-08-07
### Fixed
- Asset import: assigned_to_employee_id is now always NULL if blank or not found (prevents SQL error)

## [1.5.1] - 2025-08-07
### Added
- Model column to asset list (between Vendor and Serial Number)
- Notes column to asset list (between Assigned Employee Name and Actions)
- Migration script for v1.5.1
### Fixed
- Asset import and modal display for notes/remarks

## [1.5] - 2025-08-07
### Added
- Full migration script for current schema
- Pagination for assets, consumables, and history pages (matching employee list style)
- Improved documentation and versioning in all files

### Changed
- Footer and documentation updated to version 1.5
- Setup wizard documentation updated

### Fixed
- Directory creation for migrations
- Minor UI/UX improvements

---
See previous releases for earlier changes.
