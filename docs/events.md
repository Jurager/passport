# Events

Passport fires package events and standard Laravel auth events.

## Package Events

- `Jurager\Passport\Events\Authenticated`
- `Jurager\Passport\Events\Logout`
- `Jurager\Passport\Events\Unauthenticated`

## Listen

```php
use Jurager\Passport\Events\Authenticated;
use Illuminate\Support\Facades\Event;

Event::listen(Authenticated::class, function ($event) {
    // $event->user, $event->request
});
```

Laravel auth events are also fired (`Login`, `Logout`, `Failed`, etc.).
