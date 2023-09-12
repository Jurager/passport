<?php

namespace Jurager\Passport\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property int $region_id
 * @property bool $request
 * @property float $balance
 * @property float $percent
 * @property bool $mode
 * @property string $referral
 * @property string $crated_at
 * @property string $updated_at
 * @property int $active_count
 *
 * @mixin Builder
 */
class Token extends Model
{

    use SoftDeletes;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 'name', 'token', 'last_used_at', 'expires_at' ];

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setTable(config('passport.tokens_table_name'));

        parent::__construct($attributes);
    }

    /**
     * Get the tokenable model that the access token belongs to.
     *
     * @return MorphTo
     */
    public function tokenable()
    {
        return $this->morphTo('tokenable');
    }
}
