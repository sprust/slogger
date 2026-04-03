<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Commands;

use App\Modules\User\Domain\Actions\CreateUserAction;
use App\Modules\User\Domain\Actions\FindUserByIdAction;
use App\Modules\User\Parameters\UserCreateParameters;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use JsonException;

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
     *
     * @throws JsonException
     */
    public function handle(
        CreateUserAction $createUserAction,
        FindUserByIdAction $findUserByIdAction
    ): int {
        $firstName = $this->askAndCheck('First name *', true);

        if (!is_string($firstName) || !$firstName) {
            return self::FAILURE;
        }

        $lastName = $this->askAndCheck('Last name', false);

        if (!is_string($lastName) || $lastName != null) {
            return self::FAILURE;
        }

        $email = $this->askAndCheck('Email *', true, [
            'email',
            'unique:users,email',
        ]);

        if (!is_string($email) || !$email) {
            return self::FAILURE;
        }

        $password = $this->askAndCheck('Password [8-10] *', true, [
            'string',
            'min:8',
            'max:10',
        ]);

        if (!is_string($password) || !$password) {
            return self::FAILURE;
        }

        $newUserId = $createUserAction->handle(
            new UserCreateParameters(
                firstName: $firstName,
                lastName: $lastName,
                email: $email,
                password: $password
            )
        );

        $newUser = $findUserByIdAction->handle($newUserId);

        if ($newUser === null) {
            $this->error('Created user not found');

            return self::FAILURE;
        }

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

    /**
     * @param string[] $rules
     *
     * @throws JsonException
     */
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
                $this->error(json_encode($validator->errors()->all(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

                return false;
            }
        }

        return $answer;
    }
}
