<?php

namespace Jurager\Passport\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Session;
use Jurager\Passport\Session\ServerSessionManager;

class History extends Model
{
    use SoftDeletes;

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
     * @return MorphTo
     */
    public function authenticatable(): MorphTo
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
     * @return bool|null
     * @throws \Exception
     */
    public function revoke(): ?bool
    {
        $storage = new ServerSessionManager();

        if ($this->session_id) {

            // Destroy session
            $storage->deleteUserData($this->session_id);
        }

        // Delete login
        return $this->delete();
    }
}