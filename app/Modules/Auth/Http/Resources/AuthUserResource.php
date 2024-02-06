<?php

namespace App\Modules\Auth\Http\Resources;

use App\Http\Resources\AbstractApiResource;
use App\Models\Users\User;

class AuthUserResource extends AbstractApiResource
{
    private int $id;
    private string $first_name;
    private ?string $last_name;
    private string $email;
    private string $api_token;

    public function __construct(User $user)
    {
        parent::__construct($user);

        $this->id         = $user->id;
        $this->first_name = $user->first_name;
        $this->last_name  = $user->last_name;
        $this->email      = $user->email;
        $this->api_token  = $user->api_token;
    }
}
