<?php

namespace Tests\Packages\Sconcur\Database;

use PHPUnit\Framework\TestCase;
use SConcur\Laravel\Database\Mysql\Dsn;

/**
 * The go-sql-driver DSN is where everything the PDO connector would have done in
 * SET statements after connecting has to be said instead, so what ends up in the
 * query string is the whole of the connection's behaviour.
 */
class DsnTest extends TestCase
{
    public function testCredentialsAndAddressComeFromTheUsualConfigKeys(): void
    {
        $dsn = Dsn::build([
            'host'     => 'sl-mysql',
            'port'     => 3306,
            'database' => 'slogger',
            'username' => 'root',
            'password' => 'secret',
        ]);

        $this->assertSame('root:secret@tcp(sl-mysql:3306)/slogger', $dsn);
    }

    /**
     * A password is not a place to demand the operator avoid punctuation: it goes
     * through the same encoding as everything else.
     */
    public function testAPasswordWithReservedCharactersIsEncoded(): void
    {
        $dsn = Dsn::build([
            'host'     => 'db',
            'port'     => 3306,
            'database' => 'app',
            'username' => 'user',
            'password' => 'p@ss:w/rd?',
        ]);

        $this->assertStringStartsWith('user:p%40ss%3Aw%2Frd%3F@tcp(db:3306)/app', $dsn);
    }

    public function testAUnixSocketReplacesTheTcpAddress(): void
    {
        $dsn = Dsn::build([
            'unix_socket' => '/var/run/mysqld/mysqld.sock',
            'host'        => 'ignored',
            'port'        => 3306,
            'database'    => 'app',
            'username'    => 'user',
            'password'    => '',
        ]);

        $this->assertSame('user:@unix(/var/run/mysqld/mysqld.sock)/app', $dsn);
    }

    /**
     * charset and collation are parameters the driver knows: handleParams() turns
     * them into SET NAMES ... COLLATE ..., which is what the PDO connector runs.
     */
    public function testCharsetAndCollationTravelAsDriverParameters(): void
    {
        $dsn = Dsn::build([
            'host'      => 'db',
            'port'      => 3306,
            'database'  => 'app',
            'username'  => 'user',
            'password'  => '',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        $this->assertStringContainsString('charset=utf8mb4', $dsn);
        $this->assertStringContainsString('collation=utf8mb4_unicode_ci', $dsn);
    }

    /**
     * The value reaches the server through `SET sql_mode = <value>`, so the quotes
     * are part of it — and the whole thing is url-encoded because the driver
     * decodes it on the way in.
     */
    public function testStrictModeCarriesLaravelsOwnSqlMode(): void
    {
        $dsn = Dsn::build([
            'host'     => 'db',
            'port'     => 3306,
            'database' => 'app',
            'username' => 'user',
            'password' => '',
            'strict'   => true,
        ]);

        $this->assertStringContainsString(
            'sql_mode=' . rawurlencode(
                "'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE"
                . ",ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'"
            ),
            $dsn
        );
    }

    public function testNonStrictModeOnlyKeepsTheEngineSubstitutionMode(): void
    {
        $dsn = Dsn::build([
            'host'     => 'db',
            'port'     => 3306,
            'database' => 'app',
            'username' => 'user',
            'password' => '',
            'strict'   => false,
        ]);

        $this->assertStringContainsString('sql_mode=' . rawurlencode("'NO_ENGINE_SUBSTITUTION'"), $dsn);
    }

    public function testExplicitModesWinOverStrict(): void
    {
        $dsn = Dsn::build([
            'host'     => 'db',
            'port'     => 3306,
            'database' => 'app',
            'username' => 'user',
            'password' => '',
            'strict'   => true,
            'modes'    => ['NO_ENGINE_SUBSTITUTION', 'ONLY_FULL_GROUP_BY'],
        ]);

        $this->assertStringContainsString(
            'sql_mode=' . rawurlencode("'NO_ENGINE_SUBSTITUTION,ONLY_FULL_GROUP_BY'"),
            $dsn
        );
    }

    public function testNeitherStrictNorModesLeavesTheServerDefaultAlone(): void
    {
        $dsn = Dsn::build([
            'host'     => 'db',
            'port'     => 3306,
            'database' => 'app',
            'username' => 'user',
            'password' => '',
        ]);

        $this->assertStringNotContainsString('sql_mode', $dsn);
    }

    /**
     * url.QueryUnescape reads a raw `+` as a space, which would turn `+00:00` into
     * ` 00:00` and make the SET fail. rawurlencode escapes it.
     */
    public function testATimezoneOffsetIsEncodedSoThePlusSurvives(): void
    {
        $dsn = Dsn::build([
            'host'     => 'db',
            'port'     => 3306,
            'database' => 'app',
            'username' => 'user',
            'password' => '',
            'timezone' => '+00:00',
        ]);

        $this->assertStringContainsString('time_zone=' . rawurlencode("'+00:00'"), $dsn);
        $this->assertStringNotContainsString('time_zone=%27+00', $dsn);
    }

    /**
     * With parseTime the driver returns dates RFC3339, and Eloquent only parses
     * those through its fallback path; without it they arrive in the format
     * Model::getDateFormat() names.
     */
    public function testParseTimeIsNotEnabled(): void
    {
        $dsn = Dsn::build([
            'host'      => 'db',
            'port'      => 3306,
            'database'  => 'app',
            'username'  => 'user',
            'password'  => '',
            'charset'   => 'utf8mb4',
            'strict'    => true,
            'timezone'  => 'UTC',
        ]);

        $this->assertStringNotContainsString('parseTime', $dsn);
    }

    public function testDsnParamsReachTheDriverVerbatim(): void
    {
        $dsn = Dsn::build([
            'host'       => 'db',
            'port'       => 3306,
            'database'   => 'app',
            'username'   => 'user',
            'password'   => '',
            'dsn_params' => ['readTimeout' => '30s', 'tls' => 'skip-verify'],
        ]);

        $this->assertStringContainsString('readTimeout=30s', $dsn);
        $this->assertStringContainsString('tls=skip-verify', $dsn);
    }
}
