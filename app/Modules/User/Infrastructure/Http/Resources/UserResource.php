<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\User\Entities\UserObject;

class UserResource extends AbstractApiResource
{
    private int $id;
    private string $first_name;
    private ?string $last_name;
    private string $email;
    private string $created_at;
    private string $updated_at;

    public function __construct(UserObject $user)
    {
        parent::__construct($user);

        $this->id         = $user->id;
        $this->first_name = $user->firstName;
        $this->last_name  = $user->lastName;
        $this->email      = $this->maskEmail($user->email);
        $this->created_at = $user->createdAt->toDateTimeString();
        $this->updated_at = $user->updatedAt->toDateTimeString();
    }

    private function maskEmail(string $email): string
    {
        $atPosition = strrpos($email, '@');

        $local  = $atPosition === false ? $email : substr($email, 0, $atPosition);
        $domain = $atPosition === false ? '' : substr($email, $atPosition);

        $visibleLength = mb_strlen($local) > 2 ? 2 : 1;

        return mb_substr($local, 0, $visibleLength) . '****' . $domain;
    }
}
