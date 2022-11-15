<?php

namespace Jurager\Passport\Listeners;

use Jurager\Passport\Factories\HistoryFactory;
use Illuminate\Support\Facades\Session;
use Jurager\Passport\RequestContext;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login as LoginEvent;
use Illuminate\Auth\Recaller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class AuthEventSubscriber
{
    /**
     * @var Request
     */
    protected Request $request;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param LoginEvent $event
     * @return void
     */
    public function handleSuccessfulLogin(LoginEvent $event)
    {
        if ($this->tracked($event->user)) {

            if (Auth::guard()->viaRemember()) {

                // Logged in via remember token
                //
                //if (!is_null($recaller = $this->recaller())) {

                //    // Update session identifier
                //    //
                //    History::where('remember_token', $recaller->token())->update(['session_id' => session()->getId()]);
                //}

            } else {

                // Initial login

                // Regenerate the session identifier to avoid session fixation attacks
                //
                Session::regenerate();

                // Get information as possible about the request
                //
                $context = new RequestContext;

                // Build a new history
                //
                $history = HistoryFactory::build($event, $context);

                // Set the expiration date based on whether it is a remembered login or not
                //if ($event->remember) {
                //    $history->expiresAt(Carbon::now()->addDays(config('auth_tracker.remember_lifetime', 365)));
                //} else {
                //    $history->expiresAt(Carbon::now()->addMinutes(config('session.lifetime')));
                //}

                // Attach the login to the user and save it
                //
                $event->user->history()->save($history);

                // Update remember token
                //
                $this->updateRememberToken($event->user, Str::random(60));
            }
        }
    }

    /**
     * @param $event
     * @return void
     */
    public function handleSuccessfulLogout($event)
    {
        if ($this->tracked($event->user)) {

            // Delete history
            //
            $event->user->history()->where('session_id', session()->getId())->delete();
        }
    }

    /**
     * Get the decrypted recaller cookie for the request.
     *
     * @return Recaller|null
     */
    protected function recaller()
    {
        if (is_null($this->request)) {
            return null;
        }

        if ($recaller = Cookie::get(Auth::guard()->getRecallerName())) {
            return new Recaller($recaller);
        }

        return null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    protected function updateRememberToken(Authenticatable $user, $token)
    {
        $user->setRememberToken($token);
        $user->timestamps = false;
        $user->save();
    }

    /**
     * Tracking enabled for this user?
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return bool
     */
    protected function tracked($user)
    {
        return in_array('Jurager\Passport\Traits\Passport', class_uses($user));
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe(\Illuminate\Events\Dispatcher $events): void
    {
        $events->listen(
            'Illuminate\Auth\Events\Login',
            'Jurager\Passport\Listeners\AuthEventSubscriber@handleSuccessfulLogin'
        );

        $events->listen(
            'Illuminate\Auth\Events\Logout',
            'Jurager\Passport\Listeners\AuthEventSubscriber@handleSuccessfulLogout'
        );
    }
}