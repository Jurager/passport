<?php

namespace Jurager\Passport\Traits;

use Illuminate\Support\Str;
use Jurager\Passport\Models\Token;

trait HasTokens
{
    /**
     * @return mixed
     */
    public function tokens(): mixed
    {
        return $this->morphMany(Token::class, 'tokenable');
    }

    /**
     * Create new token for simple authorization
     *
     * @param $name
     * @param $expires
     * @return string
     */
    public function createToken($name, $expires): string
    {
        $this->tokens()->create([
            'name'  => $name,
            'expires_at' => $expires ? now()->addMinutes($expires) : NULL,
            'token' => hash('sha256', $plainTextToken = Str::random(40)),
        ]);

        return $plainTextToken;
    }

    /**
     * Remove user created token
     *
     * @param $token_id
     * @return bool
     */
    public function removeToken($token_id): bool
    {
        return $this->tokens()->where('id', $token_id)->delete();
    }
}