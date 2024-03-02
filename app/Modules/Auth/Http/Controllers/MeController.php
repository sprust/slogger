<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Http\Resources\UserResource;
use Illuminate\Http\Request;

readonly class MeController
{
    public function __invoke(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
