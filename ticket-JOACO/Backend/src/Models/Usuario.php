<?php

final class Usuario
{
    public function __construct(
        private PDO $conexion
    ) {
    }

    public function buscarPorId(int $idUsuario): ?array
    {
        $sql = '
            SELECT
                id_usuario,
                cedula,
                usuario,
                rol,
                activo
            FROM usuarios
            WHERE id_usuario = :id_usuario
        ';

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            'id_usuario' => $idUsuario
        ]);

        $usuario = $consulta->fetch();

        return $usuario ?: null;
    }

    public function buscarPorNombreUsuario(string $nombreUsuario): ?array
    {
        /*
         * En este método sí recuperamos password_hash porque será
         * utilizado por AuthService para comprobar la contraseña.
         */

        $sql = '
            SELECT
                id_usuario,
                cedula,
                usuario,
                password_hash,
                rol,
                activo
            FROM usuarios
            WHERE usuario = :usuario
        ';

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            'usuario' => $nombreUsuario
        ]);

        $usuario = $consulta->fetch();

        return $usuario ?: null;
    }

    public function listarDocentes(): array
    {
        $sql = "
            SELECT
                id_usuario,
                cedula,
                usuario,
                rol,
                activo
            FROM usuarios
            WHERE rol = 'docente'
            ORDER BY usuario ASC
        ";

        $consulta = $this->conexion->query($sql);

        return $consulta->fetchAll();
    }

    public function crear(
        string $cedula,
        string $nombreUsuario,
        string $passwordHash,
        string $rol
    ): int {
        $sql = '
            INSERT INTO usuarios (
                cedula,
                usuario,
                password_hash,
                rol,
                activo
            ) VALUES (
                :cedula,
                :usuario,
                :password_hash,
                :rol,
                TRUE
            )
        ';

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            'cedula' => $cedula,
            'usuario' => $nombreUsuario,
            'password_hash' => $passwordHash,
            'rol' => $rol
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    public function cambiarEstadoActivo(
        int $idUsuario,
        bool $activo
    ): bool {
        $sql = '
            UPDATE usuarios
            SET activo = :activo
            WHERE id_usuario = :id_usuario
        ';

        $consulta = $this->conexion->prepare($sql);

        $consulta->bindValue(
            ':activo',
            $activo,
            PDO::PARAM_BOOL
        );

        $consulta->bindValue(
            ':id_usuario',
            $idUsuario,
            PDO::PARAM_INT
        );

        $consulta->execute();

        return $consulta->rowCount() > 0;
    }

    public function existeCedula(string $cedula): bool
    {
        $sql = '
            SELECT COUNT(*)
            FROM usuarios
            WHERE cedula = :cedula
        ';

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            'cedula' => $cedula
        ]);

        return (int) $consulta->fetchColumn() > 0;
    }

    public function existeNombreUsuario(string $nombreUsuario): bool
    {
        $sql = '
            SELECT COUNT(*)
            FROM usuarios
            WHERE usuario = :usuario
        ';

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            'usuario' => $nombreUsuario
        ]);

        return (int) $consulta->fetchColumn() > 0;
    }
}