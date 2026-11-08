<?php

final class Conexion
{
    private static ?PDO $conexion = null;

    public static function conectar(): PDO
    {
        if (self::$conexion !== null) {
            return self::$conexion;
        }

        $config = require __DIR__ . '/../../Config/Parametros.php';

        self::validarConfiguracion($config);

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['database']
        );

        self::$conexion = new PDO(
            $dsn,
            $config['username'],
            $config['password']
        );

        return self::$conexion;
    }

    private static function validarConfiguracion(array $config): void
    {
        $camposObligatorios = [
            'host',
            'port',
            'database',
            'username',
            'password'
        ];

        foreach ($camposObligatorios as $campo) {
            if (
                !array_key_exists($campo, $config) ||
                $config[$campo] === false ||
                $config[$campo] === ''
            ) {
                throw new RuntimeException(
                    "Falta configurar el valor: {$campo}"
                );
            }
        }
    }

}