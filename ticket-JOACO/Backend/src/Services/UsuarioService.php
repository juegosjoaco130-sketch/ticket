<?php


final class UsuarioService
{
    public function __construct(
        private Usuario $usuarioModel
    ) {
    }

    public function listarRepartidoresActivos(): array
    {
        return $this->usuarioModel->listarRepartidoresActivos();
    }

    public function crear(
        string $cedula,
        string $nombre,
        string $apellido,
        string $nombreUsuario,
        string $password,
        string $rol
    ): array {
        //B05: esquema canónico database/init.sql (cédula obligatoria).
        $cedula = trim($cedula);
        $nombre = trim($nombre);
        $apellido = trim($apellido);
        $nombreUsuario = trim($nombreUsuario);
        $rol = trim($rol);

        if (!preg_match('/^[0-9]{8}$/', $cedula)) {
            throw new RuntimeException(
                'La cédula debe contener exactamente 8 números',
                422
            );
        }

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

        if ($this->usuarioModel->existeCedula($cedula)) {
            throw new RuntimeException(
                'Ya existe un usuario con esa cédula',
                409
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
            $cedula,
            $nombre,
            $apellido,
            $nombreUsuario,
            $passwordHash,
            $rol
        );

        return [
            'id_usuario' => $idUsuario,
            'cedula' => $cedula,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'usuario' => $nombreUsuario,
            'rol' => $rol,
            'activo' => true
        ];
    }
}