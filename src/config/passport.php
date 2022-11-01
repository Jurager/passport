<?php

return [

    /**
     * Configurations for SSO Server
     */
    'server' => [

        /**
        * Passport server driver, used to store brokers
        * Supported drivers: "model", "array",
        */
        'driver' => env('PASSPORT_SERVER_DRIVER', 'model'),

        /**
         * Broker model class, required for "model" driver.
         */
        'model' => env('PASSPORT_SERVER_MODEL', 'App\Models\Broker'),

        /**
         * Broker model id field, required for model driver.
         */
        'id_field' => env('PASSPORT_SERVER_ID_FIELD', 'field'),

        /**
         * Broker model secret field, required for model driver.
         */
        'secret_field' => env('PASSPORT_SERVER_SECRET_FIELD', 'secret'),

        /**
         * Array of available brokers and it's secrets, required for "array" driver. ['id' => 'secret']
         */
        'brokers' => [],
    ],

    /**
    * Configurations for SSO Client
    */
    'broker' => [

        /**
        * Broker id for client configuration. Must be null on Server. Must match any word [a-zA-Z0-9_]
        */
        'client_id' => env('PASSPORT_BROKER_CLIENT_ID'),

        /**
        * Broker secret for client configuration. Must be null on SSO Server
        */
        'client_secret' => env('PASSPORT_BROKER_CLIENT_SECRET'),

        /**
        * Broker client unique username
        */
        'client_username' => env('PASSPORT_BROKER_CLIENT_USERNAME', 'email'),

        /**
        * The server Url. Required for clients.
        */
        'server_url' => env('PASSPORT_BROKER_SERVER_URL'),

        /**
        * The return Url. Required for clients.
        */
        'return_url' => env('PASSPORT_BROKER_RETURN_URL', true),
    ],

    /**
    * Enable debug mode
    */
    'debug' => env('PASSPORT_DEBUG', false),

    /**
    * Session live time Default to 60 seconds. Set to null to store forever
    */
    'session_ttl' => env('PASSPORT_SESSION_TTL', 60),

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
