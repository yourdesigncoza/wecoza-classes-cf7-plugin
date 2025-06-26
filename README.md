# WeCoza Classes CF7 Plugin

A WordPress plugin that dynamically populates Contact Form 7 select fields with client data from a PostgreSQL database.

## Features

- **Dynamic Client Population**: Automatically populates CF7 select fields with client data from PostgreSQL
- **Cascading Site Selection**: Site dropdown automatically populates based on selected client
- **Automatic Address Population**: Address field automatically populates based on selected site
- **Caching**: Implements WordPress transient caching for improved performance (clients and sites)
- **MVC Architecture**: Clean, organized code structure following WordPress best practices
- **Error Handling**: Comprehensive error handling and logging
- **Security**: Proper sanitization and validation of all data
- **AJAX Support**: Real-time cascading updates and optional refresh functionality
- **Form Validation**: Enhanced validation for client and site selections with read-only address field
- **CF7 Validation Fix**: Custom validation hooks prevent "undefined value" errors for dynamic options

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Contact Form 7 plugin
- PostgreSQL database access
- PDO PostgreSQL extension

## Installation

1. Upload the plugin files to `/wp-content/plugins/wecoza-classes-cf7-plugin/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure PostgreSQL database settings (see Configuration section)

## Configuration

### Database Settings

The plugin uses WordPress options to store PostgreSQL connection details. Set these options in your WordPress database or via code:

```php
// Set PostgreSQL connection details
update_option('wecoza_postgres_host', 'your-postgres-host');
update_option('wecoza_postgres_port', '5432');
update_option('wecoza_postgres_dbname', 'your-database-name');
update_option('wecoza_postgres_user', 'your-username');
update_option('wecoza_postgres_password', 'your-password');
```

### Database Schema

The plugin expects `clients` and `sites` tables with the following structure:

```sql
CREATE TABLE public.clients (
    client_id SERIAL PRIMARY KEY,
    client_name VARCHAR(255) NOT NULL
);

CREATE TABLE public.sites (
    site_id SERIAL PRIMARY KEY,
    client_id INTEGER REFERENCES clients(client_id),
    site_name VARCHAR(255) NOT NULL,
    address TEXT
);
```

## Usage

### Basic CF7 Form Setup

Create a Contact Form 7 form with cascading fields:

```
[select* client_id class:form-select include_blank]
[select* site_id class:form-select include_blank]
[text site_address class:form-control readonly]
```

The plugin will automatically:
1. Detect the `client_id`, `site_id`, and `site_address` fields
2. Populate client dropdown with data from PostgreSQL
3. Initially disable site dropdown with "Select Site" option
4. When client is selected, enable and populate site dropdown with that client's sites
5. When site is selected, automatically populate the read-only address field
6. Maintain proper form validation and cascading behavior

### Generated HTML

The plugin transforms your CF7 shortcodes into cascading dropdowns:

**Client Dropdown:**
```html
<select class="wpcf7-form-control wpcf7-select wpcf7-validates-as-required form-select"
        aria-required="true" aria-invalid="false" name="client_id"
        data-wecoza-dynamic="client_id">
    <option value="">—Please choose an option—</option>
    <option value="1">Client Name 1</option>
    <option value="2">Client Name 2</option>
    <!-- ... more clients ... -->
</select>
```

**Site Dropdown (initially disabled):**
```html
<select class="wpcf7-form-control wpcf7-select wpcf7-validates-as-required form-select"
        aria-required="true" aria-invalid="false" name="site_id"
        data-wecoza-dynamic="site_id" data-depends-on="client_id" disabled="disabled">
    <option value="">Select Site</option>
</select>
```

**Address Field (read-only):**
```html
<input class="wpcf7-form-control form-control" readonly="readonly"
       name="site_address" data-wecoza-dynamic="site_address" data-depends-on="site_id"
       placeholder="Address will appear when site is selected" value="">
```

### Cascading Behavior

The plugin implements intelligent cascading dropdown behavior:

1. **Initial State**:
   - Client dropdown is populated and enabled
   - Site dropdown shows "Select Site" and is disabled

2. **Client Selection**:
   - Site dropdown enables and populates with sites for selected client
   - Previous site selection is cleared

3. **Site Selection**:
   - Address field automatically populates with selected site's address
   - Address field becomes visually validated

4. **Client Change**:
   - Site dropdown resets and repopulates with new client's sites
   - Address field is cleared automatically
   - Form validation prevents invalid client-site combinations

5. **Site Change**:
   - Address field updates with new site's address
   - Previous address is replaced

6. **AJAX Updates**:
   - Site data is fetched dynamically via AJAX
   - Address updates happen instantly without server requests
   - Loading states provide user feedback
   - Error handling for failed requests

### Form Validation

The plugin enhances CF7's built-in validation:
- Both client and site fields are validated as required
- Address field is automatically populated and read-only
- Custom validation prevents form submission with invalid combinations
- **CF7 Validation Fix**: Resolves "undefined value" errors for dynamic options
- Database validation ensures submitted values exist and are valid
- Real-time validation feedback with visual indicators
- Form submission includes client ID, site ID, and site address

## Testing

Visit `/wp-content/plugins/wecoza-classes-cf7-plugin/test-cf7-integration.php` to test the integration and verify database connectivity.

## Troubleshooting

### "Undefined value was submitted through this field" Error

This error occurs when Contact Form 7 validates select fields against the original form definition, but the options are populated dynamically. The plugin includes a fix for this:

**Solution Implemented:**
- **CF7 5.9+ Schema-based Validation (SWV) Fix**: Modifies validation schema to handle dynamic options
- Custom CF7 validation hooks (`wpcf7_validate_select` and `wpcf7_validate_select*`)
- Database validation instead of form definition validation
- Enum validation removal for dynamic fields with custom replacement
- Proper handling of dynamic options for `client_id` and `site_id` fields

**If you still see this error:**
1. Ensure the plugin is active and properly loaded
2. Check that field names are exactly `client_id` and `site_id`
3. Verify database connectivity using the test page
4. Check browser console for JavaScript errors that might prevent proper option population

### Contact Form 7 Version Compatibility

**CF7 5.9+ (Schema-based Validation)**
- The plugin automatically detects CF7 5.9+ and applies SWV schema fixes
- Removes enum validation for dynamic fields while preserving it for other fields
- Multiple fallback approaches ensure maximum compatibility

**CF7 5.8 and Earlier**
- Uses traditional validation hooks (`wpcf7_validate_select`)
- Full backward compatibility maintained

**Recommended CF7 Version**: 5.9+ for best performance and validation handling

## API Reference

### Client Service

```php
// Get client service instance
$client_service = WeCoza_Client_Service::get_instance();

// Get all clients
$clients = $client_service->get_clients();

// Get client by ID
$client = $client_service->get_client_by_id(123);

// Search clients
$results = $client_service->search_clients('search term');

// Clear cache
$client_service->clear_cache();
```

### Database Service

```php
// Get database service instance
$db_service = WeCoza_Database_Service::get_instance();

// Test connection
$is_connected = $db_service->test_connection();

// Execute query
$stmt = $db_service->query("SELECT * FROM clients WHERE client_id = ?", [123]);
```

## Caching

The plugin implements WordPress transient caching:

- **Cache Duration**: 1 hour (3600 seconds)
- **Cache Key**: `wecoza_cf7_clients`
- **Auto-refresh**: Cache is automatically refreshed when expired
- **Manual refresh**: Available via AJAX (in debug mode)

### Cache Management

```php
// Get cache status
$cache_status = $client_service->get_cache_status();

// Clear cache manually
$client_service->clear_cache();

// Refresh cache
$fresh_data = $client_service->refresh_cache();
```

## Debugging

Enable WordPress debug mode to access additional features:

```php
define('WP_DEBUG', true);
```

Debug features include:
- Detailed error logging
- Connection status information
- Cache status monitoring
- AJAX refresh buttons (frontend)

## Security

The plugin implements several security measures:

- **Data Sanitization**: All client data is sanitized using WordPress functions
- **SQL Injection Prevention**: Uses prepared statements for all database queries
- **CSRF Protection**: AJAX requests include nonce verification
- **Input Validation**: Client IDs are validated before database operations

## Error Handling

The plugin handles various error scenarios:

- **Database Connection Failures**: Graceful fallback with error logging
- **Missing Client Data**: Shows "No clients available" option
- **CF7 Not Active**: Displays admin notice
- **Invalid Queries**: Comprehensive error logging

## Hooks and Filters

### Available Hooks

```php
// Modify client data before caching
add_filter('wecoza_cf7_clients_data', function($clients) {
    // Modify $clients array
    return $clients;
});

// Customize blank option label
add_filter('wecoza_cf7_blank_option_label', function($label) {
    return 'Custom blank option text';
});
```

## File Structure

```
wecoza-classes-cf7-plugin/
├── wecoza-classes-cf7-plugin.php    # Main plugin file
├── includes/
│   ├── class-autoloader.php         # Autoloader
│   ├── class-database-service.php   # Database service
│   ├── class-client-service.php     # Client data service
│   └── class-cf7-integration.php    # CF7 integration
├── assets/
│   └── js/
│       └── cf7-integration.js       # Frontend JavaScript
├── legacy/                          # Reference code (not used)
└── README.md                        # This file
```

## Troubleshooting

### Common Issues

1. **"No clients available"**: Check database connection and table structure
2. **Select field not populated**: Ensure field name is exactly `client_id`
3. **Database connection errors**: Verify PostgreSQL credentials and network access
4. **CF7 not detected**: Ensure Contact Form 7 plugin is active

### Debug Information

Access debug information programmatically:

```php
$debug_info = WeCoza_CF7_Integration::get_instance()->get_debug_info();
print_r($debug_info);
```

## License

GPL v2 or later

## Support

For support and bug reports, please contact Your Design Co.
