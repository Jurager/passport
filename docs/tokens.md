# Tokens

Personal access tokens allow API authentication without a password.

Tokens are stored locally on the broker and are not shared across brokers.

## Setup

```php
use Jurager\Passport\Traits\HasTokens;

class User extends Authenticatable
{
    use HasTokens;
}
```

## Create Token

```php
$token = $user->createToken('api-token', 60); // minutes
```

> [!NOTE]
> The plain token is shown only once. Store it immediately.

## Use Token

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" https://your-app.com/api/user
```

## Manage Tokens

```php
$tokens = $user->tokens()->latest()->get();
$user->removeToken($tokenId);
```

## Cleanup

```bash
php artisan model:prune --model="Jurager\Passport\Models\Token"
```

> [!NOTE]
> Passport registers a scheduled prune command, but you still need Laravel's scheduler running.
