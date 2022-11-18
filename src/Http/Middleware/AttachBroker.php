<?php

namespace Jurager\Passport\Http\Middleware;

use Closure;
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
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(\Illuminate\Http\Request $request, Closure $next): mixed
    {
        if(!$this->broker->isAttached($request)) {
            return redirect()->route('sso.broker.attach', ['return_url' => $this->request->fullUrl()]);
        }


        return $next($request);
    }
}