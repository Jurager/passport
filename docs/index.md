# Jurager/Passport Documentation

Welcome to the Jurager/Passport documentation! This Laravel package provides a comprehensive Single Sign-On (SSO) solution for centralizing authentication across multiple applications.

## Quick Links

- [GitHub Repository](https://github.com/jurager/passport)
- [Packagist](https://packagist.org/packages/jurager/passport)
- [Report Issues](https://github.com/jurager/passport/issues)

## Getting Started

New to Jurager/Passport? Start here:

1. **[Introduction](introduction.md)** - Learn what Jurager/Passport is and how it works
2. **[Installation](installation.md)** - Install the package and set up middleware
3. **[Server Setup](server-setup.md)** - Configure your SSO server
4. **[Broker Setup](broker-setup.md)** - Configure your SSO clients
5. **[Authentication](authentication.md)** - Understand the authentication flow

## Core Concepts

### Architecture

- **[Introduction](introduction.md)** - SSO concepts and architecture overview
- **[Authentication](authentication.md)** - Complete authentication flow explained
- **[Middleware](middleware.md)** - Understanding middleware components

### Setup & Configuration

- **[Installation](installation.md)** - Package installation and setup
- **[Server Setup](server-setup.md)** - Configure the SSO server
- **[Broker Setup](broker-setup.md)** - Configure SSO clients
- **[Configuration](configuration.md)** - Complete configuration reference

### Features

- **[Sessions](sessions.md)** - Session management and tracking
- **[Tokens](tokens.md)** - API token authentication
- **[History](history.md)** - Login history and geolocation
- **[Commands](commands.md)** - Custom server commands
- **[Events](events.md)** - Authentication events

### Components

- **[Models](models.md)** - Broker, History, and Token models
- **[Traits](traits.md)** - Passport, HasTokens, and MakesApiCalls traits
- **[Middleware](middleware.md)** - AttachBroker, ClientAuthenticate, ServerAuthenticate, ValidateBroker

### Security & Best Practices

- **[Security](security.md)** - Security features and best practices
- **[Troubleshooting](troubleshooting.md)** - Common issues and solutions

### Reference

- **[API Reference](api-reference.md)** - Complete API documentation
- **[Upgrade Guide](upgrade-guide.md)** - Version upgrade instructions

## Documentation by Topic

### For Server Administrators

Setting up and maintaining the SSO server:

1. [Server Setup](server-setup.md)
2. [Broker Management](server-setup.md#broker-management)
3. [User Model Configuration](server-setup.md#user-model-configuration)
4. [Custom Commands](commands.md)
5. [Security Configuration](security.md)

### For Client Developers

Integrating SSO into broker applications:

1. [Broker Setup](broker-setup.md)
2. [Authentication Flow](authentication.md)
3. [Using Middleware](middleware.md)
4. [Session Management](sessions.md)
5. [API Tokens](tokens.md)

### For Full-Stack Developers

Complete SSO implementation:

1. [Introduction](introduction.md)
2. [Installation](installation.md)
3. [Server Setup](server-setup.md)
4. [Broker Setup](broker-setup.md)
5. [Authentication](authentication.md)
6. [Sessions](sessions.md)
7. [Events](events.md)
8. [Security](security.md)

## Feature Index

### Authentication

- [Login Flow](authentication.md#authentication-flow)
- [Logout Methods](authentication.md#logout-methods)
- [Remember Me](authentication.md#remember-me)
- [Bearer Tokens](tokens.md#bearer-token-authentication)
- [Custom Guards](authentication.md#passportguard-methods)

### Session Management

- [View Sessions](sessions.md#viewing-active-sessions)
- [Terminate Sessions](sessions.md#terminating-sessions)
- [Session History](history.md)
- [Session TTL](sessions.md#session-ttl)
- [Auto Cleanup](sessions.md#automatic-session-cleanup)

### User Tracking

- [Login History](history.md#history-record-structure)
- [Device Detection](history.md#user-agent-parsing)
- [IP Geolocation](history.md#ip-geolocation)
- [Browser Fingerprinting](history.md#user-agent-parsing)

### API Integration

- [Creating Tokens](tokens.md#creating-tokens)
- [Using Tokens](tokens.md#using-tokens)
- [Token Management](tokens.md#managing-tokens)
- [Token Security](tokens.md#token-security)

### Customization

- [Custom Commands](commands.md)
- [User Info Callback](configuration.md#user_info)
- [Post-Auth Verification](configuration.md#after_authenticating)
- [User Create/Update Strategies](configuration.md#user_create_strategy)
- [Custom IP Providers](history.md#custom-provider)

### Security

- [Checksum Validation](security.md#checksum-validation)
- [Open Redirect Protection](security.md#open-redirect-protection)
- [Attach Throttling](security.md#attach-throttling)
- [Redirect Loop Protection](security.md#redirect-loop-protection)
- [Secret Management](security.md#secret-management)
- [Token Hashing](security.md#token-hashing)

## Common Tasks

### Setup Tasks

- [Install the package](installation.md#composer-installation)
- [Run migrations](installation.md#running-migrations)
- [Register middleware](installation.md#middleware-registration)
- [Create a broker](server-setup.md#create-a-broker)
- [Configure environment](broker-setup.md#environment-configuration)

### Development Tasks

- [Implement login](authentication.md#login-phase)
- [Handle logout](authentication.md#logout-methods)
- [Display sessions](sessions.md#complete-session-management-example)
- [Create API tokens](tokens.md#creating-tokens)
- [Define custom commands](commands.md#defining-commands)
- [Listen to events](events.md#creating-event-listeners)

### Maintenance Tasks

- [Clear sessions](sessions.md#automatic-session-cleanup)
- [Prune expired tokens](tokens.md#automatic-token-cleanup)
- [Monitor failed logins](events.md#track-failed-login-attempts)
- [Rotate secrets](security.md#secret-management)
- [Update configuration](configuration.md)

### Troubleshooting Tasks

- [Fix redirect loops](troubleshooting.md#redirect-loops)
- [Debug session issues](troubleshooting.md#session-not-found)
- [Resolve checksum errors](troubleshooting.md#invalid-checksum)
- [Fix CORS errors](troubleshooting.md#cors-errors)
- [Enable debug mode](troubleshooting.md#debug-mode)

## Quick Reference

### Environment Variables

```env
# Server
PASSPORT_SERVER_DRIVER=model
PASSPORT_SERVER_MODEL=Jurager\Passport\Models\Broker
PASSPORT_SERVER_ID_FIELD=client_id
PASSPORT_SERVER_SECRET_FIELD=secret

# Broker
PASSPORT_BROKER_CLIENT_ID=
PASSPORT_BROKER_CLIENT_SECRET=
PASSPORT_BROKER_SERVER_URL=
PASSPORT_BROKER_CLIENT_USERNAME=email
PASSPORT_BROKER_RETURN_URL=true

# General
PASSPORT_STORAGE_TTL=600
PASSPORT_DEBUG=false
```

See [Configuration](configuration.md) for all options.

### Artisan Commands

```bash
# Installation
composer require jurager/passport
php artisan migrate
php artisan vendor:publish --provider="Jurager\Passport\PassportServiceProvider"

# Maintenance
php artisan model:prune  # Cleanup expired sessions/tokens
php artisan session:clear

# Troubleshooting
php artisan config:clear
php artisan cache:clear
php artisan route:list | grep sso
```

### Common Code Snippets

**Check authentication:**
```php
use Illuminate\Support\Facades\Auth;

if (Auth::guard()->check()) {
    $user = Auth::user();
}
```

**Login:**
```php
if (Auth::guard()->attempt($credentials)) {
    return redirect('/dashboard');
}
```

**Logout:**
```php
Auth::guard()->logout();
```

**Create token:**
```php
$token = $user->createToken('api-token', 60);
```

**View sessions:**
```php
$sessions = $user->history()->whereNull('deleted_at')->get();
```

**Logout from other devices:**
```php
$user->logoutOthers();
```

## Support

### Getting Help

- **Documentation:** You're reading it!
- **GitHub Issues:** [Report bugs or request features](https://github.com/jurager/passport/issues)
- **Troubleshooting:** [Common issues and solutions](troubleshooting.md)

### Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

### License

Jurager/Passport is open-sourced software licensed under the MIT license.

---

## Full Documentation Index

### Getting Started
- [Introduction](introduction.md)
- [Installation](installation.md)
- [Server Setup](server-setup.md)
- [Broker Setup](broker-setup.md)

### Core Features
- [Authentication](authentication.md)
- [Sessions](sessions.md)
- [Tokens](tokens.md)
- [History](history.md)
- [Commands](commands.md)
- [Events](events.md)

### Components
- [Middleware](middleware.md)
- [Models](models.md)
- [Traits](traits.md)

### Advanced
- [Configuration](configuration.md)
- [Security](security.md)
- [API Reference](api-reference.md)

### Maintenance
- [Troubleshooting](troubleshooting.md)
- [Upgrade Guide](upgrade-guide.md)

---

**Ready to get started?** Begin with the [Introduction](introduction.md) or jump straight to [Installation](installation.md).
