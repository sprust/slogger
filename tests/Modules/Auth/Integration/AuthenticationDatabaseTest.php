<?php

declare(strict_types=1);

namespace Tests\Modules\Auth\Integration;

use Tests\Modules\BaseModuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Modules\Auth\Domain\UseCases\LoginByAdminUseCase;
use App\Modules\Auth\Domain\Exceptions\AuthenticationFailedException;
use App\Modules\Auth\Parameters\LoginParameters;
use App\Modules\User\Repositories\UserTokenRepository;
use Tests\Factories\Entities\UserFactory;
use Illuminate\Support\Carbon;

class AuthenticationDatabaseTest extends BaseModuleTestCase
{
    #[DataProvider('dataProvider')]
    public function test(
        bool $validPassword
    ): void {
        $userTokenRepository = $this->appMake(UserTokenRepository::class);
        $loginByAdminUseCase = $this->appMake(LoginByAdminUseCase::class);
        $userFactory         = $this->appMake(UserFactory::class);

        $email    = fake()->uuid() . '@example.test';
        $password = fake()->password();
        $userId   = $userFactory->create(
            email: $email,
            password: $password,
        );

        if (!$validPassword) {
            $this->expectException(AuthenticationFailedException::class);
        }

        $loggedUser = $loginByAdminUseCase->handle(
            new LoginParameters(
                email: $email,
                password: $validPassword ? $password : $password . '-invalid',
            )
        );

        self::assertSame(
            expected: $userId,
            actual: $loggedUser->id
        );
        self::assertSame(
            expected: $userId,
            actual: $userTokenRepository->findUserIdByToken(
                token: $loggedUser->apiToken,
                now: Carbon::now(),
            )
        );
    }

    /** @return array<string, array{bool}> */
    public static function dataProvider(): array
    {
        return [
            'valid password creates a persisted session' => [true],
            'invalid password is rejected'               => [false],
        ];
    }
}
