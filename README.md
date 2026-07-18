# RRD WooCommerce Integration Plugin

## Overview

This WordPress/WooCommerce plugin integrates order submission with the RRD `createorder` API using Basic HTTP Authentication and JSON payloads.

**Version:** 0.1.0  
**Author:** Darick L. Quinto  
**Status:** Development - Steps 1 & 2 Complete

---

## Current Features (Steps 1 & 2)

### ✅ Plugin Setup

- Plugin header with proper WordPress standards
- Activation/deactivation hooks
- Admin menu with main dashboard
- Initialization of default options

### ✅ Settings Page

- Secure credential storage in WordPress options
- Environment selector (QA/Staging vs Production)
- Client ID configuration
- API Username storage
- API Password storage (masked in logs)
- Displays both API endpoints for reference
- Form validation and nonce protection
- Success confirmation on save

### ✅ Helper Functions

- `rrd_get_api_endpoint()` — Returns QA or Production endpoint based on environment
- `rrd_get_basic_auth_header()` — Generates Basic Auth header
- `rrd_get_api_headers()` — Returns all required API headers
- `rrd_log()` — Logs to WordPress error log with masking
- `rrd_mask_sensitive_data()` — Masks credentials in logs
- `rrd_validate_configuration()` — Validates all credentials are set

---

## Requirements

Before installing this plugin, ensure:

- ✅ WordPress is running
- ✅ WooCommerce plugin is active
- ✅ PHP 7.4 or higher
- ✅ Administrator access to WordPress admin

## Installation & Setup

### 1. Copy Plugin to WordPress

Copy the `rrd-woocommerce-integration` folder to `/wp-content/plugins/`

### 2. Activate in WordPress Admin

- Go to **Plugins** → Find "RRD WooCommerce Integration" → Click **Activate**

### 3. Configure Credentials

- Go to **RRD Integration** → **Settings**
- Enter your RRD credentials:
  - Environment: Select **QA** (for testing)
  - Client ID: `ESTRELLITA01`
  - API Username: `sfpassquser`
  - API Password: `c3Q5mzgYf7HpkX2`
- Click **Save Settings**

---

## File Structure

```
rrd-woocommerce-integration/
├── rrd-woocommerce-integration.php  (Main plugin file)
├── includes/
│   └── helpers.php                   (Helper functions for API communication)
└── README.md                         (This file)
```

---

## API Endpoints

| Environment      | URL                                                 |
| ---------------- | --------------------------------------------------- |
| **QA / Staging** | `https://api85-qa.rrd.com/corporate/v1/createorder` |
| **Production**   | `https://api85.rrd.com/corporate/v1/createorder`    |

---

## Configuration Stored in WordPress Options

| Option Key         | Purpose                      |
| ------------------ | ---------------------------- |
| `rrd_environment`  | QA or Production environment |
| `rrd_client_id`    | RRD Client ID                |
| `rrd_api_username` | Basic Auth Username          |
| `rrd_api_password` | Basic Auth Password          |

All credentials are stored in `wp_options` and masked when logged.

---

## Security Notes

- ✅ Credentials are stored securely in WordPress options
- ✅ Password fields use `type="password"` in admin UI
- ✅ All logs mask sensitive authentication data
- ✅ Nonce verification on settings form
- ✅ Only accessible to users with `manage_options` capability

---

## Troubleshooting

### Settings not saving?

- Ensure you have administrator access
- Check browser console for JavaScript errors
- Verify nonce field is present in form

### Credentials not working?

- Double-check the exact values from RRD
- Test in QA environment first before switching to Production
- Check WordPress error logs: `/wp-content/debug.log`

---

## Development Notes

This plugin is built with a step-by-step approach. Each step adds functionality and validates with the QA endpoint before moving forward.

- Uses WordPress best practices (nonces, sanitization, escaping)
- Requires WooCommerce plugin to be active
- Requires PHP 7.4+
- Compatible with WordPress 5.0+

---
