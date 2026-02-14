# Security

Passport includes several protections by default and allows you to lock down redirects and attach behavior.

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

> [!NOTE]
> If `allowed_redirect_hosts` is empty, all hosts are allowed (backwards compatible, not recommended for production).

## Recommended

- Use HTTPS everywhere.
- Store broker secrets in `.env` only.
- Rotate secrets for production brokers.
- Rate limit login endpoints.
- Keep sessions and tokens short-lived where possible.
