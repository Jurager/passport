# API Reference

Complete API reference for Jurager/Passport classes and methods.

## Broker Class

**Namespace:** `Jurager\Passport\Broker`

The Broker class handles communication between broker applications and the SSO server.

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `$client_id` | string | Broker client ID |
| `$client_secret` | string | Broker secret key |
| `$server_url` | string | SSO server URL |

### Methods

#### generateClientToken()

Generate a unique session token.

```php
public function generateClientToken(): string
```

**Returns:** Random token string

**Example:**
```php
$token = $broker->generateClientToken();
```

#### saveClientToken($token)

Save session token to storage.

```php
public function saveClientToken($token): void
```

**Parameters:**
- `$token` (string) - Token to save

#### getClientToken()

Get the current session token.

```php
public function getClientToken(): string|array|null
```

**Returns:** Token from bearer header or storage, or null

#### clearClientToken()

Clear the session token.

```php
public function clearClientToken(): void
```

#### sessionName()

Get the session key name.

```php
public function sessionName(): string
```

**Returns:** Session key name (e.g., `sso_token_my_app`)

#### sessionId(?string $token)

Generate session ID from token.

```php
public function sessionId(?string $token): string
```

**Parameters:**
- `$token` (string|null) - Client token

**Returns:** Session ID (format: `Passport-{broker}-{token}-{checksum}`)

**Throws:** `NotAttachedException` if token is null

#### isAttached()

Check if broker is attached to server.

```php
public function isAttached(): bool
```

**Returns:** True if attached, false otherwise

#### generateAttachChecksum(string $token)

Generate attachment checksum.

```php
public function generateAttachChecksum(string $token): string
```

**Parameters:**
- `$token` (string) - Attach token

**Returns:** Checksum hash

#### login(array $credentials, Request $request)

Send login request to server.

```php
public function login(array $credentials, Request $request): bool|array
```

**Parameters:**
- `$credentials` (array) - Login credentials
- `$request` (Request) - HTTP request instance

**Returns:** User data array or false

**Example:**
```php
$user = $broker->login([
    'email' => 'user@example.com',
    'password' => 'password',
], $request);
```

#### profile(Request $request)

Fetch user profile from server.

```php
public function profile(Request $request): bool|string|array
```

**Parameters:**
- `$request` (Request) - HTTP request instance

**Returns:** User data array, or false if not authenticated

#### logout(Request $request, $method = null)

Send logout request to server.

```php
public function logout(Request $request, $method = null): bool
```

**Parameters:**
- `$request` (Request) - HTTP request instance
- `$method` (string|null) - Logout method: `null`, `'id'`, `'all'`, `'others'`

**Returns:** True if successful

**Example:**
```php
// Logout current session
$broker->logout($request);

// Logout by ID
$broker->logout($request, 'id');

// Logout all sessions
$broker->logout($request, 'all');

// Logout others
$broker->logout($request, 'others');
```

#### commands(string $command, array $params, Request $request)

Execute custom server command.

```php
public function commands(string $command, array $params, Request $request): bool|string
```

**Parameters:**
- `$command` (string) - Command name
- `$params` (array) - Command parameters
- `$request` (Request) - HTTP request instance

**Returns:** Command response

**Example:**
```php
$result = $broker->commands('hasRole', ['role' => 'admin'], $request);
```

---

## Server Class

**Namespace:** `Jurager\Passport\Server`

The Server class handles broker validation and session management on the SSO server.

### Methods

#### findBrokerById($id)

Find broker by ID.

```php
public function findBrokerById($id): mixed
```

**Parameters:**
- `$id` (string) - Broker ID

**Returns:** Broker model

**Throws:** `InvalidSessionIdException` if not found

#### validateBrokerSessionId(?string $sid)

Validate broker session ID.

```php
public function validateBrokerSessionId(?string $sid): string
```

**Parameters:**
- `$sid` (string|null) - Session ID

**Returns:** Broker ID

**Throws:** `InvalidSessionIdException` if invalid

#### generateSessionId(mixed $broker, string $token)

Generate session ID.

```php
public function generateSessionId(mixed $broker, string $token): string
```

**Parameters:**
- `$broker` (mixed) - Broker model
- `$token` (string) - Session token

**Returns:** Session ID

#### verifyAttachChecksum(mixed $broker, string $token, string $checksum)

Verify attach checksum.

```php
public function verifyAttachChecksum(mixed $broker, string $token, string $checksum): bool
```

**Parameters:**
- `$broker` (mixed) - Broker model
- `$token` (string) - Attach token
- `$checksum` (string) - Checksum to verify

**Returns:** True if valid

#### getBrokerInfoFromSessionId(?string $sid)

Extract broker info from session ID.

```php
public function getBrokerInfoFromSessionId(?string $sid): array
```

**Parameters:**
- `$sid` (string|null) - Session ID

**Returns:** `[broker_id, token, checksum]`

**Throws:** `InvalidSessionIdException` if invalid

#### getBrokerSessionId(Request $request)

Get session ID from request.

```php
public function getBrokerSessionId(Request $request): ?string
```

**Parameters:**
- `$request` (Request) - HTTP request

**Returns:** Session ID or null

#### getBrokerFromRequest(Request $request)

Get broker model from request.

```php
public function getBrokerFromRequest(Request $request): mixed
```

**Parameters:**
- `$request` (Request) - HTTP request

**Returns:** Broker model

---

## PassportGuard Class

**Namespace:** `Jurager\Passport\PassportGuard`

Laravel authentication guard for SSO.

### Methods

#### user()

Get the currently authenticated user.

```php
public function user(): ?Authenticatable
```

**Returns:** User model or null

#### attempt(array $credentials = [], bool $remember = false)

Attempt to authenticate user.

```php
public function attempt(array $credentials = [], bool $remember = false): Authenticatable|bool|null
```

**Parameters:**
- `$credentials` (array) - Login credentials
- `$remember` (bool) - Remember me flag

**Returns:** User model or false

#### loginFromPayload(array $payload)

Login user from server payload.

```php
public function loginFromPayload(array $payload): Authenticatable|bool|null
```

**Parameters:**
- `$payload` (array) - User data from server

**Returns:** User model or false

#### loginFromToken(string $token)

Login user from bearer token.

```php
public function loginFromToken(string $token): Authenticatable|bool|null
```

**Parameters:**
- `$token` (string) - Bearer token

**Returns:** User model or false

#### validate(array $credentials = [])

Validate credentials without logging in.

```php
public function validate(array $credentials = []): bool
```

**Parameters:**
- `$credentials` (array) - Login credentials

**Returns:** True if valid

#### logout()

Logout the user.

```php
public function logout(): void
```

---

## Models

### Broker Model

**Namespace:** `Jurager\Passport\Models\Broker`

**Table:** `brokers` (configurable)

**Attributes:**

| Column | Type | Description |
|--------|------|-------------|
| id | integer | Primary key |
| client_id | string | Broker client ID |
| secret | string | Secret key (hidden) |
| created_at | timestamp | Created timestamp |
| updated_at | timestamp | Updated timestamp |
| deleted_at | timestamp | Soft delete timestamp |

**Traits:** `SoftDeletes`

### History Model

**Namespace:** `Jurager\Passport\Models\History`

**Table:** `history` (configurable)

**Attributes:**

| Column | Type | Description |
|--------|------|-------------|
| id | integer | Primary key |
| authenticatable_id | integer | User ID |
| authenticatable_type | string | User model class |
| session_id | string | Session ID |
| user_agent | string | User agent |
| ip | string | IP address |
| device_type | string | Device type |
| device | string | Device name |
| platform | string | Platform |
| browser | string | Browser |
| city | string | City |
| region | string | Region |
| country | string | Country |
| remember_token | string | Remember token |
| expires_at | timestamp | Expiration |
| created_at | timestamp | Created |
| updated_at | timestamp | Updated |
| deleted_at | timestamp | Deleted |

**Traits:** `SoftDeletes`, `Prunable`

**Relationships:**
- `authenticatable()` - MorphTo user

**Accessors:**
- `location` - Combined city, region, country
- `is_current` - True if current session

**Methods:**
- `revoke()` - Revoke session

### Token Model

**Namespace:** `Jurager\Passport\Models\Token`

**Table:** `access_tokens` (configurable)

**Attributes:**

| Column | Type | Description |
|--------|------|-------------|
| id | integer | Primary key |
| tokenable_id | integer | User ID |
| tokenable_type | string | User model class |
| name | string | Token name |
| token | string | Hashed token |
| last_used_at | timestamp | Last used |
| expires_at | timestamp | Expiration |
| created_at | timestamp | Created |
| updated_at | timestamp | Updated |
| deleted_at | timestamp | Deleted |

**Traits:** `SoftDeletes`, `Prunable`

**Relationships:**
- `tokenable()` - MorphTo user

---

## Traits

### Passport Trait

**Namespace:** `Jurager\Passport\Traits\Passport`

**Methods:**

- `history()` - Get user history
- `current()` - Get current session
- `logoutById($id)` - Logout by ID
- `logoutOthers()` - Logout others
- `logoutAll()` - Logout all

### HasTokens Trait

**Namespace:** `Jurager\Passport\Traits\HasTokens`

**Methods:**

- `tokens()` - Get user tokens
- `createToken($name, $expires)` - Create token
- `removeToken($id)` - Remove token

---

## HTTP Endpoints

### Server Endpoints

Base URL: `/sso/server` (configurable)

| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/attach` | Attach broker |
| POST | `/login` | Login user |
| GET | `/profile` | Get user profile |
| POST | `/logout` | Logout user |
| POST | `/commands/{command}` | Execute command |

### Broker Endpoints

Base URL: `/sso/client` (configurable)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/attach` | Attach to server |
| POST | `/logout/id` | Logout by ID |
| POST | `/logout/all` | Logout all |
| POST | `/logout/others` | Logout others |
