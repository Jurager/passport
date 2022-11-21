<?php

namespace Jurager\Passport\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Jurager\Passport\Models\History;

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
    public function history(): mixed
    {
        return $this->morphMany(History::class, 'authenticatable');
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
        return $this->history()->where('session_id', Session::getId())->first();
    }

    /**
     * Destroy a session by identifier.
     *
     * @param int|null $history_id
     * @return bool
     */
    public function logout(int $history_id = null): bool
    {
        // Find the login entry by identifier or current session
        //
        $history = $history_id ? $this->history()->find($history_id) : $this->current();

        // If found try to revoke session
        //
        return $history && !empty($history->revoke());
    }

    /**
     * Destroy all sessions, except the current one.
     *
     * @return mixed
     */
    public function logoutOthers(): mixed
    {
        return $this->history()->where(function (Builder $query) {
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
        return $this->history()->revoke();
    }
}
