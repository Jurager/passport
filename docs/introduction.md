# Introduction

## What is Jurager/Passport?

Jurager/Passport is a Laravel Single Sign-On (SSO) package that simplifies the implementation of centralized authentication across multiple applications. It enables you to maintain a single user repository on a central server while allowing multiple client applications (brokers) to authenticate users through that central authority.

## How It Works

The package implements a server-broker architecture where:

- **SSO Server** - The central authentication server that stores user accounts and validates credentials
- **SSO Broker/Client** - Applications that delegate authentication to the SSO server

When a user attempts to access a protected resource on a broker application, they are redirected to the SSO server for authentication. Once authenticated, the user can seamlessly access all connected broker applications without needing to log in again.

## Key Features

### Centralized Authentication
- Single user repository on the SSO server
- Unified login across multiple applications
- Consistent user data across all brokers

### Session Management
- Comprehensive session history tracking
- View all active sessions across applications
- Logout from specific sessions or all sessions at once
- Automatic session expiration with configurable TTL

### Security
- Checksum-based session validation
- Protection against open redirect attacks
- Throttling to prevent attach flooding
- Redirect loop detection and prevention
- Secure secret management

### User Geolocation & Device Detection
- IP geolocation tracking (ip-api, ip2location-lite)
- User-Agent parsing (Agent, WhichBrowser)
- Device type, browser, and platform detection
- Cloudflare IP detection support

### API Token Support
- Personal access tokens for API authentication
- Token expiration management
- Bearer token authentication
- Automatic token cleanup

### Flexible Configuration
- Array or database-driven broker management
- Custom user creation and update strategies
- Configurable user information callbacks
- Post-authentication verification hooks
- Custom command support

### Event System
- Authenticated event
- Logout event
- Unauthenticated event
- Full Laravel event listener support

## Architecture Overview

### Server-Broker Communication Flow

1. **Attach Phase**: Broker generates a token and redirects to server with checksum
2. **Authentication Phase**: User logs in on server, session is created
3. **Profile Phase**: Broker requests user profile from server
4. **Logout Phase**: Session is terminated on server and all brokers

### Session Validation

Each request from broker to server includes a session ID with the following format:

```
Passport-{broker_id}-{token}-{checksum}
```

The checksum ensures that:
- The broker is authorized
- The token hasn't been tampered with
- The request is legitimate

## Requirements

- PHP >= 8.1
- Laravel 9.x or higher
- Composer
- (Optional) Guzzle HTTP client for IP geolocation
- (Optional) User-Agent parser (jenssegers/agent or whichbrowser/parser)

## Use Cases

Jurager/Passport is ideal for:

- **Multi-tenant applications** where users need access to multiple related services
- **Microservices architecture** requiring centralized authentication
- **Enterprise systems** with multiple internal applications
- **SaaS platforms** offering multiple products under one account
- **Educational institutions** with separate portals for students, teachers, and administration