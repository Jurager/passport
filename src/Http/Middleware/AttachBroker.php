<?php

namespace Jurager\Passport\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Jurager\Passport\Broker;
use Jurager\Passport\Server;
use Jurager\Passport\Exceptions\InvalidSessionIdException;
use Jurager\Passport\Exceptions\InvalidClientException;
use Jurager\Passport\Exceptions\UnauthorizedException;

class AttachBroker
{
    protected Broker $broker;

    public function __construct(Broker $broker)
    {
        $this->broker = $broker;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if(!$this->broker->isAttached()) {
            return redirect()->route('sso.broker.attach', ['return_url' => $request->fullUrl()], 307)->send();
        }


        return $next($request);
    }
}