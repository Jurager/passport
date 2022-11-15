<?php

namespace Jurager\Passport\Traits;

use Illuminate\Support\Facades\Session;

trait Passport
{
    /**
     * The passport payload data
     *
     * @var mixed
     */
    protected mixed $passport_payload;

    /**
     * Set payload data
     *
     * @param mixed $payload
     */
    public function setPayload(mixed $payload): void
    {
        $this->passport_payload = $payload;
    }

    /**
     * Return payload data
     *
     * @return mixed
     */
    public function getPayload(): mixed
    {
        return $this->passport_payload;
    }

    /**
     * @return mixed
     */
    public function histories(): mixed
    {
        return $this->morphMany(Login::class, 'authenticatable');
    }

    /**
     * Get the current user's login.
     *
     * @return mixed
     */
    public function current(): mixed
    {
        // Find current authenticated use history entry
        //
        return $this->histories()->where('session_id', Session::getId())->first();
    }

    /**
     * Destroy a session by identifier.
     *
     * @param int|null $id
     * @return bool
     * @throws \Exception
     */
    public function logout(int $id = null): bool
    {
        // Find the login entry by identifier or current session
        //
        $history = $id ? $this->histories()->find($id) : $this->current();

        // If found try to revoke session
        //
        return $history && !empty($history->revoke($history->session_id));
    }

    /**
     * Destroy all sessions, except the current one.
     *
     * @return mixed
     */
    public function logoutOthers(): mixed
    {
        return $this->histories()->where(function (Builder $query) {
            return $query->where('session_id', '!=', Session::getId())->orWhereNull('session_id');
        })->revoke();
    }

    /**
     * Destroy all sessions.
     *
     * @return mixed
     */
    public function logoutAll(): mixed
    {
        return $this->histories()->revoke();
    }
}
