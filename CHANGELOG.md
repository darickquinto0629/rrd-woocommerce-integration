# Changelog

All notable changes to the RRD WooCommerce Integration plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [0.1.0] - 2026-07-19

### Added

#### Core Plugin Infrastructure

- Basic WordPress plugin header with standard metadata (name, description, version, author, license)
- Plugin constants for versioning and file paths
- Activation hook with default options initialization
- Deactivation hook with cleanup logic
- Admin menu registration and dashboard page

#### Settings Management

- Dedicated settings page accessible from WordPress admin menu
- Environment selector (QA/Staging vs Production)
- Client ID configuration field
- API Username configuration field
- API Password configuration field (password-masked input)
- Settings form with nonce protection and data sanitization
- Success notifications on save
- Reference table displaying both API endpoints

#### Helper Functions & Utilities

- `rrd_get_api_endpoint()` - Dynamic endpoint resolver based on environment
- `rrd_get_basic_auth_header()` - Base64 Basic Auth header generator
- `rrd_get_api_headers()` - Complete HTTP headers builder with authentication
- `rrd_log()` - Dual logging to WordPress error log and WooCommerce order notes
- `rrd_mask_sensitive_data()` - Secure data masking for logs and output
- `rrd_validate_configuration()` - Credential validation checker

#### Order Submission UI

- Admin meta box on WooCommerce order pages with submission status display
- Status badge with color-coded states (Pending, Success, Failed)
- **Generate Payload Preview** button with AJAX payload preview display
- **Send to RRD** button with order submission capability (currently simulated)
- Collapsible accordion sections for viewing payload and response JSON
- Loading spinners and visual feedback for AJAX operations
- Order meta storage for tracking submission status, payloads, and responses
- Order notes integration for audit trail logging
- JavaScript client-side logic with AJAX communication
- CSS styling for professional UI presentation

#### Security

- AJAX nonce verification for all admin-facing endpoints
- WordPress capability checks (`edit_shop_orders`)
- Data sanitization and validation on input and output
- Consolidated `rrd_validate_ajax_request()` function for centralized security checks
- Secure data passing via `wp_localize_script()`

#### Documentation

- Comprehensive README with project overview and setup instructions
- Detailed DEVELOPMENT_LOG.md with step-by-step implementation notes
- This CHANGELOG documenting release history

### Technical Details

**Supported Environment:**

- WordPress 5.0+
- PHP 7.4+
- WooCommerce (any recent version)

**QA/Staging Configuration:**

- Endpoint: `https://api85-qa.rrd.com/corporate/v1/createorder`
- Client ID: ESTRELLITA01
- Username: sfpassquser
- Password: c3Q5mzgYf7HpkX2

**Files Structure:**

```
rrd-woocommerce-integration/
├── rrd-woocommerce-integration.php      # Main plugin file
├── includes/
│   ├── helpers.php                      # Core helper functions
│   └── order-submission.php             # Order submission UI & AJAX
├── assets/
│   ├── css/
│   │   └── order-submission.css         # Meta box styling
│   └── js/
│       └── order-submission.js          # Client-side logic
├── docs/
│   └── DEVELOPMENT_LOG.md               # Development progress tracking
├── README.md                            # Project documentation
├── CHANGELOG.md                         # This file
└── LICENSE                              # Project license
```

### Known Limitations

- **Step 5 (Real Payload Builder):** Currently generates placeholder payloads. Real product extraction and mapping not yet implemented.
- **Step 6 (Live API):** Order submission is currently simulated. Real HTTP POST to RRD API not yet implemented.
- **Step 7 (Error Handling):** Retry logic and duplicate prevention not yet implemented.
- **Step 8 (CustomArtOrder):** Only BasicOrder structure is prepared. CustomArtOrder support planned for future release.

### Next Steps (Planned)

- **v0.2.0:** Implement real payload builder with WooCommerce product mapping
- **v0.3.0:** Implement live API communication to RRD endpoint
- **v0.4.0:** Add error handling, retry logic, and duplicate prevention
- **v1.0.0:** Production-ready with CustomArtOrder support and comprehensive testing

---

## Version Reference

| Version | Status      | Release Date | Focus Area                                         |
| ------- | ----------- | ------------ | -------------------------------------------------- |
| 0.1.0   | ✅ Released | 2026-07-19   | Core infrastructure, settings, order submission UI |
| 0.2.0   | 🔄 Planned  | TBD          | Real payload builder                               |
| 0.3.0   | 🔄 Planned  | TBD          | Live API integration                               |
| 1.0.0   | 🔄 Planned  | TBD          | Production release                                 |
