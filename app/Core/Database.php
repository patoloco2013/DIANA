<?php
declare(strict_types=1);

namespace Diana\Core;

use PDO;

/**
 * Conexión PDO única. Siempre con consultas preparadas —
 * sustituye los mysqli_query con variables interpoladas del SIE.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                cfg('db.host'),
                (int) cfg('db.puerto', 3306),
                cfg('db.nombre'),
                cfg('db.charset', 'utf8mb4')
            );
            self::$pdo = new PDO($dsn, cfg('db.usuario'), cfg('db.password'), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$pdo;
    }

    /** SELECT de varias filas. */
    public static function todas(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** SELECT de una fila (o null). */
    public static function una(string $sql, array $params = []): ?array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $fila = $stmt->fetch();
        return $fila === false ? null : $fila;
    }

    /** SELECT de un valor escalar. */
    public static function valor(string $sql, array $params = []): mixed
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /** INSERT/UPDATE/DELETE. Devuelve filas afectadas. */
    public static function ejecutar(string $sql, array $params = []): int
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function ultimoId(): int
    {
        return (int) self::pdo()->lastInsertId();
    }
}
