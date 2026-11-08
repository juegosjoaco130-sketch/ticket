<?php

declare(strict_types=1);

final class AuthMiddleware
{
    public static function verificar(): array
    {
        if (!isset($_SESSION['id_usuario'])) {
            Response::json([
                'error' => 'Debes iniciar sesión'
            ], 401);
        }

        return [
            'id_usuario' => $_SESSION['id_usuario'],
            'usuario' => $_SESSION['usuario'],
            'rol' => $_SESSION['rol']
        ];
    }
}