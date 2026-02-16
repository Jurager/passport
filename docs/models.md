---
title: Models
weight: 130
---

## Models

Passport provides three Eloquent models. You can extend them and point config to your custom classes.

## Broker

`Jurager\Passport\Models\Broker` stores registered brokers on the server.

Fields include `client_id`, `secret`, `name`, timestamps, and soft deletes.

```env
PASSPORT_BROKERS_TABLE=brokers
```

Notes:
- `secret` is hidden from JSON output.
- `client_id` identifies the broker.
- Field names can be changed via `PASSPORT_SERVER_ID_FIELD` and `PASSPORT_SERVER_SECRET_FIELD`.

## History

`Jurager\Passport\Models\History` stores login sessions.

Fields include `session_id`, `user_agent`, `ip`, device/browser info, optional geo fields, `expires_at`, timestamps, and soft deletes.

```env
PASSPORT_HISTORY_TABLE=history
```

Accessors:
- `location` combines city, region, country.
- `is_current` is true for the active session.

Methods:
- `revoke()` ends the session and soft-deletes the record.

## Token

`Jurager\Passport\Models\Token` stores API tokens.

Fields include `name`, hashed `token`, `last_used_at`, `expires_at`, timestamps, and soft deletes.

```env
PASSPORT_TOKENS_TABLE=access_tokens
```

Notes:
- Tokens are stored hashed (SHA-256). The plain token is only returned once.
- Tokens are prunable when expired.

## Extending Models

You can extend a model to add fields or behavior and update config to use it.

```php
namespace App\Models;

use Jurager\Passport\Models\Broker as BaseBroker;

class Broker extends BaseBroker
{
    protected $fillable = ['client_id', 'secret', 'name', 'is_active'];
}
```

Set the class in config or env:

```env
PASSPORT_SERVER_MODEL=App\Models\Broker
```
