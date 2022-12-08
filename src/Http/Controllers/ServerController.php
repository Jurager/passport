<?php

namespace Jurager\Passport\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use JsonException;
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

    /**
     * @param Server $server
     * @param ServerSessionManager $storage
     */
    public function __construct(Server $server, ServerSessionManager $storage)
    {
        $this->middleware(ValidateBroker::class)->except('attach');
        $this->middleware(ServerAuthenticate::class)->only(['profile', 'logout']);

        $this->server = $server;
        $this->storage = $storage;
    }

    /**
     * Attach client broker to server
     *
     * @param Request $request
     * @return Response|JsonResponse|RedirectResponse
     */
    public function attach(Request $request): Response|JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'broker' => 'required|string',
            'token' => 'required|string',
            'checksum' => 'required|string',
            'return_url' => 'nullable|string'
        ]);

         if ($validator->fails()) {
            return response($validator->errors() . '', 400);
        }

        $broker_id = $request->input('broker');
        $token     = $request->input('token');
        $checksum  = $request->input('checksum');
        $return_url = $request->input('return_url');

        // Generate attach checksum
        //
        $generated = $this->server->generateAttachChecksum($broker_id, $token);

        // Compare generated and received checksum
        //
        if (!$checksum || $checksum !== $generated) {

            // Failed checksum comprehension
            //
            return response(trans('passport::errors.invalid_checksum'), 400);
        }

        // Generate new session
        //
        $sid = $this->server->generateSessionId($broker_id, $token);

        // Start a new session
        //
        $this->storage->start($sid);

        // Response, if request not containing redirecting route
        //
        if (!$return_url) {
            return response()->json(['success' => 'attached']);
        }

        // Redirect to
        //
        return redirect()->away($return_url);
    }

    /**
     * Login
     *
     * @param Request $request
     * @return JsonResponse
     * @throws JsonException
     */
    public function login(Request $request): JsonResponse
    {
        // Retrieve broker session from request
        //
        $sid = $this->server->getBrokerSessionId($request);

        // Check if session exists in storage
        //
        if (is_null($this->storage->get($sid))) {

            // Broker must be attached before authenticating users
            //
            return response()->json(['code' => 'not_attached', 'message' => trans('passport::errors.not_attached')], 403);
        }

        // Authenticate user from request
        //
        if ($this->authenticate($request, $this)) {

            // Get the currently authenticated user
            //
            $user = Auth::guard()->user();

            // Get request information
            //
            $context = new RequestContext;

            // Build a new history
            //
            $history = HistoryFactory::build($context);

            // Attach the login to the user and save it
            //
            $user->history()->save($history);

            //  Succeeded auth event
            //
            event(new Events\AuthSucceeded($user, $request));

            // Return current user information
            //
            return response()->json($this->userInfo($user, $request));
        }

        //  Failed auth event
        //
        event(new Events\AuthFailed($this->loginCredentials($request), $request));

        //  Return unauthenticated response
        //
        return response()->json([], 401);
    }

    /**
     * Get user profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        // Get authorized account
        //
        $user = Auth::guard()->user();

        if($user) {

            // Additional verification
            //
            $callback = $this->afterAuthenticatingUser($user, $request);

            // Failed verification
            //
            if (!$callback) {

                //  Return unauthenticated response
                //
                return response()->json([], 401);
            }

            // Return current user information
            //
            return response()->json($this->userInfo($callback, $request));
        }

        //  Return unauthenticated response
        //
        return response()->json([], 401);
    }

    /**
     * Logout user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        // Get authorized account
        //
        $user = Auth::guard()->user();

        // Available methods
        //
        $methods = ['id', 'all', 'others'];

        // Check user authorisation
        //
        if($user) {

            // Check accepted method
            //
            if(in_array($method = $request->get('method'), $methods, true)) {

                // By session identifier
                //
                if(($method === 'id') && !$user->logoutById($request->get('id'))) {
                    return response()->json(['error' => true]);
                }

                // By all or others method
                //
                if(($method === 'all' || $method === 'others') && !$user->{'logout'.ucfirst($method)}()) {
                    return response()->json(['error' => true]);
                }

                //  Succeeded logout event
                //
                event(new Events\Logout($user));

                //  Succeeded logout response
                //
                return response()->json(['success' => true]);
            }

            //  Return bad request response
            //
            return response()->json([], 400);
        }

        //  Return unauthenticated response
        //
        return response()->json([], 401);
    }

    /**
     * Run command
     *
     * @param Request $request
     * @param $command
     * @return JsonResponse
     */
    public function commands(Request $request, $command): JsonResponse
    {
        // Retrieve commands from configuration
        //
        $commands = config('passport.commands', []);

        // Command not found in configuration
        //
        if (!array_key_exists($command, $commands)) {
            return response()->json(['message' => trans('passport::errors.command_not_found')], 404);
        }

        // Create closure
        //
        $closure = $commands[$command];

        // Retrieve broker model from request
        //
        $broker = $this->server->getBrokerFromRequest($request);

        // Return closure if it is callable
        //
        if (is_callable($closure)) {
            return response()->json($closure($broker, $request));
        }

        // Return closure not callable
        //
        return response()->json(['message' => trans('passport::errors.command_not_callable')], 400);
    }
}
