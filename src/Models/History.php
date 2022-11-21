<?php

namespace Jurager\Passport\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Session;
use Illuminate\Database\Eloquent\MassPrunable;
use Jurager\Passport\Scopes\HistoryScope;

class History extends Model
{
    use SoftDeletes, MassPrunable;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'authenticatable_type',
        'authenticatable_id',
        'session_id',
        'remember_token',
        'oauth_access_token_id',
        'personal_access_token_id',
        'expires_at',
        'deleted_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['is_current'];

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setTable(config('passport.history_table_name'));

        parent::__construct($attributes);
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope(new HistoryScope);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function authenticatable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Add "location" attribute.
     *
     * @return string|null
     */
    public function getLocationAttribute(): ?string
    {
        $location = [ $this->city, $this->region, $this->country ];

        return array_filter($location) ? implode(', ', $location) : null;
    }

    /**
     * Add the "is_current" attribute.
     *
     * @return bool
     */
    public function getIsCurrentAttribute(): bool
    {
        // Check the session is current
        //
        return $this->session_id === Session::getId();
    }

    /**
     * Revoke the login.
     *
     * @return mixed
     * @throws \Exception
     */
    public function revoke()
    {
        if ($this->session_id) {

            // Destroy session
            Session::getHandler()->destroy($this->session_id);
        }

        // Delete login
        return $this->delete();
    }

    /**
     * Get the prunable model query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function prunable(): \Illuminate\Database\Eloquent\Builder
    {
        // Delete history entries older than storage time to live
        //
        return static::where('created_at', '<=', config('passport.storage_ttl'));
    }
}