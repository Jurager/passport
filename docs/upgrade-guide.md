# Upgrade Guide

Use this checklist when upgrading the package.

## Update

```bash
composer update jurager/passport
php artisan migrate
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

If you published config, compare with the new file and re-apply changes.

## Laravel 12 Middleware

Middleware registration moved to `bootstrap/app.php`. See [Installation](installation.md).

## Compatibility

| Package | Laravel | PHP |
| --- | --- | --- |
| 1.x | 9.x - 12.x | >= 8.1 |
