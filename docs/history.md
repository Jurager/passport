# Session History & Geolocation

Jurager/Passport automatically tracks detailed session history including device information, User-Agent details, and IP geolocation.

## Overview

Every time a user authenticates, the package creates a History record containing:

- **Device Information** - Device type, name, platform, browser
- **IP Address** - Client IP address
- **Geolocation** - City, region, country (optional)
- **Session Metadata** - Session ID, timestamps, expiration

## User-Agent Parsing

The package can parse User-Agent strings to extract device and browser information.

### Supported Parsers

#### Agent Parser (Recommended)

**Package:** [jenssegers/agent](https://github.com/jenssegers/agent)

**Install:**

```bash
composer require jenssegers/agent
```

**Configure:**

```env
PASSPORT_SERVER_PARSER=agent
```

**What it provides:**
- Device type (desktop, mobile, tablet)
- Device name (iPhone, Samsung Galaxy, etc.)
- Platform (iOS, Android, Windows, macOS, Linux)
- Browser (Chrome, Firefox, Safari, Edge, etc.)

#### WhichBrowser Parser

**Package:** [WhichBrowser/Parser-PHP](https://github.com/WhichBrowser/Parser-PHP)

**Install:**

```bash
composer require whichbrowser/parser
```

**Configure:**

```env
PASSPORT_SERVER_PARSER=whichbrowser
```

Provides similar information with potentially more detailed device recognition.

### Configuration

```php
// config/passport.php

'server' => [
    'parser' => 'agent', // or 'whichbrowser'
],
```

## IP Geolocation

The package can look up IP addresses to determine the user's geographic location.

### Prerequisites

IP geolocation requires Guzzle HTTP client:

```bash
composer require guzzlehttp/guzzle
```

### Supported Providers

#### IP-API (Default)

**Website:** [ip-api.com](https://ip-api.com/)

Free service with generous limits.

**Configure:**

```php
// config/passport.php

'server' => [
    'lookup' => [
        'provider' => 'ip-api',
        'timeout' => 1.0,
        'environments' => ['production', 'local'],
    ],
],
```

**Features:**
- Free for non-commercial use
- 45 requests per minute
- No API key required
- Returns: city, region, country, timezone, ISP, etc.

#### IP2Location Lite

**Website:** [lite.ip2location.com](https://lite.ip2location.com/database/ip-country-region-city)

Database-based lookup for better performance and privacy.

**Install:**

1. Download the IP2Location database (DB3 or higher)
2. Import to your MySQL database
3. Configure table names

**Configure:**

```php
// config/passport.php

'server' => [
    'lookup' => [
        'provider' => 'ip2location-lite',

        'ip2location' => [
            'ipv4_table' => 'ip2location_db3',
            'ipv6_table' => 'ip2location_db3_ipv6',
        ],
    ],
],
```

**Features:**
- No external API calls
- Faster lookups
- Better privacy (data stays local)
- Requires database setup

#### Custom Provider

Create your own IP geolocation provider.

**Create provider class:**

```php
namespace App\Services;

use Jurager\Passport\Traits\MakesApiCalls;

class CustomIpProvider
{
    use MakesApiCalls;

    protected string $ip;

    public function __construct(string $ip)
    {
        $this->ip = $ip;
        parent::__construct();
    }

    protected function getRequest()
    {
        return new \GuzzleHttp\Psr7\Request(
            'GET',
            "https://your-api.com/lookup?ip={$this->ip}"
        );
    }

    public function getCity(): ?string
    {
        return $this->result?->get('city');
    }

    public function getRegion(): ?string
    {
        return $this->result?->get('region');
    }

    public function getCountry(): ?string
    {
        return $this->result?->get('country');
    }
}
```

**Register provider:**

```php
// config/passport.php

'server' => [
    'lookup' => [
        'provider' => 'custom',

        'custom_providers' => [
            'custom' => \App\Services\CustomIpProvider::class,
        ],
    ],
],
```

### Geolocation Configuration

```php
// config/passport.php

'server' => [
    'lookup' => [
        // Provider name
        'provider' => 'ip-api',

        // Request timeout (seconds)
        'timeout' => 1.0,

        // Environments where lookup is enabled
        'environments' => ['production', 'local'],

        // Custom providers
        'custom_providers' => [],
    ],
],
```

### Disable Geolocation

To disable IP geolocation:

```php
'lookup' => [
    'provider' => false,
],
```

## Cloudflare IP Detection

If your application uses Cloudflare, enable proper IP detection:

```env
PASSPORT_BROKER_CLOUDFLARE=true
```

This reads the real client IP from the `CF-Connecting-IP` header instead of the proxy IP.

## History Record Structure

When a user logs in, a History record is created:

```php
$history = [
    'user_agent' => 'Mozilla/5.0...',
    'ip' => '192.168.1.1',
    'device_type' => 'desktop',
    'device' => 'Mac',
    'platform' => 'macOS',
    'browser' => 'Chrome',
    'city' => 'New York',
    'region' => 'NY',
    'country' => 'United States',
    'session_id' => 'abc123...',
    'remember_token' => '...',
    'expires_at' => '2024-01-01 12:00:00',
];
```

## Accessing History Data

### Get User's Login History

```php
$user = Auth::user();

// All history (including logged out sessions)
$allHistory = $user->history()->orderBy('created_at', 'desc')->get();

// Active sessions only
$activeSessions = $user->history()
    ->whereNull('deleted_at')
    ->orderBy('created_at', 'desc')
    ->get();

// With soft-deleted sessions
$allSessions = $user->history()
    ->withTrashed()
    ->orderBy('created_at', 'desc')
    ->get();
```

### Display History

```php
foreach ($allHistory as $session) {
    echo "Device: {$session->device} ({$session->device_type})\n";
    echo "Browser: {$session->browser} on {$session->platform}\n";
    echo "Location: {$session->location}\n"; // Computed attribute
    echo "IP: {$session->ip}\n";
    echo "Logged in: {$session->created_at->diffForHumans()}\n";

    if ($session->is_current) {
        echo "Current session\n";
    }

    if ($session->deleted_at) {
        echo "Logged out: {$session->deleted_at->diffForHumans()}\n";
    }
}
```

### Complete History Page Example

**Controller:**

```php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class LoginHistoryController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $history = $user->history()
            ->withTrashed()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('history.index', compact('history'));
    }
}
```

**View:**

```blade
<h1>Login History</h1>

<table class="table">
    <thead>
        <tr>
            <th>Device</th>
            <th>Location</th>
            <th>IP Address</th>
            <th>Login Time</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($history as $session)
            <tr>
                <td>
                    {{ $session->device }} - {{ $session->browser }}<br>
                    <small>{{ $session->platform }}</small>
                </td>
                <td>{{ $session->location ?? 'Unknown' }}</td>
                <td>{{ $session->ip }}</td>
                <td>{{ $session->created_at->format('Y-m-d H:i:s') }}</td>
                <td>
                    @if($session->is_current)
                        <span class="badge badge-success">Current</span>
                    @elseif($session->deleted_at)
                        <span class="badge badge-secondary">
                            Logged out {{ $session->deleted_at->diffForHumans() }}
                        </span>
                    @elseif($session->expires_at && $session->expires_at->isPast())
                        <span class="badge badge-warning">Expired</span>
                    @else
                        <span class="badge badge-info">Active</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

{{ $history->links() }}
```

## Performance Considerations

### Timeout Configuration

Set appropriate timeout for IP lookups:

```php
'lookup' => [
    'timeout' => 1.0, // 1 second max
],
```

If the lookup takes longer, it's skipped and location fields remain null.

### Selective Environments

Only enable geolocation in production:

```php
'lookup' => [
    'environments' => ['production'],
],
```

This avoids unnecessary API calls during development.

### Local Database

For high-traffic applications, use IP2Location Lite with a local database to avoid:
- External API rate limits
- Network latency
- Privacy concerns

## Privacy & GDPR

### Data Collection Notice

Inform users that you collect:
- IP addresses
- Device information
- Geographic location

### Data Retention

Implement data retention policies:

```php
// Delete old history records
$user->history()
    ->where('created_at', '<', now()->subMonths(6))
    ->forceDelete();
```

### User Rights

Allow users to:
- View their login history
- Delete specific sessions
- Request data deletion
