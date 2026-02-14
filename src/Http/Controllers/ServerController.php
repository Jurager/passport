<?php

namespace Jurager\Passport\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use JsonException;
use Jurager\Passport\Encryption;
use Jurager\Passport\Events;
use Jurager\Passport\Factories\HistoryFactory;
use Jurager\Passport\Http\Concerns\Authenticate;
use Jurager\Passport\Http\Middleware\ServerAuthenticate;
use Jurager\Passport\Http\Middleware\ValidateBroker;
use Jurager\Passport\RequestContext;
use Jurager\Passport\Server;
use Jurager\Passport\Session\ServerSessionManager;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ServerController extends Controller
{
    use Authenticate;

    protected Server $server;

    protected ServerSessionManager $storage;

    protected Encryption $encryption;

    public function __construct(Server $server, ServerSessionManager $storage)
    {
        $this->middleware(ValidateBroker::class)->except('attach');
        $this->middleware(ServerAuthenticate::class)->only(['profile', 'logout']);

        $this->server = $server;
        $this->storage = $storage;
        $this->encryption = new Encryption();
    }

    /**
     * Attach client broker to server
     */
    public function attach(Request $request): Response|JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'broker' => 'required|string',
            'token' => 'required|string',
            'checksum' => 'required|string',
            'return_url' => 'nullable|string|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $broker_id = $request->input('broker');
        $token = $request->input('token');
        $checksum = $request->input('checksum');
        $return_url = $request->input('return_url');

        if ($return_url && !$this->isAllowedRedirectUrl($return_url)) {
            return response(trans('passport::errors.invalid_return_url'), 400);
        }

        // Verify broker and checksum
        try {
            // Find broker once to avoid duplicate DB queries
            $broker = $this->server->findBrokerById($broker_id);

            if (!$this->server->verifyAttachChecksum($broker, $token, $checksum)) {
                return response(trans('passport::errors.invalid_checksum'), 400);
            }

            // Generate new session using the same broker instance
            $sid = $this->server->generateSessionId($broker, $token);
        } catch (Exception $e) {
            // Broker not found or other error
            return response($e->getMessage(), 400);
        }

        // Start a new session
        $this->storage->start($sid);

        // Response, if request not containing redirecting route
        if (! $return_url) {
            return response()->json(['success' => 'attached']);
        }

        // Redirect to
        return redirect()->away($return_url);
    }

    /**
     * Login
     *
     * @throws JsonException
     */
    public function login(Request $request): JsonResponse
    {
        // Retrieve broker session from request
        $sid = $this->server->getBrokerSessionId($request);

        // Check if session exists in storage
        if (is_null($this->storage->get($sid))) {

            // Broker must be attached before authenticating users
            return response()->json(['code' => 'not_attached', 'message' => trans('passport::errors.not_attached')], 403);
        }

        // Authenticate user from request
        if ($this->authenticate($request, $this)) {

            // Get the currently authenticated user
            $user = Auth::guard()->user();

            // Ensure user was successfully retrieved
            if (!$user) {
                return response()->json([], 401);
            }

            // Get request information
            $context = new RequestContext();

            // Build a new history
            $history = HistoryFactory::build($context);

            // Attach the login to the user and save it
            $user->history()->save($history);

            // Return current user information
            return response()->json($this->userInfo($user, $request));
        }

        //  Failed auth event
        event(new Events\Unauthenticated($this->loginCredentials($request), $request));

        // Unauthorized exception
        return response()->json([], 401);
    }

    /**
     * Get user profile
     */
    public function profile(Request $request): JsonResponse
    {
        // Get authorized account
        $user = Auth::guard()->user();

        if ($user) {

            // Additional verification
            $callback = $this->afterAuthenticatingUser($user, $request);

            // Failed verification
            if (! $callback) {

                // Unauthorized exception
                //return response()->json(['code' => 'unauthorized', 'message' => trans('passport::errors.not_authorized') ], 401);
                return response()->json([], 401);
            }

            // Return current user information
            return response()->json($this->userInfo($callback, $request));
        }

        // Unauthorized exception
        //return response()->json(['code' => 'unauthorized', 'message' => trans('passport::errors.not_authorized') ], 401);
        return response()->json([], 401);
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        // Get authorized account
        $user = Auth::guard()->user();

        // Available methods
        $methods = ['id', 'all', 'others'];

        // Check user authorization
        if ($user) {

            // Check accepted method
            if (in_array($method = $request->input('method'), $methods, true)) {

                // By session identifier
                if (($method === 'id') && ! $user->logoutById($request->input('id'))) {
                    return response()->json(['error' => true]);
                }

                // By all or others method
                if (($method === 'all' || $method === 'others') && ! $user->{'logout'.ucfirst($method)}()) {
                    return response()->json(['error' => true]);
                }

                // Succeeded logout event
                event(new Events\Logout($user));

                // Succeeded logout response
                return response()->json(['success' => true]);
            }

            // Return bad request response
            return response()->json([], 400);
        }

        // Unauthorized exception
        //return response()->json(['code' => 'unauthorized', 'message' => trans('passport::errors.not_authorized') ], 401);
        return response()->json([], 401);
    }

    /**
     * Run command
     */
    public function commands(Request $request, $command): JsonResponse
    {
        // Retrieve commands from configuration
        $commands = config('passport.commands', []);

        // Command not found in configuration
        if (! is_array($commands) || ! array_key_exists($command, $commands)) {
            return response()->json(['message' => trans('passport::errors.command_not_found')], 404);
        }

        // Create closure
        $closure = $commands[$command];

        // Return closure if it is callable
        if (is_callable($closure)) {
            return response()->json($closure($this->server, $request));
        }

        // Return closure not callable
        return response()->json(['message' => trans('passport::errors.command_not_callable')], 400);
    }

    /**
     * Validate if redirect URL is allowed to prevent open redirect attacks
     */
    protected function isAllowedRedirectUrl(?string $url): bool
    {
        if (empty($url)) {
            return true;
        }

        // Parse the URL
        $parsedUrl = parse_url($url);

        // Allow relative URLs (no host)
        if (!isset($parsedUrl['host'])) {
            return true;
        }

        // Get allowed hosts from configuration
        $allowedHosts = config('passport.allowed_redirect_hosts', []);

        // If no hosts configured, allow all (backwards compatibility)
        // In production, you should configure allowed hosts
        if (empty($allowedHosts)) {
            return true;
        }

        // Check if the host is in the allowed list
        foreach ($allowedHosts as $allowedHost) {
            $allowedHost = trim($allowedHost);
            if ($parsedUrl['host'] === $allowedHost || str_ends_with($parsedUrl['host'], '.' . $allowedHost)) {
                return true;
            }
        }

        return false;
    }
}
