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
    //B05: rol limitado definitivo: repartidor.
    public static function soloRepartidor(): void
{
    $usuario = AuthMiddleware::verificar();

    if ($usuario['rol'] !== 'repartidor') {
        Response::json([
            'error' => 'Esta operación es exclusiva para repartidores'
        ], 403);
    }
}
}