<?php

namespace App\Modules\Auth\Http\Resources;

use App\Modules\Auth\Dto\Objects\LoggedUserObject;
use App\Modules\Common\Http\Resources\AbstractApiResource;

class LoggedUserResource extends AbstractApiResource
{
    private int $id;
    private string $first_name;
    private ?string $last_name;
    private string $email;
    private string $api_token;

    public function __construct(LoggedUserObject $loggedUser)
    {
        parent::__construct($loggedUser);

        $this->id         = $loggedUser->id;
        $this->first_name = $loggedUser->firstName;
        $this->last_name  = $loggedUser->lastName;
        $this->email      = $loggedUser->email;
        $this->api_token  = $loggedUser->apiToken;
    }
}
