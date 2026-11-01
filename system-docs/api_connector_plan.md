# API Connector: plan and status

One service for calling outside APIs (REST now, SOAP and GraphQL later). Each integration is set up as database rows in wms_admin, so adding a new external system needs no code deploy.

## Status
| Milestone | Scope | Status |
|---|---|---|
| M1 Core + REST | 4 tables, `SecretStore`, `ConnectorRepository`, `RestAdapter`, auth none / api_key / bearer / basic / header, `ConnectorClient`, `CallLogger`, `Redactor`, `UrlGuard` | **Done** (branch `feature/api-connector`) |
| M2 Mapping + admin console | `MappingEngine`; wms_admin `apiConnectors.php` (connectors, operations + mapping editor, credentials, test); tenant `php/modules/integrations/api.php` | **Built, not yet tested end to end.** Engine, client wiring, tenant `api.php` and the wms_admin console are written. The console's test button is deferred. |
| M3 Resilience + OAuth2 | Retries, circuit breaker, oauth2_cc with token cache, Monitor tab, alert + purge crons | Planned |
| M4 SOAP | `SoapAdapter`, SOAP_FAULT, WS-Security UsernameToken, mTLS | Planned (check the SOAP extension on Hostinger first) |
| M5 Hardening | Key-rotation tool, GraphQL adapter if needed | Planned |
| M6 First consumer (optional) | e.g. rebuild the DO AI scan on the connector, behind a feature flag | Planned |
| JWT login | `jwt` auth type: `JwtSession`, token cache, optional logout | **Done** (run the `01/11/2026` block of `SQL/deploy.sql`) |

## Decisions
- **Hosting is Hostinger with PHP + MySQL only.** No Redis, Vault or Prometheus. Breaker state, the OAuth token cache and metrics all live in MySQL.
- **Only the vendor (wms_admin) defines connectors and operations.** Tenants can only supply their own credentials, for connectors flagged `tenant_configurable`.
- **Tenant access is behind the `include_api_integration` company feature** (off by default, set in wms_admin's company features). It is checked at the tenant entry points (`apiIntegrations.php`, `php/modules/integrations/api.php`, the DO `scan` action), not in `ConnectorClient`, so any new caller must check it too. It is separate from `include_integration`, which is the report export integrations.
- **Calls are synchronous** within the PHP request.
- **Tests run with PHPUnit as a local phar, never through Composer.** `vendor/` is committed, so `composer require --dev` would rewrite the tracked autoload files and the server could fail looking for PHPUnit files it doesn't have. The phar lives in the git-ignored `tools/` folder and is never deployed. See "How to test" in [api_connector_guide.md](api_connector_guide.md).

## Calling a connector
```php
use App\Integrations\Connector\ConnectorClient;
use App\Integrations\Connector\ConnectorRepository;

$client = new ConnectorClient(new ConnectorRepository($db, (int)$_SESSION['customer'], (int)$_SESSION['userID'], (string)$_SESSION['role']));
$result = $client->execute('connector_code', 'operation_code', ['any' => 'payload']);

$result->ok;          // bool
$result->data;        // decoded JSON array, or the raw text
$result->errorCode;   // see Error codes
$result->toArray();   // {status, message, data, meta: {call_id, http_status, latency_ms, attempts}, error_code}
```
`execute()` never throws. For tenant-facing endpoints, pass `['exposed_only' => true]` so only operations flagged `exposed` can run.

Until M2, the payload passes straight through. GET and DELETE send it as the query string. Other methods send it as a JSON body, or as form data when the operation's `options` has `{"body_format":"form"}`.

## Code
`php/classes/Integrations/Connector/` (namespace `App\Integrations\Connector`)

| Class | Job |
|---|---|
| `ConnectorClient` | Entry point. Resolves config, applies auth, sends, normalizes and logs. |
| `ConnectorRepository` | All SQL (extends `BaseService`, so it is scoped to the session company) |
| `SecretStore` | The only place that encrypts or decrypts secrets (AES-256-GCM, versioned keys) |
| `Redactor` | Masks secret values, sensitive headers and query params in logs and returned data |
| `UrlGuard` | SSRF checks, plus pinning the checked IP for curl |
| `CallLogger` | Writes one `api_call_logs` row per call, and an `error_log` line on failure |
| `Auth/*` | One provider per `auth_type`, registered in `AuthRegistry` |
| `JwtSession` | JWT login, token cache and logout |
| `Adapters/*` | One adapter per protocol (`ProtocolAdapter`), registered in `AdapterRegistry` |
| `ErrorCode`, `NormalizedResult`, `PreparedRequest`, `RawResponse` | Value types |

**wms_admin console.** The console is `apiConnectors.php` in the wms_admin repo, with its endpoint at `php/modules/apiConnectors/api.php`. It only saves configuration and never makes calls. wms_admin holds byte-identical copies of `SecretStore.php` and `MappingEngine.php` at the same path. When either file changes in wms, copy it to wms_admin as well.

**Adding a protocol:** write one adapter class, add one `AdapterRegistry` entry, and add one ENUM value to `api_connectors.protocol`.

**Adding an auth type:** write one provider class and add one `AuthRegistry` entry.

## Data model (`SQL/deploy.sql`, block `29/10/2026 (part 2)`)
| Table | Purpose |
|---|---|
| `api_connectors` | Base URL, protocol, timeouts, retry/breaker settings, `allow_http`, `tenant_configurable`, `options` (`allow_private`) |
| `api_connector_operations` | Method, path, `exposed`, `retry_safe`, and `request_spec` / `response_spec` / `error_spec` (JSON mappings, used from M2) |
| `api_connector_credentials` | One row per connector + company (`company = 0` is the platform default). Holds `auth_config` (plain JSON) and `secret` (encrypted JSON), plus OAuth token cache and breaker columns (used from M3). |
| `api_call_logs` | One row per call. Metadata, plus `request_body` and `response_body`. Bodies are redacted and stored in full. |

Soft delete sets `deleted = 1` and `deleted_at = NOW()`. `UNIQUE (…, deleted_at)` allows only one active row per key.

### Where auth settings live
| auth_type | `auth_config` (plain) | `secret` (encrypted) |
|---|---|---|
| none | — | — |
| api_key | `{"in":"header","name":"x-api-key"}` or `{"in":"query","name":"key"}` | `{"key"}` |
| bearer | — | `{"token"}` |
| basic | `{"username"}` | `{"password"}` |
| header | — | `{"X-Header-A": "...", ...}` |
| jwt | `{"login_path","token_path","username"}` required, `{"username_field","password_field","expires_path","ttl_s","logout_path"}` optional | `{"password"}` |
| oauth2_cc (M3) | `{"token_url","client_id","scope","audience","auth_style"}` | `{"client_secret"}` |
| ws_security (M4) | `{"username","digest":true}` | `{"password"}` |
| mtls (M4) | — | `{"cert_pem","key_pem","key_pass"}` |

A connector with no credential row returns `CREDENTIALS_MISSING`. A public API still needs a `none` credential row.

### JWT login (`JwtSession`)
- The server logs in with a JSON POST to `base_url + login_path`, reads the token at `token_path`, and sends it as `Authorization: Bearer`. `username_field` and `password_field` default to `username` and `password`.
- The token is cached encrypted on the credential row (`token_cipher`, `token_expires_at`) and renewed 60 s before expiry. Logins are serialized with `GET_LOCK`.
- Expiry, first one found wins: the value at `expires_path` (date, Unix time or seconds from now), the token's `exp` claim, `ttl_s` (default 3600). A date with no timezone is read as `Asia/Kuala_Lumpur`.
- On a 401, the server logs in again and resends once.
- Logout is optional. `$client->logout('connector_code')` calls `logout_path` if it is set, and always clears the cached token. Nothing calls it automatically, and no page has a button for it.
- Saving a credential clears the cached token, but wms_admin never makes calls, so the vendor session lasts until it expires.
- `company = 0` credentials share one token across tenants.
- Login and logout requests are not written to `api_call_logs` bodies, and a login has no row of its own.
- Customer side: see "Customer logins" below.

### Customer logins
- The vendor sets `tenant_auth_type` (one of `api_key`, `bearer`, `basic`, `header`, `jwt`) and an optional `tenant_auth_config` preset on each `tenant_configurable` connector. The customer never picks the type.
- The customer page `apiIntegrations.php` (menu: API Integrations, replaces AI Setup) shows one row per connector and draws only the boxes for that type. Field names come from the type: `key`, `token`, `username` and `password`, or the header names in the preset.
- `ConnectorRepository::saveTenantLogin` keeps only the typed values. Blank secrets keep the saved one when the saved login has the same type.
- `findCredential` merges the vendor preset under a customer login. A customer login whose type no longer matches the vendor's type is not used, so calls fail until the customer saves again.
- Company 0 rows are never merged with a preset.
- The three AI connectors were set in the `01/11/2026 (part 2)` block: `llm_openai` to bearer, `llm_claude` and `llm_gemini` to api_key.
- Tenant endpoint actions: `setup` (list the boxes, ADMIN/SADMIN), `saveCredential` (takes `connector_id` and `fields`, ignores any browser `auth_type`), `deleteCredential`, `list`, `operations`, `execute`, `preview`.

## Error codes
| Code | When |
|---|---|
| `CONFIG_NOT_FOUND` | Unknown connector or operation |
| `CREDENTIALS_MISSING` | No credential row, the secret can't be decrypted, or a required field is missing |
| `AUTH_UNSUPPORTED` / `PROTOCOL_UNSUPPORTED` | Auth type or protocol not built yet |
| `URL_BLOCKED` | Not https, host changed, user info in the URL, or a private IP |
| `INVALID_REQUEST` | Header name or value is invalid (CR/LF injection) |
| `NOT_EXPOSED` | `exposed_only` call to an operation that isn't exposed |
| `AUTH_FAILED` | Upstream returned 401 or 403 |
| `RATE_LIMITED` | Upstream returned 429 |
| `CLIENT_ERROR` | Upstream returned another 4xx |
| `UPSTREAM_ERROR` | Upstream returned 5xx |
| `TIMEOUT` / `UNREACHABLE` | Transport failure |
| `INTERNAL_ERROR` | Unexpected failure on our side, such as a DB error |

On an upstream error, `data` still carries the provider's (redacted) response body.

## Security rules
- **Secrets** are decrypted only in `SecretStore`. They never appear in results, logs or pages. Pages see only `secret_hint`.
- **Masking:** known secret values, anything auth adds to the request (such as the `Basic …` header), the `Authorization`, `Cookie` and `*key*` / `*token*` / `*secret*` headers, and sensitive query params.
- **SSRF:**
  - https only, unless `allow_http` is set.
  - The request host must equal the `base_url` host.
  - Private and reserved IPs are blocked unless `options.allow_private` is set (for dev mocks only).
  - The resolved IP is pinned for curl, and redirects are not followed.
- **Tenant isolation:** credential lookup and call logs are scoped to `$_SESSION['customer']`. A tenant falls back to company 0, never to another tenant.

## Deploy
1. Run the `29/10/2026 (part 2)` block of `SQL/deploy.sql`. It also drops the old draft's `api_integrations` table if it exists.
2. On each server, copy `php/config/secret.example.php` to `php/config/secret.php` (git-ignored) and set a new key: `php -r "echo base64_encode(random_bytes(32));"`. Back the file up somewhere safe: if it is lost, every saved secret has to be entered again. wms_admin needs the same file with the **same key**, because it encrypts the secrets that wms decrypts.
3. To rotate the key, add version 2 and point `WMS_SECRET_KEY_CURRENT` at it. Keep version 1 until every secret has been re-saved.

## Planned details (M2–M4)
- **Mapping (M2).** A JSON template DSL with `{{path | transform}}` references into input, config and credential options.
  - Operators: `$if`, `$switch`, `$omitIfEmpty`, `$each`.
  - Transforms come from a fixed whitelist. There is no `eval` and no user-supplied regex.
  - Response spec: `{"extract":"a.0.b","then":["json_decode"]}`.
  - Specs are validated on save and previewed in the admin test.
- **Retries (M3).**
  - Only on timeout, unreachable, 429, 502, 503 and 504, and only for `retry_safe` operations.
  - Exponential backoff with jitter, honoring `Retry-After`, within one overall deadline.
- **Circuit breaker (M3).**
  - One breaker per credential row.
  - Consecutive 5xx or transport failures open it; once open, calls fail fast.
  - After the cooldown, a single atomic UPDATE claims a half-open probe.
- **OAuth2 client credentials (M3).**
  - The token is cached encrypted on the credential row and refreshed 60 s before expiry.
  - Refreshes are serialized with `GET_LOCK`.
  - On a 401, the token is refreshed once and the call retried.
- **Observability (M3).**
  - Metrics come from SQL over `api_call_logs`: calls, success %, errors by code, p95 latency.
  - The wms_admin Monitor tab shows them.
  - A cron emails through PHPMailer when a breaker opens or the error rate exceeds 20%.
  - A purge cron handles log retention.
- **SOAP (M4).**
  - Built on `SoapClient`, with timeouts in the stream context.
  - mTLS uses a temporary 0600 PEM file.
  - WS-Security UsernameToken; a `SoapFault` becomes `SOAP_FAULT`.
- **gRPC** can't run natively on Hostinger. Call such services through their REST or grpc-gateway endpoints instead.

## Open questions
- Is the SOAP extension enabled on Hostinger? This blocks M4.
- How long should call logs be kept? The default is 90 days.
- May request or response bodies ever be logged? Decided: yes, redacted and stored in full.
- Which integrations are next? This decides whether to build WS-Security X.509 signing.
