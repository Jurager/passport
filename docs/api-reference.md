# API Reference

Short reference for core classes and endpoints.

## Broker

`Jurager\Passport\Broker`

- `generateClientToken(): string`
- `saveClientToken(string $token): void`
- `getClientToken(): string|array|null`
- `clearClientToken(): void`
- `sessionName(): string`
- `sessionId(?string $token): string`
- `isAttached(): bool`
- `generateAttachChecksum(string $token): string`
- `login(array $credentials, Request $request): bool|array`
- `profile(Request $request): bool|string|array`
- `logout(Request $request, $method = null): bool`
- `commands(string $command, array $params, Request $request): bool|string`

## Server

`Jurager\Passport\Server`

- `findBrokerById($id): mixed`
- `validateBrokerSessionId(?string $sid): string`
- `generateSessionId(mixed $broker, string $token): string`
- `verifyAttachChecksum(mixed $broker, string $token, string $checksum): bool`
- `getBrokerInfoFromSessionId(?string $sid): array`
- `getBrokerSessionId(Request $request): ?string`
- `getBrokerFromRequest(Request $request): mixed`

## PassportGuard

`Jurager\Passport\PassportGuard`

- `user(): ?Authenticatable`
- `attempt(array $credentials = [], bool $remember = false)`
- `loginFromPayload(array $payload)`
- `loginFromToken(string $token)`
- `validate(array $credentials = []): bool`
- `logout(): void`

## Endpoints

Server prefix: `sso/server`

- `GET|POST /attach`
- `POST /login`
- `GET /profile`
- `POST /logout`
- `POST /commands/{command}`

Broker prefix: `sso/client`

- `GET /attach`
- `POST /logout/{id}`
- `POST /logout/all`
- `POST /logout/others`
