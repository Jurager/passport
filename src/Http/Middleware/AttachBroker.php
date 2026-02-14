<?php

namespace Jurager\Passport\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Jurager\Passport\Broker;
use Jurager\Passport\Exceptions\RedirectLoopException;

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
            // Prevent infinite redirect loops
            $redirectCount = session('sso_attach_redirect_count', 0);
            $maxAttempts = config('passport.max_redirect_attempts', 3);

            if ($redirectCount >= $maxAttempts) {
                session()->forget('sso_attach_redirect_count');
                throw new RedirectLoopException('SSO attach', $redirectCount);
            }

            session(['sso_attach_redirect_count' => $redirectCount + 1]);

            return redirect()->route('sso.broker.attach', ['return_url' => $request->fullUrl()], 307)->send();
        }

        // Reset redirect counter on successful attach
        session()->forget('sso_attach_redirect_count');

        return $next($request);
    }
}
