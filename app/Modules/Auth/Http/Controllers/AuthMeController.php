<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Http\Resources\AuthUserResource;
use Illuminate\Http\Request;

readonly class AuthMeController
{
    public function __invoke(Request $request): AuthUserResource
    {
        return new AuthUserResource($request->user());
    }
}
