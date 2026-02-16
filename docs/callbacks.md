---
title: Callbacks
weight: 100
---

# Callbacks

Callbacks let you customize payloads and user sync without overriding package code.

## Server Callbacks

### user_info

Controls which fields the server returns to brokers. Called on the server when responding to `/login` and `/profile`.

```php
'user_info' => function ($user, $broker, $request) {
    return [
        'id' => $user->id,
        'email' => $user->email,
        'roles' => $user->roles->pluck('name'),
    ];
},
```

> [!NOTE]
> The returned payload must include the broker username field (default: `email`).

### after_authenticating

Runs after credentials are valid. Return `false` to deny login.

Called on the server right after the user is resolved (used in `/login` and `/profile`).

```php
'after_authenticating' => function ($user, $request) {
    return (bool) $user->email_verified_at;
},
```

## Broker Callbacks

Many apps rely on a local User model for authorization, relationships, and UI. These strategies create and update a local user so the app keeps standard Laravel behavior while auth is delegated to the server.

### user_create_strategy

Creates a local user when a payload arrives and the user cannot be found locally by credentials.

```php
'user_create_strategy' => function ($data) {
    return \App\Models\User::create([
        'email' => $data['email'],
        'name' => $data['name'],
        'password' => '',
    ]);
},
```

> [!WARNING]
> If the create strategy returns `null` or `false`, the broker will remain unauthenticated.

You can also reference a class method:

```php
'user_create_strategy' => [\App\Services\PassportUserStrategy::class, 'create'],
```

`PassportUserStrategy` is your own class. Create it in your app and implement the method.

Example implementation:

```php
namespace App\Services;

class PassportUserStrategy
{
    public static function create(array $data)
    {
        return \App\Models\User::create([
            'email' => $data['email'],
            'name' => $data['name'],
            'password' => '',
        ]);
    }

    public static function update($user, array $data)
    {
        $user->update([
            'email' => $data['email'],
            'name' => $data['name'],
        ]);

        return $user;
    }
}
```

### user_update_strategy

Updates a local user on each login/profile sync. Called on the broker after a payload is resolved, even if the user already exists.

```php
'user_update_strategy' => function ($user, $data) {
    $user->update([
        'email' => $data['email'],
        'name' => $data['name'],
    ]);

    return $user;
},
```

## Tips

- Keep payloads minimal; add only fields brokers need.
- Avoid heavy queries in callbacks.
- For brokers, treat payload as the source of truth.
