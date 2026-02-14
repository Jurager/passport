<?php

namespace Jurager\Passport\Models;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Session;
use Jurager\Passport\Session\ServerSessionManager;

/**
 * @property int $id
 * @property int $authenticatable_id
 * @property string $authenticatable_type
 * @property string $tokenable_type
 * @property string $user_agent
 * @property string $ip
 * @property string $device_type
 * @property string $device
 * @property string $platform
 * @property string $browser
 * @property string $city
 * @property string $region
 * @property string $country
 * @property string $remember_token
 * @property string $expires_at
 * @property string $session_id
 * @property string $crated_at
 * @property string $updated_at
 * @property string $deleted_at
 *
 * @mixin Builder
 */
class History extends Model
{
    use Prunable;
    use SoftDeletes;

    /**
     * The attributes that should be mass fillable.
     *
     * @var array
     */
    protected $fillable = [
        'user_agent',
        'ip',
        'device_type',
        'device',
        'platform',
        'browser',
        'city',
        'region',
        'country',
        'remember_token',
        'expires_at',
        'session_id',
    ];

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
        'expires_at',
        'deleted_at',
    ];

    public function __construct(array $attributes = [])
    {
        $this->setTable(config('passport.history_table_name'));

        parent::__construct($attributes);
    }

    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Add "location" attribute.
     */
    public function getLocationAttribute(): ?string
    {
        $location = [$this->city, $this->region, $this->country];

        return array_filter($location) ? implode(', ', $location) : null;
    }

    /**
     * Add the "is_current" attribute.
     */
    public function getIsCurrentAttribute(): bool
    {
        // Check the session is current
        return $this->session_id === Session::getId();
    }

    /**
     * Revoke the login.
     *
     * @throws Exception
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

    /**
     * Get the prunable model query.
     *
     * @return Builder
     */
    public function prunable(): Builder
    {
        return $this->where('expires_at', '<=', now());
    }
}
