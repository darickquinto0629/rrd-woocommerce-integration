# RRD WooCommerce Integration Plugin

## Overview

This WordPress/WooCommerce plugin integrates order submission with the RRD `createorder` API using Basic HTTP Authentication and JSON payloads. Built with service-oriented architecture (SOA) following Single Responsibility Principle (SRP) for maintainability and scalability.

**Version:** 0.3.0  
**Author:** Darick L. Quinto  
**Status:** Production-Ready - All Core Features & Architecture Complete

---

## Current Features (All Steps Complete)

### ✅ Core Features (v0.2.0)

#### Plugin Setup

- Plugin header with proper WordPress standards
- Activation/deactivation hooks with default options
- Admin menu with main dashboard
- Initialization of default options

#### Settings Management

- Secure credential storage in WordPress options
- Environment selector (QA/Staging vs Production)
- Client ID, API Username, and Password configuration
- Form validation and nonce protection
- Success notifications on save
- Both API endpoints displayed for reference

#### Helper Functions & Utilities

- `rrd_get_api_endpoint()` — Dynamic endpoint resolver
- `rrd_get_basic_auth_header()` — Basic Auth header generator
- `rrd_get_api_headers()` — Complete HTTP headers with auth
- `rrd_log()` — WordPress error log + order notes logging
- `rrd_mask_sensitive_data()` — Secure credential masking
- `rrd_validate_configuration()` — Credential validation

#### Real Payload Builder

- Dynamic product extraction from WooCommerce orders
- Automatic SKU mapping to `CustomerSKU` field
- Dynamic UOM (Unit of Measure) support via product meta
- Multi-line payload support for all order items
- Missing SKU visibility with warning logging
- Real BasicOrder payload generation

#### Live API Communication

- Real HTTP POST requests to RRD endpoint
- Basic HTTP Authentication with stored credentials
- 30-second timeout for requests
- Complete response parsing (return code, description)
- Network error handling with exceptions
- Full order meta storage for audit trail
- Submission history tracking with attempt count

#### Admin Order Integration

- Order submission status display with color-coded badges
- **Generate Payload Preview** button with JSON display
- **Send to RRD** button for manual submission
- Collapsible sections for payload and response viewing
- Loading spinners and visual feedback
- Order notes logging for audit trail
- Secure AJAX handlers with nonce verification

### ✅ Architecture & Code Quality (v0.3.0 - NEW)

#### Service-Oriented Architecture

- **6 Dedicated Service Classes** following Single Responsibility Principle:
  1. `RRD_Payload_Builder` — Payload generation
  2. `RRD_API_Client` — HTTP communication
  3. `RRD_Response_Handler` — Response processing
  4. `RRD_Order_Service` — Data persistence
  5. `RRD_Admin` — Admin UI rendering (NEW)
  6. `RRD_AJAX` — AJAX request handling (NEW)

#### Code Quality Improvements

- Clean separation of concerns
- No business logic in UI/AJAX handlers
- Improved testability and modularity
- Reduced coupling between components
- Follows WordPress and WooCommerce coding standards
- 100% backward compatible (non-breaking refactor)

#### File Structure

```
rrd-woocommerce-integration/
├── rrd-woocommerce-integration.php    (Bootstrap, initialization)
├── includes/
│   ├── helpers.php                    (Utilities & core functions)
│   ├── class-rrd-payload-builder.php  (Payload generation)
│   ├── class-rrd-api-client.php       (API communication)
│   ├── class-rrd-response-handler.php (Response processing)
│   ├── class-rrd-order-service.php    (Data persistence)
│   ├── class-rrd-admin.php            (Admin UI)
│   ├── class-rrd-ajax.php             (AJAX handlers)
│   └── order-submission.php           (Core orchestration)
├── assets/
│   ├── css/order-submission.css       (UI styling)
│   └── js/order-submission.js         (Client-side logic)
├── docs/
│   └── DEVELOPMENT_LOG.md             (Detailed development notes)
├── README.md                          (This file)
├── CHANGELOG.md                       (Release history)
└── LICENSE                            (Project license)
```

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
