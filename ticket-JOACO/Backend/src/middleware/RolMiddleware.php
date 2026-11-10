<?php

final class RolMiddleware
{
    public static function soloEncargado(): void
    {
        $usuario = AuthMiddleware::verificar();

        if ($usuario['rol'] !== 'encargado') {
            Response::json([
                'error' => 'No tienes permisos para realizar esta operación'
            ], 403);
        }
    }
    public static function soloDocente(): void
{
    $usuario = AuthMiddleware::verificar();

    if ($usuario['rol'] !== 'docente') {
        //B02: mensaje adaptado a Dely. El valor comparado ('docente') se
        //mantiene por el ENUM actual de la BD; se renombrará en la etapa
        //de base de datos.
        Response::json([
            'error' => 'Esta operación es exclusiva para repartidores'
        ], 403);
    }
}
}