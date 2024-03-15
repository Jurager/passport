<?php

namespace Jurager\Passport\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $tokenable_id
 * @property string $tokenable_type
 * @property string $name
 * @property string $token
 * @property string $last_used_at
 * @property string $expires_at
 * @property string $crated_at
 * @property string $updated_at
 * @property string $deleted_at
 *
 * @mixin Builder
 */
class Token extends Model
{
    use Prunable, SoftDeletes;

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
    protected $fillable = ['name', 'token', 'last_used_at', 'expires_at'];

    public function __construct(array $attributes = [])
    {
        $this->setTable(config('passport.tokens_table_name'));

        parent::__construct($attributes);
    }

    /**
     * Get the tokenable model that the access token belongs to.
     */
    public function tokenable(): MorphTo
    {
        return $this->morphTo('tokenable');
    }

    /**
     * Get the prunable model query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function prunable()
    {
        return $this->where('expires_at', '<=', now());
    }
}
