<?php
/**
 * httpdocs/app/Database.php
 * MariaDBへのPDO接続を一元管理し、CMS全体で同じ接続を再利用します。
 */

declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = config_value('db.host');
        $name = config_value('db.name');
        $charset = config_value('db.charset', 'utf8mb4');
        $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";

        self::$pdo = new PDO($dsn, (string)config_value('db.user'), (string)config_value('db.pass'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$pdo;
    }
}
