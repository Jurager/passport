---
title: Events
weight: 120
---

## Events

Passport fires package events on the server and Laravel auth events on brokers via the custom guard.

## Package Events (Server)

These events are dispatched by the server when handling SSO requests.

> [!NOTE]
> Package events are only fired on the server application, not on brokers.

- `Jurager\Passport\Events\Authenticated`
  - Fired after credentials are validated in `/login`.
  - Fired when an existing session is validated by `ServerAuthenticate` (for `/profile` and `/logout`).

- `Jurager\Passport\Events\Unauthenticated`
  - Fired when `/login` fails (invalid credentials).

- `Jurager\Passport\Events\Logout`
  - Fired after a successful server logout (`/logout` with `id`, `all`, or `others`).

## Laravel Auth Events (Broker)

When you use the Passport guard (via `ClientAuthenticate`), it dispatches standard Laravel auth events:

- `Attempting` when `attempt()` is called.
- `Login` after a successful login via `attempt()`.
- `Authenticated` after the broker logs in from a payload.
- `Logout` when `logout()` completes.

## Listen

```php
use Jurager\Passport\Events\Authenticated;
use Illuminate\Support\Facades\Event;

Event::listen(Authenticated::class, function ($event) {
    // $event->user, $event->request
});
```
