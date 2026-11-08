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

        $nombreUsuario = $datos['usuario'] ?? '';
        $password = $datos['password'] ?? '';

        //Si algún campo llega con un tipo inesperado (arreglo u objeto),
        //se rechaza con 422 en lugar de provocar un error interno.
        if (!is_string($nombreUsuario) || !is_string($password)) {
            Response::json([
                'error' => 'Los datos enviados no son válidos'
            ], 422);
        }

        $nombreUsuario = trim($nombreUsuario);

        //La contraseña se verifica tal cual fue escrita; trim() solo se
        //usa para detectar un campo con únicamente espacios.
        if ($nombreUsuario === '' || trim($password) === '') {
            Response::json([
                'error' => 'Debes completar usuario y contraseña'
            ], 422);
        }

        //Valores imposibles según el esquema (usuario de hasta 50
        //caracteres): se responden igual que unas credenciales
        //incorrectas para no revelar información sobre las cuentas.
        if (strlen($nombreUsuario) > 50 || strlen($password) > 255) {
            Response::json([
                'error' => 'Usuario o contraseña incorrectos'
            ], 401);
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