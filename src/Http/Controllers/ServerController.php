<?php

namespace Jurager\Passport\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Jurager\Passport\ServerBrokerManager;
use Jurager\Passport\SessionManager;
use Jurager\Passport\Http\Middleware\ValidateBroker;
use Jurager\Passport\Http\Middleware\ServerAuthenticate;
use Jurager\Passport\Http\Concerns\Authenticate;
use Jurager\Passport\Events;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class ServerController extends Controller
{
    use Authenticate;

    protected ServerBrokerManager $broker;

    protected SessionManager $session;

    protected $return_type = null;

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
     * @return \Illuminate\Http\Response
     * @throws \JsonException
     */
    public function attach(Request $request): \Illuminate\Http\Response
    {
        $validator = Validator::make($request->all(), [
            'broker' => 'required',
            'token' => 'required',
            'checksum' => 'required'
        ]);

         if ($validator->fails()) {
            return response($validator->errors() . '', 400);
        }

        $this->detectReturnType($request);

        if (!$this->return_type) {
            return response('No return url specified', 400);
        }

        $broker_id = $request->input('broker');
        $token     = $request->input('token');
        $checksum  = $request->input('checksum');
        $callback = $request->input('callback');
        $return_url = $request->input('return_url');

        $gen_checksum = $this->broker->generateAttachChecksum($broker_id, $token);

        if (!$checksum || $checksum !== $gen_checksum) {
            return response('Invalid checksum', 400);
        }

        $sid = $this->broker->generateSessionId($broker_id, $token);

        $this->session->start($sid);


        if ($this->return_type === 'json') {
            return response()->json(['success' => 'attached']);
        }

        if ($this->return_type === 'jsonp') {
            $data = json_encode(['success' => 'attached'], JSON_THROW_ON_ERROR);
            return response("$callback($data, 200)");
        }

        if ($this->return_type === 'redirect') {
            return redirect()->away($return_url);
        }
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
        $sid = $this->broker->getBrokerSessionId($request);

        if (is_null($this->session->get($sid))) {
            return response()->json([
                'code' => 'not_attached',
                'message' => 'Client broker not attached.'
            ], 403);
        }

        if ($this->authenticate($request, $this)) {
            $user = $this->guard()->user();

            event(new Events\LoginSucceeded($user, $request));

            return response()->json($this->userInfo($user, $request));
        }

        event(new Events\LoginFailed($this->loginCredentials($request), $request));

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
        $user = $this->afterAuthenticatingUser($this->guard()->user(), $request);

        if (!$user) {
            return response()->json([], 401);
        }

        return response()->json(
            $this->userInfo($user, $request)
        );
    }

    /**
     * Logout user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        $sid = $this->broker->getBrokerSessionId($request);

        $this->session->setUserData($sid, null);

        event(new Events\Logout($user));

        return response()->json(['success' => true]);
    }

    /**
     * Set return_type based on request
     *
     * @param Request $request
     * @return void
     */
    protected function detectReturnType(Request $request): void
    {
        if ($request->has('return_url')) {
            $this->return_type = 'redirect';
        } elseif ($request->has('callback')) {
            $this->return_type = 'jsonp';
        } elseif ($request->expectsJson()) {
            $this->return_type = 'json';
        }
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
        $commands = config('passport.commands', []);

        if (!array_key_exists($command, $commands)) {
            return response()->json(['message' => 'Command not found.'], 404);
        }

        $closure = $commands[$command];
        $broker = $this->broker->getBrokerFromRequest($request);

        if (is_callable($closure)) {
            return response()->json($closure($broker, $request));
        }

        return response()->json(null);
    }
}
