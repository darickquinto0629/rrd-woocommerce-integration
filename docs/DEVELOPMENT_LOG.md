# RRD WooCommerce Integration - Development Log

## Project Overview

WordPress/WooCommerce plugin that integrates order submission with the RRD `createorder` API using Basic HTTP Authentication and JSON payloads.

**Version:** 0.2.0  
**Status:** Steps 1-6 Complete

---

## Completed Steps

### ✅ Step 1: Basic Plugin Setup

**Date Completed:** 2026-07-19

**What was implemented:**

- Proper WordPress plugin header with standard metadata
- Plugin constants (version, path, URL)
- Activation hook (`rrd_plugin_activate`) - initializes default options
- Deactivation hook (`rrd_plugin_deactivate`) - cleans up scheduled hooks
- Admin menu registration with main dashboard page
- Dashboard page with welcome message and setup instructions

**Files:**

- `rrd-woocommerce-integration.php` (main plugin file)

---

### ✅ Step 2: Settings Page

**Date Completed:** 2026-07-19

**What was implemented:**

- Settings form with nonce protection (`rrd_settings_nonce`)
- Environment selector dropdown (QA/Staging or Production)
- Client ID input field
- API Username input field
- API Password input field (password type for masking)
- Form validation and sanitization on POST
- Success notification message on save
- Reference table displaying both API endpoints:
  - QA/Staging: `https://api85-qa.rrd.com/corporate/v1/createorder`
  - Production: `https://api85.rrd.com/corporate/v1/createorder`

**Security Features:**

- WordPress nonce verification
- Data sanitization on input/output
- Password field masking in UI
- Credentials masked in logs

**Files:**

- `rrd-woocommerce-integration.php` (settings page function)

---

### ✅ Step 3: Helper Functions

**Date Completed:** 2026-07-19

**Functions Implemented:**

1. **`rrd_get_api_endpoint()`**
   - Returns QA or Production endpoint based on stored environment
   - Default: QA endpoint

2. **`rrd_get_basic_auth_header()`**
   - Generates Base64-encoded Basic Auth header
   - Format: `Basic [base64(username:password)]`
   - Returns empty string if credentials missing

3. **`rrd_get_api_headers()`**
   - Returns complete HTTP headers array
   - Includes: Content-Type, Accept, Authorization
   - Automatically adds Basic Auth if available

4. **`rrd_log()`**
   - Logs to WordPress error log with timestamp
   - Logs to WooCommerce order notes if order_id provided
   - Supports custom action labels (send_request, receive_response, error, etc.)

5. **`rrd_mask_sensitive_data()`**
   - Masks sensitive keys in data arrays
   - Sensitive keys: Authorization, password, rrd_api_password
   - Returns masked copy of data

6. **`rrd_validate_configuration()`**
   - Validates all required credentials are configured
   - Checks: environment, client_id, username, password

**Files:**

- `includes/helpers.php` (all helper functions)

---

### ✅ Step 4: Order Submission UI

**Date Completed:** 2026-07-19

**What was implemented:**

- Admin meta box on WooCommerce order pages displaying submission status
- Status badge with color coding (Pending/Success/Failed)
- **Generate Payload Preview** button - builds and displays JSON structure
- **Send to RRD** button - submits order (simulated, Step 6 implements real API)
- Collapsible accordion sections for payload and response viewing
- JavaScript AJAX handlers with proper nonce security
- CSS styling for meta box, buttons, badges, loading spinners
- Order meta storage for submission status, payload, response, return codes
- Order notes logging for audit trail

**Security Features:**

- AJAX nonce verification for both handlers
- Capability check (`edit_shop_orders`)
- Data sanitization in AJAX responses
- Consolidated validation function `rrd_validate_ajax_request()`

**Key Functions:**

- `rrd_enqueue_order_submission_assets()` - Loads CSS/JS only on order pages
- `rrd_render_order_section_admin($order)` - Main meta box renderer
- `rrd_render_order_status_section($order)` - Status display component
- `rrd_localize_order_submission_script()` - Passes secure data to JavaScript
- `rrd_validate_ajax_request($nonce_key)` - Single validation for both AJAX handlers
- `rrd_ajax_preview_payload()` - AJAX handler for payload preview
- `rrd_ajax_submit_order()` - AJAX handler for order submission
- `rrd_generate_payload_preview($order)` - Builds real payload with actual product mapping
- `rrd_submit_order_to_api($order)` - Updates meta and stores results (Step 6 implements real API call)

**Files:**

- `includes/order-submission.php` (core functionality)
- `assets/css/order-submission.css` (styling)
- `assets/js/order-submission.js` (client-side logic)
- `rrd-woocommerce-integration.php` (hook registration)

**Backward Compatibility:**

- No changes to existing helper functions
- No database migrations required
- Settings and credentials from Steps 2-3 remain unchanged
- Previous functionality unaffected

---

## QA Credentials (Staging)

- **URL:** https://api85-qa.rrd.com/corporate/v1/createorder
- **Client ID:** ESTRELLITA01
- **Username:** sfpassquser
- **Password:** c3Q5mzgYf7HpkX2
- **Auth Method:** Basic HTTP Authentication
- **Headers:** Content-Type: application/json, Accept: application/json

---

### ✅ Step 5: Real Payload Builder

**Date Completed:** 2026-07-19

**What was implemented:**

- Real product extraction from WooCommerce orders using `$order->get_items()`
- Dynamic SKU mapping from product data to `CustomerSKU` field
- Dynamic UOM (Unit of Measure) extraction from product meta (`rrd_uom` custom field)
- Multi-line payload support - generates `Line` array with all order items
- Missing SKU handling - products without SKU are included but marked as "MISSING_SKU" for visibility
- Warning logging for products with missing SKU to aid debugging
- Complete payload generation with real order data

**Implementation Details:**

**Function:** `rrd_generate_payload_preview($order)`

- Iterates through all order items using `$order->get_items()`
- For each product item:
  - Retrieves product object and SKU
  - Uses "MISSING_SKU" placeholder if SKU is empty (instead of skipping)
  - Extracts UOM from `rrd_uom` product meta field, defaults to "EA"
  - Logs warning for missing SKU with product name and line number
  - Builds line item with real quantity from order
- Constructs complete BasicOrder payload with:
  - Real line items array (replaces single placeholder)
  - All other fields remain (order number, shipping address, etc.)
  - Removed placeholder note field

**Product Meta Field:**

- Custom field name: `rrd_uom`
- Stores Unit of Measure (EA, BOX, CASE, etc.)
- Default fallback: "EA" (Each)

---

### ✅ Step 6: Live API Communication

**Date Completed:** 2026-07-19

**What was implemented:**

- Real HTTP POST requests to RRD endpoint using WordPress `wp_remote_post()`
- Complete Basic HTTP Authentication with stored credentials
- 30-second timeout for all API requests
- Full response parsing to extract `ReturnCode` and `Description` from JSON response
- Network error handling with exception handling and proper error logging
- Comprehensive order meta storage for complete audit trail
- Submission history tracking with all submission attempts

**Implementation Details:**

**Function:** `rrd_submit_order_to_api($order)`

- Validates configuration before attempting API call
- Generates real BasicOrder payload via `rrd_generate_payload_preview()`
- Constructs HTTP headers with Basic Authentication
- Makes real POST request to RRD endpoint via `wp_remote_post()`
- Handles network errors with try/catch and exception logging
- Parses JSON response to extract `ReturnCode` and `Description`
- Falls back to HTTP status code if API JSON not available
- Updates order meta with submission details:
  - `rrd_submission_status`: Set to 'success' (200) or 'failed' (any other code)
  - `rrd_last_submitted_at`: MySQL timestamp of submission attempt
  - `rrd_return_code`: HTTP or API return code
  - `rrd_last_response_body`: Full JSON response body for debugging
  - `rrd_description`: API description text from response
  - `rrd_submit_count`: Incremented submission attempt count
- Saves order changes to database via `$order->save()`

**Order Meta Fields Stored:**

| Meta Key                   | Type    | Example                 | Purpose                         |
| -------------------------- | ------- | ----------------------- | ------------------------------- |
| `rrd_submission_status`    | string  | 'success' or 'failed'   | Status badge display            |
| `rrd_last_submitted_at`    | string  | '2026-07-19 14:30:00'   | Timestamp of last attempt       |
| `rrd_return_code`          | integer | 200 or 403              | HTTP/API response code          |
| `rrd_last_response_body`   | string  | JSON string             | Complete API response           |
| `rrd_description`          | string  | 'Success' or error text | User-facing description         |
| `rrd_submit_count`         | integer | 1, 2, 3...              | Number of submission attempts   |
| `rrd_last_request_payload` | string  | JSON string             | Last payload sent (from Step 5) |

**Error Handling:**

- **Network Error:** Exception caught and logged, status set to 'failed'
- **HTTP Error (non-200):** Logged, status set to 'failed', return code stored
- **Missing Configuration:** Exception thrown before API call, user notified
- **JSON Parse Error:** Falls back to HTTP status code, logs warning

**Security:**

- Credentials never exposed in response or logs (masked via `rrd_mask_sensitive_data()`)
- All order meta stored securely in WordPress database
- AJAX handler already has nonce and capability checks (from Step 4)
- Request body sent as JSON with proper Content-Type header

**Files Modified:**

- `includes/order-submission.php` - `rrd_submit_order_to_api()` function updated to use real API

**Backward Compatibility:**

- No changes to helper functions
- No changes to settings or configuration
- No database migrations required
- Previous Steps 1-5 functionality unaffected
- AJAX endpoints remain the same

**Current Limitations (Step 6):**

- **QA Credentials:** Currently using provided QA credentials that return 403 Forbidden
- **Production Credentials:** Production endpoint available but requires production credentials
- **No Retry Logic:** Single submission attempt per click (Step 7 will add retry logic)
- **BasicOrder Only:** Only BasicOrder payload supported (CustomArtOrder planned for Step 8)
- **No Duplicate Prevention:** No check for duplicate submissions (Step 7 will add this)

**Error Handling:**

- Missing SKU: Included as "MISSING_SKU" + warning log (allows debugging)
- Missing Product: Skipped silently (non-critical)
- Missing UOM: Defaults to "EA"

**Key Functions:**

- `rrd_generate_payload_preview($order)` - Now implements real product mapping

**Files:**

- `includes/order-submission.php` - Updated `rrd_generate_payload_preview()` function

**Testing Approach:**

1. Create WooCommerce order with multiple products that have SKUs
2. Click "Generate Payload Preview" button
3. Verify JSON shows all products with correct SKUs and quantities
4. Create test order with product missing SKU
5. Verify payload includes product with "MISSING_SKU" value
6. Check order notes for warning log entry

**Backward Compatibility:**

- UI/buttons unchanged - no visual changes
- AJAX handlers unchanged
- Only internal payload generation logic updated
- Response structure unchanged

---

## Planned Next Steps

### Step 6: Live API Communication (Validation & Error Handling)

- Implement actual HTTP POST to RRD `createorder` endpoint
- Replace simulated response with real API response
- Handle timeouts (30s for BasicOrder)
- Parse response for error codes and messages
- Store response data in order meta

### Step 7: Error Handling & Retry Logic

- Implement retry mechanism for failed submissions
- Add manual retry button in admin UI
- Detailed error messages for failed API calls
- Duplicate submission prevention

### Step 8: CustomArtOrder Support

- Implement second payload structure for custom art orders
- Add order type detection logic
- Create separate payload builder for CustomArtOrder
- Support both BasicOrder and CustomArtOrder in single integration

### Step 9: Testing & Validation

- Test with QA credentials against staging API
- Verify payload formats match RRD requirements
- Test both single and multi-item orders
- Test error handling and edge cases
- User acceptance testing with client

---

## File Structure

```
rrd-woocommerce-integration/
├── rrd-woocommerce-integration.php    # Main plugin file
├── includes/
│   └── helpers.php                    # Helper functions
├── admin/                             # Admin pages (TBD)
├── docs/
│   └── DEVELOPMENT_LOG.md             # This file
└── README.md                          # Project documentation
```

---

## Technical Notes

- **WordPress Version:** 5.0+
- **PHP Version:** 7.4+
- **Required Plugin:** WooCommerce
- **Authentication:** HTTP Basic Auth (username:password → Base64)
- **Request Format:** JSON
- **Logging:** WordPress error log + WooCommerce order notes
