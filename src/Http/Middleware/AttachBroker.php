<?php

namespace Jurager\Passport\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Jurager\Passport\Broker;

class AttachBroker
{
    protected Broker $broker;

    public function __construct(Broker $broker)
    {
        $this->broker = $broker;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $this->broker->isAttached()) {
            return redirect()->route('sso.broker.attach', ['return_url' => $request->fullUrl()], 307)->send();
        }

        return $next($request);
    }
}
