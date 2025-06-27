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
