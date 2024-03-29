<?php

namespace Jurager\Passport\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $app_id
 * @property string $secret
 * @property string $crated_at
 * @property string $updated_at
 *
 * @mixin Builder
 */
class Broker extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'secret',
    ];

    public function __construct(array $attributes = [])
    {
        $this->setTable(config('passport.brokers_table_name'));

        parent::__construct($attributes);
    }
}
