<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $host = getenv('REPIT_DB_HOST') ?: '127.0.0.1';
            $port = getenv('REPIT_DB_PORT') ?: '3306';
            $name = getenv('REPIT_DB_NAME') ?: 'repit_sante';
            $user = getenv('REPIT_DB_USER') ?: 'root';
            $password = getenv('REPIT_DB_PASSWORD');

            self::$connection = new PDO(
                "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",
                $user,
                $password === false ? '' : $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        }

        return self::$connection;
    }
}
