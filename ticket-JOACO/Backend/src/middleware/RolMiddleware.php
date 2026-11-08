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
        Response::json([
            'error' => 'Esta operación es exclusiva para docentes'
        ], 403);
    }
}
}