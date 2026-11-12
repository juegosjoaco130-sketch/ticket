<?php

final class Conexion
{
    private static ?PDO $conexion = null;

    public static function conectar(): PDO
    {
        if (self::$conexion !== null) {
            return self::$conexion;
        }

        $config = require __DIR__ . '/../../config/Parametros.php';

        self::validarConfiguracion($config);

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['database']
        );

        //B03: modos PDO explícitos. Sin esto, los errores de consulta
        //fallaban en silencio (ERRMODE_SILENT) y las filas llegaban en
        //formato dual (FETCH_BOTH). Con excepciones, index.php los
        //convierte en 500 genérico + error_log, sin exponer detalles.
        self::$conexion = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
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