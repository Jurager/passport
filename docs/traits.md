---
title: Traits
weight: 140
---

# Traits

Passport ships traits that add SSO and token helpers to your User model.

## Passport

Adds session helpers:

- `history()`
- `current()`
- `logoutById($id)`
- `logoutOthers()`
- `logoutAll()`

```php
use Jurager\Passport\Traits\Passport;

class User extends Authenticatable
{
    use Passport;
}
```

## HasTokens

Adds API token helpers:

- `tokens()`
- `createToken($name, $expires)`
- `removeToken($id)`

```php
use Jurager\Passport\Traits\HasTokens;

class User extends Authenticatable
{
    use HasTokens;
}
```

## MakesApiCalls

Helper for custom IP providers. Use it only when building a lookup provider.
