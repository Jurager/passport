<?php

namespace Jurager\Passport\Test\Models;

use Illuminate\Database\Eloquent\Model;

class App extends Model
{
    protected $fillable = ['app_id', 'secret'];
}
