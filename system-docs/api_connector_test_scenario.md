# API Connector: manual test scenario

An end-to-end check of the M2 work: the wms_admin console saves the configuration, and the wms tenant endpoint runs the calls.

It uses the local mock server, `php/tests/connector/mockServer.php`:
- `/echo` sends back the method, headers, query and body it received.
- `/status/404` returns an error that echoes the API key back, which tests masking.

## 1. Setup

1. Run the `29/10/2026 (part 2)` block of `SQL/deploy.sql` against the local database.
2. Generate a key:
   ```
   /Applications/XAMPP/xamppfiles/bin/php -r "echo base64_encode(random_bytes(32));"
   ```
3. Copy `php/config/secret.example.php` to `php/config/secret.php` in **both** wms and wms_admin. Paste the same key into both.
4. Start the mock server from the wms folder and leave it running:
   ```
   /Applications/XAMPP/xamppfiles/bin/php -S 127.0.0.1:8099 php/tests/connector/mockServer.php
   ```

## 2. Configure in wms_admin (API Connectors menu)

### Connector

| Field | Value |
|---|---|
| Code | `mock` |
| Name | `Local mock` |
| Base URL | `http://127.0.0.1:8099` |
| Tenant configurable | ticked |
| Allow HTTP | ticked |
| Options | `{"allow_private": true}` (needed because 127.0.0.1 is a private IP) |

### Operation 1: `echo_order`

POST, path `/echo/{id}`, Exposed ticked.

Request spec:
```json
{
  "path": {"id": "{{input.order_id | int}}"},
  "query": {"ref": "{{input.ref | trim | upper}}"},
  "headers": {"X-Source": "wms"},
  "body": {
    "customer": "{{input.customer | trim}}",
    "note": {"$omitIfEmpty": "{{input.note}}"},
    "lines": {"$each": "input.lines", "as": "l", "template": {"sku": "{{l.sku}}", "qty": "{{l.qty | int}}"}}
  }
}
```

Response spec:
```json
{"extract": "body", "then": ["json_decode"]}
```

### Operation 2: `fail_404`

GET, path `/status/404`, Exposed ticked.

Error spec:
```json
{"message": "error.detail"}
```

### Operation 3: `hidden`

GET, path `/echo`, Exposed **not** ticked.

### Credential

| Field | Value |
|---|---|
| Company | Platform default |
| Auth type | API Key |
| Auth config | `{"in": "header", "name": "x-api-key"}` |
| Secret | `{"key": "test-key-123456789"}` |

The table should then show the hint `...6789`.

## 3. Console validation checks

Each of these saves should fail with a clear message, except the last.

| Try this | Expected |
|---|---|
| Connector with base URL `http://...` and Allow HTTP unticked | "Base URL must be https unless Allow HTTP is ticked" |
| A second connector with code `mock` | "Connector code already exists" |
| Request spec `{"body": "{{response.x}}"}` | "Unknown root in response.x" |
| Request spec `{"body": "{{input.a \| eval}}"}` | "Unknown transform eval" |
| Request spec `{"body": {"$eval": "input.a"}}` | "Unknown operator $eval" |
| Request spec `not json` | "request_spec must be a JSON object" |
| Credential with auth type Basic and auth config `{}` | "Auth config needs username" |
| Edit the credential, change nothing, leave Secret blank, save | Saves, and the hint stays `...6789` |

## 4. Call it from wms as a tenant

Log into wms as a normal tenant user. Open the browser devtools console on the wms page and run:

```js
var api = 'php/modules/integrations/api.php';
var payload = JSON.stringify({order_id: '42', ref: ' abc ', customer: ' Ali ', note: '', lines: [{sku: 'A1', qty: '3'}, {sku: 'B2', qty: '1'}]});

$.post(api, {action: 'list'}, console.log);
$.post(api, {action: 'operations', connector: 'mock'}, console.log);
$.post(api, {action: 'preview', connector: 'mock', operation: 'echo_order', payload: payload}, console.log);
$.post(api, {action: 'execute', connector: 'mock', operation: 'echo_order', payload: payload}, console.log);
$.post(api, {action: 'execute', connector: 'mock', operation: 'fail_404'}, console.log);
$.post(api, {action: 'execute', connector: 'mock', operation: 'hidden'}, console.log);
$.post(api, {action: 'execute', connector: 'mock', operation: 'nope'}, console.log);
```

| Call | Expected |
|---|---|
| `list` | `mock` appears, with the credential hint |
| `operations` | `echo_order` and `fail_404` only. `hidden` is not listed |
| `preview` | URL `http://127.0.0.1:8099/echo/42?ref=ABC`, an `X-Source: wms` header, the `x-api-key` value masked, and a body with `customer: "Ali"`, no `note`, and `lines` qty as numbers. Nothing reaches the mock server |
| `execute echo_order` | `status: success`. `data` is the mapped body as the mock received it |
| `fail_404` | `status: failed`, `error_code: CLIENT_ERROR`, message is `status 404 …` with the key masked, not `test-key-123456789` |
| `hidden` | `error_code: NOT_EXPOSED` |
| `nope` | `error_code: CONFIG_NOT_FOUND` |

## 5. Check the call log

```sql
SELECT call_id, company, ok, error_code, http_status, latency_ms
FROM api_call_logs ORDER BY id DESC LIMIT 10;
```

You should see rows for the `execute` calls under your tenant's company, and none for `preview`. No key should appear anywhere in this table.

## 6. Delete cascade

In wms_admin, delete the `mock` connector.
- Its operations and credentials should all end up with `deleted = 1`.
- The tenant `list` call should no longer show it.

## Not covered

A tenant's own credential overriding the platform default. Both keys get masked in every output, so you can't tell from the results which one was sent. Checking that properly would need a tenant credential with a different auth type.
