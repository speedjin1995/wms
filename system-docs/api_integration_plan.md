# Plan: Universal API connector + LLM extraction for Delivery Order

## Context
Right now the Delivery Order screen only fills itself from our own Excel template (`action=import` → `DeliveryOrderService::importSheet`). Suppliers send scanned/photo or PDF DOs, and staff type them in by hand. We want an LLM to read those documents and fill the same form.
Before that, we need one reusable component that can call any outside API, starting with REST and SOAP, so later integrations (LLMs, ERPs, government APIs) don't each write their own curl code. Each tenant has its own settings and keys, and can pick its own LLM provider.

Nothing exists yet: the PHP code has no curl/SoapClient use. Installed: PHP 8.2 with curl, openssl, fileinfo. **The SOAP extension is not enabled** (it needs `extension=soap` in `C:\xampp\php\php.ini` and on the server).

## Design (smallest complete version)

### 1. Storage: `api_integrations` table (append to `SQL/deploy.sql`)
`id, company, code (e.g. 'do_extract'), name, method ENUM('rest','soap'), base_url (URL or WSDL), auth_type ENUM('none','bearer','basic','api_key','header'), credentials TEXT (encrypted JSON), headers JSON, options JSON (timeout, provider, model, soap opts), deleted, created_at, modified_at`. Also `secret_hint`, `created_by`, `modified_by`. `company = 0` is the platform default. The index on `(company, code, deleted)` is not unique, because soft-deleted rows can repeat. The save code makes sure there's only one active row per company + code.
- Secrets are encrypted with openssl AES-256-GCM. The key is `WMS_SECRET_KEY`, defined in a new git-ignored `php/config/secret.php`. It is never stored in the DB and never sent to the browser.
- New feature flag in `company_features`: `include_do_ai`, using the existing pattern read by `php/lookup.php:24`.

### 2. Connector: `php/classes/Integrations/ApiConnector.php` (`App\Integrations\ApiConnector`)
This has no DB access, so it is not a Service. One public entry point:
```php
call(array $config, array $request): array
// $request: ['operation'=>..., 'path'=>..., 'http_method'=>'POST', 'query'=>[], 'json'=>[]|'form'=>[]|'body'=>'', 'headers'=>[]]
// returns ['ok'=>bool, 'status'=>int, 'body'=>array|string|object, 'error'=>string]
```
- `switch ($config['method'])` picks `private rest()` or `private soap()`. Adding a new method later means adding one `case` and one private function. No interface or driver hierarchy until a third method actually exists.
- `rest()`: curl with timeout, applies auth (bearer / basic / api_key header name / custom headers), sends a JSON or form body, and decodes JSON responses.
- `soap()`: `new \SoapClient($wsdl, ['exceptions'=>true, 'connection_timeout'=>..., 'login'/'password' for basic auth, 'trace'=>0])` + `__soapCall($operation, [$payload])`. If `class_exists('SoapClient')` is false, it returns `ok=false, error='SOAP extension not enabled'`.
- It never throws to the caller. Errors go to `error_log` (with the URL host only, never the secrets), and the caller gets a generic `error`.

### 3. Config loader: `php/classes/Services/IntegrationService.php` (extends `BaseService`)
- `getConfig(string $code): ?array` loads `company IN (session company, 0)`, own row first, and decrypts `credentials`. `company = 0` is the **platform default** set in wms_admin, and a tenant row overrides it.
- `getForTenant()` / `saveOverride()` / `removeOverride()` back the tenant settings page. When the platform key is in use, the tenant sees nothing about it (no hint, no model).
- `encrypt()/decrypt()` are private helpers here. wms_admin has a matching `encryptSecret()` (`wms_admin/php/modules/apiIntegrations/crypto.php`) with the same format and the same key in its own git-ignored `php/config/secret.php`.
- `secret_hint` (`...abcd`) is stored at save time, so neither page ever decrypts a secret just to display it.

### 3a. Config pages (SleekFlow-style: configured in the UI, secrets write-only)
- **wms_admin → API Connections** (`wms_admin/apiConnections.php`, handlers in `php/modules/apiIntegrations/`): the vendor manages every row, including the "Platform default" (company 0). Load never selects `credentials`. The **Include DO AI Scan** toggle is in the company Features modal.
- **wms → Settings → Integrations** (`modules/integrations/integrations.php`, `php/modules/integrations/api.php` → `IntegrationController` → `IntegrationService`): ADMIN/SADMIN only, and only when `include_do_ai = Y`. The tenant picks provider/model, pastes its own key, and can **Test connection** or **Use platform default**.
- The existing `integration_configs` table (ERP export column mappings) is a separate thing and is not used for secrets. wholesales/industrial `reports.php` sends its `config_json` to the browser.

### 4. LLM extraction for the DO screen
**Provider-neutral extractor** `php/classes/Integrations/LlmExtractor.php`:
- `extract(array $config, string $mime, string $base64, string $prompt): ?array` builds the request for `options.provider` (`anthropic` | `openai` | `gemini`). Each provider is one small array-mapping function for the request (image/PDF block + prompt, JSON-only output) plus one path to read the reply text. It calls `ApiConnector::call` and `json_decode`s the text.
- The prompt asks for exactly: `{"date":"DD/MM/YYYY","doNo":"","rows":[{"supplier":"","product":"","grade":"","do_weight":0}]}`. It includes this tenant's supplier/product/grade names from `getOptions()` so the LLM writes back names we can match.

**Reuse the existing matching:** split `DeliveryOrderService::importSheet` (DeliveryOrderService.php:280) into
- `importSheet($rows, $categoryIds)`, which keeps parsing via `parseTable` and then calls
- new `matchSheet(array $sheet, array $categoryIds)`, which contains the current lines 292–351 unchanged.

The LLM rows are grouped into the same `$sheet` shape that `parseTable` produces (`suppliers[supplierText][product\ngrade] = {product, grade, pairs:[{do}]}`) and passed to `matchSheet`. Unmatched names come back as `warnings`, the same as the Excel import.

**Endpoint:** in `php/modules/deliveryOrder/api.php`, add `case 'extract':`. It is gated on `include_do_ai == 'Y'` and calls `DeliveryOrderController::extract($categoryIds)`:
- Validates the upload with `is_uploaded_file`, uses `finfo` to allow only jpeg/png/webp/pdf, and limits size to 10 MB.
- `IntegrationService->getConfig('do_extract')` returns "AI extraction not configured" if it finds nothing.
- `LlmExtractor->extract(...)` → `DeliveryOrderService::extractDocument(array $llmJson, $categoryIds)` → `matchSheet`.
- The response looks exactly like `import`'s.

**Frontend:** in `modules/deliveryOrder/deliveryOrder.php`, add a "Scan DO (AI)" file input/button next to the existing Excel upload. It posts `FormData` with `action=extract` through jQuery `$.ajax`, disables the button and shows a spinner while waiting, and on success runs **the same handler that `import` already uses** to fill the modal and show warnings via Toastr. Labels come from `$languageArray` keys (`scan_do_ai_code`, etc.), added to `wms_admin/php/insertDefaultTranslations.php`.

## Files
- wms new: `php/classes/Integrations/ApiConnector.php`, `php/classes/Integrations/LlmExtractor.php`, `php/classes/Services/IntegrationService.php`, `php/classes/Controllers/IntegrationController.php`, `php/modules/integrations/api.php`, `modules/integrations/integrations.php`, `php/config/secret.php` (git-ignored)
- wms edit: `SQL/deploy.sql`, `index.php` (Settings menu), `php/classes/Services/DeliveryOrderService.php` (split out `matchSheet`, add `extractPrompt`/`extractDocument`), `php/classes/Controllers/DeliveryOrderController.php` (`extract`), `php/modules/deliveryOrder/api.php` (`extract` case), `modules/deliveryOrder/deliveryOrder.php` (button + handler), `.gitignore`
- wms_admin new: `apiConnections.php`, `php/modules/apiIntegrations/{crypto,loadApiIntegrations,saveApiIntegration,deleteApiIntegration}.php`, `php/config/secret.php` (git-ignored, same key as wms)
- wms_admin edit: `index.php` (menu), `companies.php` + `php/modules/companies/featureFlagging/getCompanyFeatures.php` (`include_do_ai`), `php/insertDefaultTranslations.php`, `.gitignore`

## Deploy
1. Run the `28/10/2026` block of `SQL/deploy.sql`.
2. Create `php/config/secret.php` in **both** wms and wms_admin with the **same** `WMS_SECRET_KEY` (`define('WMS_SECRET_KEY', base64_decode('<32 random bytes, base64>'));`). If the key is lost, every saved secret has to be entered again.
3. Re-run default translations for existing companies so the new `_code` keys exist.
4. In wms_admin, add a Platform default `do_extract` connection (auth API Key, options `{"provider":"anthropic","model":"..."}`, base URL blank) and turn on **Include DO AI Scan** per company.
5. SOAP only: enable `extension=soap` in php.ini.

## Build order
1. Table + `IntegrationService` + config pages (wms_admin platform key, wms tenant override).
2. `ApiConnector` REST, then SOAP (enable `extension=soap` first).
3. Refactor `importSheet` → `matchSheet` (Excel import must behave the same as before).
4. `LlmExtractor` (start with one provider, then add the other two mappings).
5. `extract` endpoint + frontend button.

## Verification
- `php -l` on every new or changed file.
- Connector self-check script `php/tools/testConnector.php` (asserts):
  - REST: GET/POST JSON against a local stub served with `php -S localhost:8099` that echoes headers/body. Checks that auth headers are applied, JSON is decoded, and a timeout or 500 gives `ok=false`.
  - SOAP: calls the public calculator WSDL `http://www.dneonline.com/calculator.asmx?WSDL` (`Add`).
  - Round-trip encrypt/decrypt, and that a wrong key gives `null`.
- Regression: upload an existing DO Excel through `import` and check the form fills the same as before the refactor.
- End to end: configure `do_extract` for a test company, enable `include_do_ai`, then upload a photo and a PDF DO. Check that the date, DO no, and supplier/product/grade lines fill in, and that unknown names show as warnings. Then save, and confirm the rows land in `delivery_orders` / `delivery_order_items` under the right `company`.
- Tenant isolation: company B cannot use company A's config (getConfig scoped by session company).

## Not in v1
- OAuth "Connect" buttons (paste-a-key only)
- Per-company usage/cost log. This is the next step now that the platform key pays for tenants.
- GraphQL / raw XML (one `case` each later)
- Retries, call logging/cost table, async/queued extraction, OAuth2 token refresh
