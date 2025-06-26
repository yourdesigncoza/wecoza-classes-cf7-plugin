# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Architecture Overview

This is a WordPress plugin that integrates with Contact Form 7 to dynamically populate select fields with client and site data from a PostgreSQL database. The plugin uses cascading dropdowns where selecting a client populates the available sites for that client.

### Core Components

- **Main Plugin File**: `wecoza-classes-cf7-plugin.php` - Singleton pattern initialization, hooks, and dependency loading
- **CF7 Integration**: `includes/class-cf7-integration.php` - Handles CF7 form modifications, AJAX endpoints, and validation fixes
- **Database Service**: `includes/class-database-service.php` - PostgreSQL connection and query management
- **Client Service**: `includes/class-client-service.php` - Client data retrieval with WordPress transient caching
- **Site Service**: `includes/class-site-service.php` - Site data retrieval with caching and client relationship management
- **Autoloader**: `includes/class-autoloader.php` - PSR-4 style class autoloading

### Key Architecture Patterns

- **Singleton Pattern**: All service classes use singleton instances
- **Service Layer**: Separate services for database, clients, and sites
- **Caching Layer**: WordPress transients for performance (1 hour TTL)
- **Multi-layer Validation**: CF7 5.9+ SWV schema fixes, traditional validation hooks, and client-side JavaScript fixes

## Database Schema

The plugin expects PostgreSQL tables:
- `clients` table with `client_id` and `client_name` columns
- `sites` table with `site_id`, `client_id`, `site_name`, and `address` columns

Database credentials are stored as WordPress options with prefix `wecoza_postgres_`.

## Contact Form 7 Integration

### Form Fields Expected
- `client_id` - Select field for client selection
- `site_id` - Select field for site selection (cascading)
- `site_address` - Text field for auto-populated address (read-only)

### Validation Fix Implementation
The plugin includes a comprehensive fix for CF7's "undefined value" validation error:

1. **SWV Schema Modification** (Primary): Removes enum validation for dynamic fields in CF7 5.9+
2. **Custom Enum Validation**: Replaces CF7's enum validation with database validation
3. **Traditional Hooks**: Backup validation using `wpcf7_validate_select` hooks
4. **Client-side Fixes**: JavaScript to handle `aria-invalid` attributes and validation state

## Testing

### Test Integration
- `test-cf7-integration.php` provides testing utilities
- Tests database connectivity, client service, site service, and CF7 integration
- Only loads in `WP_DEBUG` mode

### Manual Testing
Visit the test file directly to verify:
- Database connection status
- Client data retrieval
- Site data retrieval
- CF7 integration functionality

## Development Commands

This is a WordPress plugin with no build process. Development is done directly with PHP files.

### Testing
```bash
# Enable WordPress debug mode in wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

# View debug logs
tail -f wp-content/debug.log
```

### Database Testing
```bash
# Access test page (when WP_DEBUG is enabled)
# Visit: /wp-content/plugins/wecoza-classes-cf7-plugin/test-cf7-integration.php
```

## Plugin Activation/Deactivation

The plugin tests PostgreSQL connectivity on activation and will prevent activation if the database is unreachable. No database schema changes are made during activation.

## Caching Strategy

- **Cache Key**: `wecoza_cf7_clients` for client data
- **Cache Duration**: 1 hour (3600 seconds)
- **Cache Clearing**: Automatic on expiration, manual via service methods
- **Debug Mode**: Shows cache status and provides refresh buttons

## Security Considerations

- All database queries use prepared statements
- Client data is sanitized using WordPress functions
- AJAX requests include nonce verification
- Direct file access is prevented with `ABSPATH` checks

## File Structure Notes

- `legacy/` directory contains reference code but is not used by the active plugin
- `daily-updates/` contains development logs and reports
- `assets/js/cf7-integration.js` handles client-side cascading behavior and validation fixes
- `schema/classes_schema_3.sql` contains the expected PostgreSQL schema

## Troubleshooting

### Common Issues
1. **"Undefined value" errors**: Ensure CF7 validation fixes are active
2. **Database connection**: Check PostgreSQL credentials in WordPress options
3. **Empty dropdowns**: Verify database tables and data exist
4. **JavaScript errors**: Check browser console for CF7 integration issues

### Debug Information
Enable `WP_DEBUG` to access detailed logging and the test integration page.