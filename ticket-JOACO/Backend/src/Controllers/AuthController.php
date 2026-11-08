<?php


final class AuthController
{
    public function __construct(
        private AuthService $authService
    ) {
    }

    public function login(): void
    {
        $contenido = file_get_contents('php://input');

        try {
            $datos = json_decode(
                $contenido,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            Response::json([
                'error' => 'El contenido JSON no es válido'
            ], 400);
        }

        $nombreUsuario = trim($datos['usuario'] ?? '');
        $password = $datos['password'] ?? '';

        if ($nombreUsuario === '' || $password === '') {
            Response::json([
                'error' => 'Debes completar usuario y contraseña'
            ], 422);
        }

        $usuario = $this->authService->iniciarSesion(
            $nombreUsuario,
            $password
        );

        if ($usuario === null) {
            Response::json([
                'error' => 'Usuario o contraseña incorrectos'
            ], 401);
        }

        Response::json([
            'mensaje' => 'Inicio de sesión correcto',
            'usuario' => $usuario
        ]);
    }

    public function sesion(): void
    {
        $usuario = $this->authService->obtenerSesion();

        if ($usuario === null) {
            Response::json([
                'error' => 'No existe una sesión activa'
            ], 401);
        }

        Response::json([
            'usuario' => $usuario
        ]);
    }

    public function logout(): void
    {
        $this->authService->cerrarSesion();

        Response::json([
            'mensaje' => 'Sesión cerrada correctamente'
        ]);
    }
}