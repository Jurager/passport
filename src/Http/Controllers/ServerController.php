<?php

namespace Jurager\Passport\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Jurager\Passport\ServerBrokerManager;
use Jurager\Passport\SessionManager;
use Jurager\Passport\Http\Middleware\ValidateBroker;
use Jurager\Passport\Http\Middleware\ServerAuthenticate;
use Jurager\Passport\Http\Concerns\Authenticate;
use Jurager\Passport\Events;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ServerController extends Controller
{
    use Authenticate;

    protected ServerBrokerManager $broker;

    protected SessionManager $session;

    /**
     * @param ServerBrokerManager $broker
     * @param SessionManager $session
     */
    public function __construct(ServerBrokerManager $broker, SessionManager $session)
    {
        $this->middleware(ValidateBroker::class)->except('attach');
        $this->middleware(ServerAuthenticate::class)->only(['profile', 'logout']);

        $this->broker = $broker;
        $this->session = $session;
    }

    /**
     * Attach client broker to server
     *
     * @param Request $request
     * @return Response|RedirectResponse
     * @throws \JsonException
     */
    public function attach(Request $request): Response|RedirectResponse
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
        $generated = $this->broker->generateAttachChecksum($broker_id, $token);

        // Compare generated and received checksum
        //
        if (!$checksum || $checksum !== $generated) {

            // Failed checksum comprehension
            //
            return response('Invalid checksum', 400);
        }

        // Generate new session
        //
        $sid = $this->broker->generateSessionId($broker_id, $token);

        // Start a new session
        //
        $this->session->start($sid);

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
     * @throws \JsonException
     */
    public function login(Request $request): JsonResponse
    {
        // Retrieve broker session from request
        //
        $sid = $this->broker->getBrokerSessionId($request);

        // Check if session exists in storage
        //
        if (!$this->session->has($sid)) {

            // Broker must be attached before authenticating users
            //
            return response()->json(['code' => 'not_attached', 'message' => 'Client broker not attached.'], 403);
        }

        // Authenticate user from request
        //
        if ($this->authenticate($request, $this)) {

            // Get the currently authenticated user
            //
            $user = $this->guard()->user();

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
        // Additional verification
        //
        $user = $this->afterAuthenticatingUser($this->guard()->user(), $request);

        // Failed verification
        //
        if (!$user) {

            //  Return unauthenticated response
            //
            return response()->json([], 401);
        }

        // Return current user information
        //
        return response()->json($this->userInfo($user, $request));
    }

    /**
     * Logout user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        // Retrieve current user
        //
        $user = $request->user();

        // Retrieve broker session
        //
        $sid = $this->broker->getBrokerSessionId($request);

        // Reset user session data
        //
        $this->session->setUserData($sid, null);

        //  Succeeded logout event
        //
        event(new Events\Logout($user));

        //  Succeeded logout response
        //
        return response()->json(['success' => true]);
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
            return response()->json(['message' => 'Command not found.'], 404);
        }

        // Create closure
        //
        $closure = $commands[$command];

        // Retrieve broker model from request
        //
        $broker = $this->broker->getBrokerFromRequest($request);

        // Return closure if it is callable
        //
        if (is_callable($closure)) {
            return response()->json($closure($broker, $request));
        }

        // Return closure not callable
        //
        return response()->json(['message' => 'Command can\'t be executed.'], 400);
    }
}
