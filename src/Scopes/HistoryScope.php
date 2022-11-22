<?php

namespace Jurager\Passport\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Session;

class HistoryScope implements Scope
{

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        $builder->macro('revoke', function (Builder $builder) {

            $history = $builder->get();

            if ($history->isNotEmpty()) {

                // Destroy sessions
                foreach ($history->pluck('session_id')->filter() as $session_id) {
                    Session::remove($session_id);
                }

                // Delete logins
                return $builder->delete();
            }

            return false;
        });
    }
}