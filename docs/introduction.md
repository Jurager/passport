# Introduction

Jurager/Passport provides Single Sign-On (SSO) for Laravel through a server (auth authority) and brokers (client apps).

## Concepts

- **Server** stores users, validates credentials, and issues sessions.
- **Broker** redirects users to the server and receives the user payload.

## Flow

1. Broker attaches to the server using a token and checksum.
2. User logs in on the server.
3. Broker pulls the profile on each request to keep the session in sync.
4. Logout revokes the server session (and ends broker access).

## When To Use

- Multiple Laravel apps need one login.
- Centralized user store with separate frontends.
- Shared sessions across internal services.

An account-center app (similar to accounts.google.com) should be a broker. It still talks to a separate server that owns the user store and sessions.

## Requirements

- PHP >= 8.1
- Laravel 9+
- Composer
- Optional: Guzzle for IP lookup
- Optional: User-Agent parser (jenssegers/agent or whichbrowser/parser)
