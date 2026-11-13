<?php
declare(strict_types=1);

final class AuthService
{
    public function __construct(
        private Usuario $usuarioModel
    ) {
    }

    public function iniciarSesion(
        string $nombreUsuario,
        string $password
    ): ?array {
        $usuario = $this->usuarioModel
            ->buscarPorNombreUsuario($nombreUsuario);

        if ($usuario === null) {
            return null;
        }

        if ((int) $usuario['activo'] !== 1) {
            return null;
        }

        if (!password_verify(
            $password,
            $usuario['password_hash']
        )) {
            return null;
        }

        session_regenerate_id(true);

        $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
        $_SESSION['usuario'] = $usuario['usuario'];
        $_SESSION['rol'] = $usuario['rol'];

        return [
            'id_usuario' => (int) $usuario['id_usuario'],
            'usuario' => $usuario['usuario'],
            'rol' => $usuario['rol']
        ];
    }

    public function obtenerSesion(): ?array
    {
        if (!isset($_SESSION['id_usuario'])) {
            return null;
        }

        return [
            'id_usuario' => $_SESSION['id_usuario'],
            'usuario' => $_SESSION['usuario'],
            'rol' => $_SESSION['rol']
        ];
    }

    public function cerrarSesion(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $parametros = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $parametros['path'],
                $parametros['domain'],
                $parametros['secure'],
                $parametros['httponly']
            );
        }

        session_destroy();
    }
}
