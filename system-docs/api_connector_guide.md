# API Connector guide

How to set up and use the API connector across wms_admin and wms, in plain terms. For the design and code details, see [api_connector_plan.md](api_connector_plan.md).

## The big idea
WMS often needs to talk to outside systems, such as an AI service or accounting software. The connector is one shared phone for all those calls. You add a new system by filling in forms, not by changing the connector code.

| Word | Plain meaning | Example |
|---|---|---|
| **Connector** | Who to call | OpenAI, at `https://api.openai.com` |
| **Operation** | What to ask them | `extract_do`: read this DO photo |
| **Credential** | The key to get in | An API key, or a JWT login |
| **Company 0** | A key shared by all companies | Shown as "Platform default" |

## Who does what

| | **wms_admin** (you, the vendor) | **wms** (your customers) |
|---|---|---|
| **Job** | Set everything up | Use it |
| **Can do** | Add connectors and operations. Pick each connector's customer login type. Turn the feature on per company. Read the logs. | Save their own login in API Integrations, if allowed. Press Scan Paper DO. |
| **Makes outside calls?** | No, it only saves settings | Yes, all calls leave from here |

Both use the same database. wms_admin locks the keys and wms unlocks them, so both need the same secret key file.

## What happens on one call
1. A user presses a button, such as Scan Paper DO.
2. WMS finds the connector and the operation.
3. WMS finds the key: the company's own first, then Company 0. It never uses another company's key.
4. WMS checks that the address is safe, then adds the key.
5. WMS sends the call, cleans up the reply and hides any secrets.
6. WMS writes the call to API Logs.

## First-time setup (once per server)
1. Run the connector parts of `SQL/deploy.sql`: the `29/10/2026 (part 2)` and `01/11/2026` blocks.
2. Copy `php/config/secret.example.php` to `php/config/secret.php`.
3. Make a key and put it in that file:
   ```
   php -r "echo base64_encode(random_bytes(32));"
   ```
4. Put the same file in wms_admin, and keep a backup. If it is lost, every saved key must be typed in again.

## Setup A: AI scan for paper DOs (the easy way)
1. In wms_admin, go to **API Connectors > From Template**.
2. Pick OpenAI, Claude or Gemini, check the model and prompt, then press **Apply**.
3. Go to **Companies**, open the company's features and turn on **API Integration**.
4. The customer reloads WMS and goes to **API Integrations**. An ADMIN pastes the AI key and saves.
5. The customer opens Delivery Order, presses Scan Paper DO and picks the AI.

A Company 0 credential can't replace step 4. The scan dropdown only lists a provider when the company has its own key.

## Setup B: a new outside system

### 1. Add Connector
- **Code:** a short name, such as `autocount`.
- **Base URL:** such as `https://api.vendor.com/v1`.
- **Tenant configurable:** tick only if customers may see this connector and save their own key.
- **Allow HTTP:** leave off unless the vendor has no https.

### 2. Add Operation (one per action)
- **Code:** such as `get_invoice`.
- **Method and Path:** such as `GET` and `/invoices/{id}`.
- **Exposed to tenants:** tick if customer-side actions may run it.
- **Request Spec:** needed if the path has `{id}`. Without a spec, `{id}` is not filled in.

### 3. Add Credential

| Auth type | Use when the vendor gives you | Auth Config | Secret |
|---|---|---|---|
| None | Nothing, it is a public API | blank | blank |
| API Key | A key | `{"in":"header","name":"x-api-key"}` | `{"key":"..."}` |
| Basic | A username and password | `{"username":"..."}` | `{"password":"..."}` |
| Custom Headers | Special headers | blank | `{"X-Header-A":"..."}` |
| Bearer Token (paste a token) | A token to paste, which can be a JWT. Calls fail when it expires. | blank | `{"token":"..."}` |
| JWT Login (server logs in by itself) | A username, password and login URL. The server gets and renews the token. | see [JWT Login](#jwt-login) | `{"password":"..."}` |

Saved keys are never shown again, only the last few letters. Leave Secret blank when editing to keep the old one.

### 4. Write the code that uses it
This is the one part that still needs a developer:
```php
$client = new ConnectorClient(new ConnectorRepository($db, (int)$_SESSION['customer'], (int)$_SESSION['userID'], (string)$_SESSION['role']));
$result = $client->execute('autocount', 'get_invoice', ['id' => 123], ['exposed_only' => true]);
```
Any new customer-facing page must also check that API Integration is on. `execute` does not check this for you.

## JWT Login
Ask the vendor three things: the login URL, the field names, and where the token sits in the reply.

Say the vendor's login works like this:
```
POST https://api.vendor.com/v1/auth/login
send:  {"email":"me@co.com","pwd":"..."}
reply: {"data":{"token":"abc","expires_at":"2026-12-01 10:00:00"}}
```

Auth Config:
```json
{
  "login_path": "auth/login",
  "token_path": "data.token",
  "username": "me@co.com",
  "username_field": "email",
  "password_field": "pwd",
  "expires_path": "data.expires_at"
}
```

Secret, always with the name `password`, whatever the vendor calls it. `password_field` sets the name sent to the vendor:
```json
{"password": "..."}
```

| Setting | Required? | What it does |
|---|---|---|
| `login_path` | Yes | Where to log in, added to the Base URL |
| `token_path` | Yes | Where the token sits in the reply |
| `username` | Yes | The login name |
| `username_field`, `password_field` | No | Field names the vendor wants. Default `username` and `password`. |
| `expires_path` | No | Where the expiry sits in the reply: a date, a Unix time, or seconds from now |
| `ttl_s` | No | How many seconds to keep the token if no expiry is found. Default 3600. |
| `logout_path` | No | Where to log out |

- **Login:** automatic. The token is saved locked and reused.
- **Expiry:** uses `expires_path` first, then the token's own `exp`, then `ttl_s`. A date with no timezone is read as Malaysia time.
- **Renewal:** logs in again 60 seconds before expiry, or once if the vendor replies 401.
- **Logout:** only if you set `logout_path` and code calls `$client->logout('code')`. Vendors with only login and expiry can skip it.
- **Changing the credential:** clears our saved token, but the vendor's session stays open until it expires.

## Request, Response and Error Specs (optional)
Leave these blank unless the vendor wants a different shape.

**Request Spec** shapes what you send. `input` is the data your code passes in:
```json
{"path": {"id": "{{input.id | int}}"}, "body": {"name": "{{input.name | trim}}"}}
```

**Response Spec** picks what you want from the reply:
```json
{"extract": "data.items"}
```

**Error Spec** picks the vendor's error text:
```json
{"message": "error.message"}
```

## How to test
wms_admin has no test button yet. Log in to WMS with API Integration on, open the browser console and run this for an exposed operation:
```js
$.post('php/modules/integrations/api.php', {action:'preview', connector:'autocount', operation:'get_invoice', payload:'{"id":123}'}, console.log)
```
`preview` shows what would be sent, with secrets hidden, and sends nothing. Change it to `execute` for a real call, then look in **wms_admin > API Logs**.

### Try each login type by hand
These public test APIs need no sign-up. Make a connector with the Base URL, an operation with the Method and Path, and a credential with the Auth Config and Secret. Tick **Exposed to tenants** so the console call above can run it.

| Auth type | Base URL | Method and Path | Auth Config | Secret |
|---|---|---|---|---|
| None | `https://httpbin.org` | `GET get` | blank | blank |
| API Key | `https://httpbin.org` | `GET headers` | `{"in":"header","name":"x-api-key"}` | `{"key":"any-value"}` |
| Basic | `https://httpbin.org` | `GET basic-auth/alice/pass123` | `{"username":"alice"}` | `{"password":"pass123"}` |
| Custom Headers | `https://httpbin.org` | `GET headers` | blank | `{"X-Test-Auth":"any-value"}` |
| Bearer Token | `https://httpbin.org` | `GET bearer` | blank | `{"token":"any-value"}` |
| JWT Login | `https://dummyjson.com` | `GET auth/me` | `{"login_path":"auth/login","token_path":"accessToken","username":"emilys"}` | `{"password":"emilyspass"}` |

- **What proves it:** Basic, Bearer and JWT Login really check the login, and a wrong or missing one fails. httpbin only echoes API Key and Custom Headers back, so success there means the value arrived, not that it was right. Echoed secrets show as `***`.
- `emilys` / `emilyspass` is dummyjson's public demo account, so never use it for anything real.
- dummyjson has no logout URL, so it can't test `logout_path`.

### Automatic tests (PHPUnit)
For developers, on a local machine only. Download the runner once. It goes in the git-ignored `tools/` folder:
```
mkdir tools
curl -L -o tools/phpunit.phar https://phar.phpunit.de/phpunit-11.phar
```

Run everything:
```
php tools/phpunit.phar -c php/tests/connector/phpunit.xml
```

Run without internet:
```
php tools/phpunit.phar -c php/tests/connector/phpunit.xml --exclude-group live
```

| File | Tests | Needs |
|---|---|---|
| `MappingEngineTest.php` | Request and response mapping | Nothing |
| `ConnectorRepositoryTest.php` | Saving rows, tenant rules, customer logins | Local database |
| `ConnectorClientTest.php` | Full calls and failures. Starts `mockServer.php` itself on port 8099. | Local database |
| `LiveAuthTest.php` | Every login type on the real APIs above | Local database and internet |

- The tests write and soft delete rows, and leave rows in `api_call_logs`. They refuse to run if `php/db_connect.php` points at another machine. A server's own `localhost` can't be told apart, so never run them on a live server.
- A live test shows as skipped if the public API is down. A skip proves nothing, so run it again later.
- Never add PHPUnit through Composer. See the decision in [api_connector_plan.md](api_connector_plan.md).

## When it fails

| Error | Meaning | Fix |
|---|---|---|
| `CONFIG_NOT_FOUND` | Wrong connector or operation code | Check the codes |
| `CREDENTIALS_MISSING` | No key, or it can't be read | Add the credential. Check `secret.php` matches on both sides. |
| `AUTH_UNSUPPORTED` | Auth type not built yet | Pick another type |
| `URL_BLOCKED` | Not https, or a private address | Fix the Base URL |
| `NOT_EXPOSED` | Customer side can't run this operation | Tick Exposed to tenants |
| `AUTH_FAILED` | Vendor refused the key or login | Check the key. For JWT, check `token_path` too. |
| `RATE_LIMITED` | Too many calls | Wait and try again |
| `CLIENT_ERROR` | Vendor refused the request | Check Path and Request Spec |
| `UPSTREAM_ERROR` | Vendor returned a server error or a redirect | Check the vendor's status and the Base URL |
| `TIMEOUT` / `UNREACHABLE` | No answer | Raise the timeout or check the vendor |
| `MAPPING_ERROR` | Bad spec or missing value | Fix the spec |
| `INTERNAL_ERROR` | A fault on our side | Check the PHP error log |

"API integration is not enabled" means the feature is off for that company. Turn it on, then the customer reloads the page.

## Not built yet
- **Retry safe:** the box saves but does nothing. Normal retries aren't built.
- **Not available yet:** OAuth2, SOAP, mTLS, a test button, a logout button.
- **Customer logins:** the customer can save a login of the type the vendor set on each connector. Customers can't change the type or the preset. Changing a connector's type makes the old customer logins stop working until they are saved again.
