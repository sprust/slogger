<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Commands;

use App\Modules\User\Contracts\Domain\CreateUserActionInterface;
use App\Modules\User\Parameters\UserCreateParameters;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a user';

    /**
     * Execute the console command.
     */
    public function handle(CreateUserActionInterface $createUserAction): int
    {
        if (($firstName = $this->askAndCheck('First name *', true)) === false) {
            return self::FAILURE;
        }

        $lastName = $this->askAndCheck('Last name', false);

        $email = $this->askAndCheck('Email *', true, [
            'email',
            'unique:users,email',
        ]);

        if ($email === false) {
            return self::FAILURE;
        }

        $password = $this->askAndCheck('Password [8-10] *', true, [
            'string',
            'min:8',
            'max:10',
        ]);

        if ($password === false) {
            return self::FAILURE;
        }

        $newUser = $createUserAction->handle(
            new UserCreateParameters(
                firstName: $firstName,
                lastName: $lastName,
                email: $email,
                password: $password
            )
        );

        $this->table(
            [
                'id',
                'token',
            ],
            [
                [
                    $newUser->id,
                    $newUser->apiToken,
                ],
            ]
        );

        return self::SUCCESS;
    }

    private function askAndCheck(string $title, bool $required, array $rules = []): bool|string|null
    {
        $answer = $this->ask($title);

        if ($required && !$answer) {
            $this->error("$title is required");

            return false;
        }

        if ($rules) {
            $validator = Validator::make(
                [
                    'value' => $answer,
                ],
                [
                    'value' => $rules,
                ]
            );

            if ($validator->fails()) {
                $this->error(json_encode($validator->errors()->all(), JSON_PRETTY_PRINT));

                return false;
            }
        }

        return $answer;
    }
}
