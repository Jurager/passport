# Security

## Built-in

- Checksum validation for broker sessions.
- Allowed redirect hosts to prevent open redirects.
- Attach throttling and redirect loop protection.
- Token hashing (SHA-256).
- Session TTL with pruning.

## Configure

```env
PASSPORT_ALLOWED_REDIRECT_HOSTS=app.com,admin.app.com
PASSPORT_ATTACH_THROTTLE=5
PASSPORT_MAX_REDIRECT_ATTEMPTS=3
PASSPORT_STORAGE_TTL=600
PASSPORT_DEBUG=false
```

## Recommended

- Use HTTPS everywhere.
- Store broker secrets in `.env` only.
- Rotate secrets for production brokers.
- Rate limit login endpoints.
- Keep sessions and tokens short-lived where possible.
