<?php


final class UsuarioService
{
    public function __construct(
        private Usuario $usuarioModel
    ) {
    }

    public function crear(
        string $nombre,
        string $apellido,
        string $nombreUsuario,
        string $password,
        string $rol
    ): array {
        //B03-corr: la tabla real usa nombre/apellido (no existe cedula).
        $nombre = trim($nombre);
        $apellido = trim($apellido);
        $nombreUsuario = trim($nombreUsuario);
        $rol = trim($rol);

        if ($nombre === '' || $apellido === '') {
            throw new RuntimeException(
                'El nombre y el apellido son obligatorios',
                422
            );
        }

        if (
            strlen($nombreUsuario) < 3 ||
            strlen($nombreUsuario) > 50
        ) {
            throw new RuntimeException(
                'El usuario debe tener entre 3 y 50 caracteres',
                422
            );
        }

        if (strlen($password) < 8) {
            throw new RuntimeException(
                'La contraseña debe tener al menos 8 caracteres',
                422
            );
        }

        if (!in_array($rol, ['repartidor', 'encargado'], true)) {
            throw new RuntimeException(
                'El rol seleccionado no es válido',
                422
            );
        }

        if (
            $this->usuarioModel
                ->existeNombreUsuario($nombreUsuario)
        ) {
            throw new RuntimeException(
                'El nombre de usuario ya está registrado',
                409
            );
        }

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $idUsuario = $this->usuarioModel->crear(
            $nombre,
            $apellido,
            $nombreUsuario,
            $passwordHash,
            $rol
        );

        return [
            'id_usuario' => $idUsuario,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'usuario' => $nombreUsuario,
            'rol' => $rol,
            'estado' => 'activo'
        ];
    }
}