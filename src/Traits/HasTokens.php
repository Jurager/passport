<?php

namespace Jurager\Passport\Traits;

use Illuminate\Support\Str;
use Jurager\Passport\Models\Token;

trait HasTokens
{
    public function tokens(): mixed
    {
        return $this->morphMany(Token::class, 'tokenable');
    }

    /**
     * Create new token for simple authorization
     */
    public function createToken($name, $expires): string
    {
        $this->tokens()->create([
            'name' => $name,
            'expires_at' => $expires ? now()->addMinutes($expires) : null,
            'token' => hash('sha256', $plainTextToken = Str::random(40)),
        ]);

        return $plainTextToken;
    }

    /**
     * Remove user created token
     */
    public function removeToken($token_id): bool
    {
        return $this->tokens()->where('id', $token_id)->delete();
    }
}
