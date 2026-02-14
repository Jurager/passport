# Troubleshooting

## Redirect loops

- Ensure `AttachBroker` is added once and after `StartSession`.
- Verify sessions persist (`SESSION_DRIVER`).
- Increase `PASSPORT_ATTACH_THROTTLE`.

## Invalid checksum

- Ensure broker secret matches the server.
- Check `PASSPORT_SERVER_ID_FIELD` and `PASSPORT_SERVER_SECRET_FIELD`.
- Sync server clocks.

## Session not found / broker not attached

- Check session TTL (`PASSPORT_STORAGE_TTL`).
- Confirm middleware registration.
- Clear sessions and cookies.

## User not created on broker

Define `user_create_strategy` in `config/passport.php`.

## Token auth fails

- Ensure token is not expired.
- Send `Authorization: Bearer <token>`.

## Debug

```env
PASSPORT_DEBUG=true
```

Check `storage/logs/laravel.log`.
