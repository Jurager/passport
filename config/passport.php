<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configurations for SSO Server
    |--------------------------------------------------------------------------
    */
    'server' => [

        /*
        |--------------------------------------------------------------------------
        | Driver
        |--------------------------------------------------------------------------
        |
        | Passport server driver, used to store brokers
        | Supported values: "model", "array"
        |
        */

        'driver' => env('PASSPORT_SERVER_DRIVER', 'model'),

        /*
        |--------------------------------------------------------------------------
        | Model
        |--------------------------------------------------------------------------
        |
        | Broker model class, required for "model" driver.
        |
        */

        'model' => env('PASSPORT_SERVER_MODEL', 'App\Models\Broker'),

        /*
        |--------------------------------------------------------------------------
        | Model Identification Field
        |--------------------------------------------------------------------------
        |
        |  Broker model id field, required for model driver.
        |
        */

        'id_field' => env('PASSPORT_SERVER_ID_FIELD', 'field'),

        /*
        |--------------------------------------------------------------------------
        | Model Secret Field
        |--------------------------------------------------------------------------
        |
        |  Broker model secret field, required for model driver.
        |
        */

        'secret_field' => env('PASSPORT_SERVER_SECRET_FIELD', 'secret'),

        /*
        |--------------------------------------------------------------------------
        | Brokers
        |--------------------------------------------------------------------------
        |
        |  Array of available brokers and it's secrets, required for "array" driver. ['id' => 'secret']
        |
        */

        'brokers' => [],

        /*
        |--------------------------------------------------------------------------
        | Parser
        |--------------------------------------------------------------------------
        |
        | Choose which parser to use to parse the User-Agent.
        | You will need to install the package of the corresponding parser.
        |
        | Supported values:
        | 'agent' (see https://github.com/jenssegers/agent)
        | 'whichbrowser' (see https://github.com/WhichBrowser/Parser-PHP)
        |
        */

        'parser' => 'agent',

        /*
        |--------------------------------------------------------------------------
        | IP Address Lookup
        |--------------------------------------------------------------------------
        |
        | This package provides a feature to get additional data about the client
        | IP (like the geolocation) by calling an external API.
        |
        | This feature makes usage of the Guzzle PHP HTTP client to make the API
        | calls, so you will have to install the corresponding package
        | (see https://github.com/guzzle/guzzle) if you are considering to enable
        | the IP address lookup.
        |
        */

        'lookup' => [

            /*
            |--------------------------------------------------------------------------
            | Provider
            |--------------------------------------------------------------------------
            |
            | If you want to enable the IP address lookup, choose a supported
            | IP address lookup provider.
            |
            | Supported values:
            | - 'ip2location-lite' (see https://lite.ip2location.com/database/ip-country-region-city)
            | - 'ip-api' (see https://members.ip-api.com)
            | - false (to disable the IP address lookup feature)
            | - any other custom name declared as a key of the custom_providers array
            |
            */

            'provider' => 'ip-api',


            /*
            |--------------------------------------------------------------------------
            | Timeout
            |--------------------------------------------------------------------------
            |
            | Float describing the number of seconds to wait while trying to connect
            | to the provider's API.
            |
            | If the request takes more time, the IP address lookup will be ignored
            | and the Jurager\Passport\Events\FailedApiCall will be
            | dispatched, receiving the attribute $exception containing the
            | GuzzleHttp\Exception\TransferException.
            |
            | Use 0 to wait indefinitely.
            |
            */

            'timeout' => 1.0,


            /*
            |--------------------------------------------------------------------------
            | Environments
            |--------------------------------------------------------------------------
            |
            | Indicate here an array of environments for which you want to enable
            | the IP address lookup.
            |
            */

            'environments' => [
                'production',
                'local',
            ],

            /*
            |--------------------------------------------------------------------------
            | Custom Providers
            |--------------------------------------------------------------------------
            |
            | You can create your own custom providers for the IP address lookup feature.
            | See in the README file how to create an IP provider class and declare it
            | in the array below.
            |
            | Format: 'name_of_your_provider' => ProviderClassName::class
            |
            */

            'custom_providers' => [],

            /*
            |--------------------------------------------------------------------------
            | Ip2Location
            |--------------------------------------------------------------------------
            |
            | If you are using 'ip2location-lite' provider, here you may change the
            | name of the tables for IPv4 and IPv6.
            |
            */

            'ip2location' => [
                'ipv4_table' => 'ip2location_db3',
                'ipv6_table' => 'ip2location_db3_ipv6',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurations for SSO Client
    |--------------------------------------------------------------------------
    */

    'broker' => [

        /*
        |--------------------------------------------------------------------------
        | Broker ID
        |--------------------------------------------------------------------------
        |
        | Broker id for client configuration. Must be null on Server.
        | Must match any word [a-zA-Z0-9_]
        |
        */

        'client_id' => env('PASSPORT_BROKER_CLIENT_ID'),


        /*
        |--------------------------------------------------------------------------
        | Broker Secret
        |--------------------------------------------------------------------------
        |
        | Broker secret for client configuration. Must be null on Server.
        |
        */

        'client_secret' => env('PASSPORT_BROKER_CLIENT_SECRET'),


        /*
        |--------------------------------------------------------------------------
        | Broker Client Username
        |--------------------------------------------------------------------------
        |
        | Broker client unique username field
        |
        */

        'client_username' => env('PASSPORT_BROKER_CLIENT_USERNAME', 'email'),


        /*
        |--------------------------------------------------------------------------
        | Server URL
        |--------------------------------------------------------------------------
        |
        | The server Url. Required for clients.
        |
        */

        'server_url' => env('PASSPORT_BROKER_SERVER_URL'),

        /*
        |--------------------------------------------------------------------------
        | Auth URL
        |--------------------------------------------------------------------------
        |
        | The auth broker Url. Required if you want only on one broker
        |
        */

        'auth_url' => env('PASSPORT_BROKER_AUTH_URL'),

        /*
        |--------------------------------------------------------------------------
        | Return URL
        |--------------------------------------------------------------------------
        |
        | The return Url. Required for clients.
        |
        */

        'return_url' => env('PASSPORT_BROKER_RETURN_URL', true),

        /*
        |--------------------------------------------------------------------------
        | Cloudlare Usage
        |--------------------------------------------------------------------------
        |
        | Using a cloudflare proxying broke IP detection.
        | Set this to 'true' to obtain client IP address from 'CF-Connecting-IP' header
        |
        */
        'cloudflare' => env('PASSPORT_BROKER_CLOUDFLARE', false),
    ],

    /**
     * Brokers database table name
     */
    'brokers_table_name' => env('PASSPORT_BROKERS_TABLE', 'brokers'),

    /**
     * History database table name
     */
    'history_table_name' => env('PASSPORT_HISTORY_TABLE', 'history'),

    /**
     * Acess Tokens database table name
     */
    'access_tokens_table_name' => env('PASSPORT_ACCESS_TOKENS_TABLE', 'access_tokens'),

    /**
    * Enable debug mode
    */
    'debug' => env('PASSPORT_DEBUG', false),

    /**
    * Session time to live, default to 10 minutes.
    * Set to null to store forever
    */
    'storage_ttl' => env('PASSPORT_STORAGE_TTL', 600),

    /**
     * Prefix used to declare client routes
     */
    'routes_prefix_client' => env('PASSPORT_ROUTES_PREFIX_CLIENT', 'sso/client'),

    /**
     * Prefix used to declare server routes
     */
    'routes_prefix_server' => env('PASSPORT_ROUTES_PREFIX_SERVER', 'sso/server'),

    /**
     * Closure that return the user info from server. This function allows you
     * to return additional payload data to the clients. By default, the user
     * attributes are returned by calling $user->toArray().
     * E.g. 'user_info' => function($user, $broker, $request) {
     *      $payload = $user->toArray();
     *      $payload['roles'] = $user->getRolesByApp($broker->id);
     *
     *      return $payload
     * }
     */
    'user_info' => null,

    /**
     * Closure that is called after a user is authenticated. Used for
     * additional verification, for example if you don't want to allow
     * unverified users. This function should return a boolean.
     * E.g. 'after_authenticating' => function($user, $request) {
     *      return $user->verified;
     * }
     */
    'after_authenticating' => null,

    /**
     * Closure that save the user in the client local database.
     * E.g. 'user_create_strategy' => function ($data) {
     *    return \App\Models\User::create([
     *        'username' => $data['username'],
     *        'email' => $data['email'],
     *        'admin' => $data['admin'],
     *        'password' => '',
     *    ]);
     * }
     */
    'user_create_strategy' => null,

    /**
     * Commands are customs additional methods that could be called
     * from the client. For example if you want to check the authenticated
     * user role.
     */
    'commands' => [
        /**
         * Should return an array
         * 'hasRole' => function($user, $broker, $request) {
         *     $role = $request->input('role');
         *     $success = $user->roles->contains($role);
         *     return ['success' => $success];
         * }
         */
    ]
];