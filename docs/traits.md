# Traits

## Passport

Adds session helpers to the User model:

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
