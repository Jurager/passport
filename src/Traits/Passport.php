<?php

namespace Jurager\Passport\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Session;
use Jurager\Passport\Models\History;

trait Passport
{
    public function history(): mixed
    {
        return $this->morphMany(History::class, 'authenticatable');
    }

    /**
     * Get the current user's login.
     */
    public function current(): mixed
    {
        // Find current authenticated use history entry
        return $this->history()->where('session_id', Session::getId())->first();
    }

    /**
     * Destroy a session by identifier.
     */
    public function logoutById(?int $history_id = null): bool
    {
        // Find the login entry by identifier or current session
        $history = $history_id ? $this->history()->find($history_id) : $this->current();

        // If found try to revoke session
        return $history && ! empty($history->revoke());
    }

    /**
     * Destroy all sessions, except the current one.
     *
     * @return bool
     */
    public function logoutOthers(): bool
    {
        $histories = $this->history()->where(function (Builder $query) {
            return $query->where('session_id', '!=', Session::getId());
        })->get();

        $histories->each(static function ($history) {

            // Session is revoked by history
            $history->revoke();
        });

        return true;
    }

    /**
     * Destroy all sessions.
     */
    public function logoutAll(): bool
    {
        // Get all user histories items
        $histories = $this->history()->get();

        $histories->each(static function ($history) {

            // Session is revoked by history
            $history->revoke();
        });

        return true;
    }
}
