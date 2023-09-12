<?php

namespace Jurager\Passport\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Session;
use Jurager\Passport\Models\History;

trait Passport
{

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
    public function logoutById(int $history_id = null): bool
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
    public function logoutOthers(): bool
    {
        $histories = $this->history()->where(function (Builder $query) {
            return $query->where('session_id', '!=', Session::getId())->orWhereNull('session_id');
        })->get();

        // If it has histories items
        //
        if ($histories) {

            // Session is revoked by history
            //
            foreach ($histories as $history) {
                $history->revoke();
            }

            // Success
            //
            return true;
        }

        return false;
    }

    /**
     * Destroy all sessions.
     *
     * @return bool
     */
    public function logoutAll(): bool
    {
        // Get all user histories items
        //
        $histories = $this->history()->get();

        // If it has histories items
        //
        if ($histories) {

            // Session is revoked by history
            //
            foreach ($histories as $history) {
                $history->revoke();
            }

            // Success
            //
            return true;
        }

        return false;
    }
}
