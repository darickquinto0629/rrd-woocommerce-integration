# Changelog

All notable changes to the RRD WooCommerce Integration plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [0.3.1] - 2026-07-19

### Fixed

#### Asset Loading Issue on Production

- **Issue:** CSS and JavaScript files not loading on production WordPress sites, causing buttons to be non-functional
- **Root Cause:** Unreliable asset enqueue check using `$_GET` and `REQUEST_URI` string matching
- **Solution:** Replaced with proper WordPress screen detection via `get_current_screen()`
- **Files Modified:** [class-rrd-admin.php](includes/class-rrd-admin.php) - `enqueue_assets()` method
- **Impact:** Assets now load reliably on all WordPress configurations (legacy and HPOS)

#### API Response Parsing - Multiple Response Formats

- **Issue:** API returning error responses with different JSON structure than expected
- **Root Cause:** Code only looked for `ReturnCode` and `Description` fields, but RRD API also uses `Status` and `Error` array format
- **Solution:** Updated response parser to handle both formats dynamically
- **Files Modified:** [class-rrd-api-client.php](includes/class-rrd-api-client.php) - `submit()` method
- **Details:**
  - Format 1 (Success): `{"ReturnCode": 200, "Description": "..."}`
  - Format 2 (Error): `{"Status": "Fail", "Error": [{"ErrorCode": "5001"}]}`
  - Now extracts error codes and messages from both formats
  - Returns structured error descriptions for troubleshooting

#### Response Handler - Status Code Recognition

- **Issue:** Response handler only recognized numeric `200` as success, failed on string status values
- **Solution:** Updated success detection to recognize both numeric `200` and string `"Success"`
- **Files Modified:** [class-rrd-response-handler.php](includes/class-rrd-response-handler.php) - `handle()` method
- **Details:**
  - Success conditions: `return_code === 200` OR `return_code === "Success"`
  - Failure conditions: Any other value (numeric codes, "Fail" status, etc.)
  - Error descriptions now properly displayed in order notes

#### Error Message Clarity

- **Improvement:** Error messages now show actual error codes from RRD API
- **Example:** `Code: Fail, Description: Error Code: 5001` (instead of generic "API Error")
- **Benefit:** Better troubleshooting and communication with RRD support

### Technical Details

- All fixes maintain 100% backward compatibility
- No database migrations required
- No changes to order meta keys or AJAX actions
- Improved error logging with better descriptions
- All PHP files syntax verified

### Testing

✅ Asset loading verified on production environment  
✅ API response parsing handles both formats  
✅ Error responses properly captured and displayed  
✅ Order notes show detailed error information  
✅ All backward compatibility maintained

---

## [0.3.0] - 2026-07-19

### Refactored

#### Service-Oriented Architecture (SOA) Implementation

Complete architectural refactoring to implement Single Responsibility Principle (SRP) and separation of concerns:

**Step 5: Admin UI Extraction**

- Extracted all admin UI rendering from monolithic `order-submission.php` into dedicated `RRD_Admin` class (189 lines)
- Separated concerns: UI rendering, asset management, script localization
- Methods: `init()`, `enqueue_assets()`, `render_meta_box()`, `render_status_section()`, `get_status_display_data()`, `localize_script()`, `get_status_label()`, `get_default_status()`
- Admin page now purely handles display logic with zero business logic
- Improved maintainability and testability of UI code

**Step 6: AJAX Handler Extraction**

- Extracted all AJAX endpoints from monolithic `order-submission.php` into dedicated `RRD_AJAX` class (90 lines)
- Centralized request validation: `validate_request()` method handles nonce, permissions, order retrieval
- Clean separation: AJAX handlers only validate requests and delegate to services
- Methods: `init()`, `validate_request()`, `preview_payload()`, `submit_order()`
- Zero business logic in AJAX handlers - all orchestration delegated to `rrd_submit_order_to_api()`

#### Architecture Overview

Complete class hierarchy following Service-Oriented Architecture:

```
rrd-woocommerce-integration.php (Bootstrap)
├── includes/helpers.php (Utilities)
├── includes/class-rrd-payload-builder.php (Step 1: Payload Generation)
├── includes/class-rrd-api-client.php (Step 2: HTTP Communication)
├── includes/class-rrd-response-handler.php (Step 3: Response Processing)
├── includes/class-rrd-order-service.php (Step 4: Data Persistence)
├── includes/class-rrd-admin.php (Step 5: UI Rendering) ⭐ NEW
├── includes/class-rrd-ajax.php (Step 6: Request Handling) ⭐ NEW
└── includes/order-submission.php (Core Orchestration)
```

#### Code Quality Improvements

- **Separation of Concerns:** Admin UI, AJAX handling, payload building, API communication, response handling, and data persistence are now completely isolated
- **Single Responsibility:** Each class has one reason to change
- **Improved Testability:** Each component can now be tested independently
- **Reduced Coupling:** Classes depend on interfaces/contracts, not implementations
- **Better Maintainability:** Small, focused classes are easier to understand and modify
- **No Business Logic in UI:** Admin and AJAX classes purely handle presentation and request routing
- **100% Backward Compatible:** All hooks, AJAX actions, order meta keys, and payloads remain unchanged

### Non-Breaking Changes

✅ **Preserved:**

- All WordPress hooks and filters
- All AJAX actions (`wp_ajax_rrd_preview_payload`, `wp_ajax_rrd_submit_order`)
- All order meta keys (`rrd_submission_status`, `rrd_last_submitted_at`, `rrd_return_code`, etc.)
- All payload structures and API behavior
- All public function signatures
- Complete user-facing functionality

✅ **Improved:**

- Code organization and maintainability
- Class separation and modularity
- Testability of individual components
- Developer experience when extending the plugin

### Technical Details

- Refactoring follows WordPress and WooCommerce coding standards
- All new classes follow SOLID principles
- Code organization follows Service-Oriented Architecture (SOA) pattern
- Each file has one responsibility per SRP guidelines
- Zero changes to database schema, options, or data structures
- All existing functionality preserved exactly as before

---

## [0.2.0] - 2026-07-19

### Added

#### Real Payload Builder (Step 5)

- Dynamic product extraction from WooCommerce orders using `$order->get_items()`
- Automatic SKU mapping from product data to `CustomerSKU` payload field
- Dynamic UOM (Unit of Measure) support via product meta field `rrd_uom`
- Multi-line payload support - complete `Line` array with all order items
- Missing SKU visibility - products without SKU included as "MISSING_SKU" for debugging
- Warning logging for products with missing SKU to aid troubleshooting

#### Live API Communication (Step 6)

- Real HTTP POST requests to RRD endpoint using WordPress `wp_remote_post()`
- Basic HTTP Authentication with credentials from settings
- 30-second timeout for API requests
- Complete response parsing to extract API `ReturnCode` and `Description`
- Network error handling with exception handling and error logging
- Full order meta storage of API responses for audit trail
- Order submission history tracking (status, timestamp, return code, full response body)
- Replaces simulated API calls with production-ready implementation

### Changed

- `rrd_generate_payload_preview()` now generates real payloads instead of placeholders
- `rrd_submit_order_to_api()` now makes real HTTP POST requests to RRD endpoint
- Replaced single placeholder product line with dynamic product extraction
- Removed placeholder note field from payload
- API submission now stores complete response body and description in order meta

### Technical Details

- Product UOM extracted from custom meta field `rrd_uom` with fallback to "EA"
- Missing SKU handled as visible marker instead of silent skip
- Warning logs include product name and line number for identification
- HTTP requests sent to configured RRD endpoint (QA or Production)
- Response stored as `rrd_last_response_body` in order meta for debugging
- API description text stored in `rrd_description` for user-facing error messages
- Submission attempt count tracked via `rrd_submit_count` order meta key

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
| 0.2.0   | ✅ Released | 2026-07-19   | Real payload builder                               |
| 0.3.0   | 🔄 Planned  | TBD          | Live API integration                               |
| 1.0.0   | 🔄 Planned  | TBD          | Production release                                 |
