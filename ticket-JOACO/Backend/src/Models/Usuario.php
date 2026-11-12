<?php

final class Usuario
{
    public function __construct(
        private PDO $conexion
    ) {
    }

    //B03-corr: consultas adaptadas a la tabla real `usuarios` de Dely:
    //id, nombre, apellido, usuario, password, rol, estado, fecha_creacion.
    //El parámetro interno $idUsuario se conserva hasta B04.

    public function buscarPorId(int $idUsuario): ?array
    {
        $sql = '
            SELECT
                id,
                nombre,
                apellido,
                usuario,
                rol,
                estado,
                fecha_creacion
            FROM usuarios
            WHERE id = :id
        ';

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            'id' => $idUsuario
        ]);

        $usuario = $consulta->fetch();

        return $usuario ?: null;
    }

    public function buscarPorNombreUsuario(string $nombreUsuario): ?array
    {
        /*
         * En este método sí recuperamos password porque será
         * utilizado por AuthService para comprobar la contraseña.
         */

        $sql = '
            SELECT
                id,
                nombre,
                apellido,
                usuario,
                password,
                rol,
                estado
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

    public function listarRepartidores(): array
    {
        $sql = "
            SELECT
                id,
                nombre,
                apellido,
                usuario,
                rol,
                estado
            FROM usuarios
            WHERE rol = 'repartidor'
            ORDER BY usuario ASC
        ";

        $consulta = $this->conexion->query($sql);

        return $consulta->fetchAll();
    }

    public function crear(
        string $nombre,
        string $apellido,
        string $nombreUsuario,
        string $passwordHash,
        string $rol
    ): int {
        $sql = '
            INSERT INTO usuarios (
                nombre,
                apellido,
                usuario,
                password,
                rol,
                estado,
                fecha_creacion
            ) VALUES (
                :nombre,
                :apellido,
                :usuario,
                :password,
                :rol,
                \'activo\',
                NOW()
            )
        ';

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'usuario' => $nombreUsuario,
            'password' => $passwordHash,
            'rol' => $rol
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    public function cambiarEstado(
        int $idUsuario,
        string $estado
    ): bool {
        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            throw new InvalidArgumentException(
                'El estado debe ser activo o inactivo'
            );
        }

        $sql = '
            UPDATE usuarios
            SET estado = :estado
            WHERE id = :id
        ';

        $consulta = $this->conexion->prepare($sql);

        $consulta->bindValue(
            ':estado',
            $estado,
            PDO::PARAM_STR
        );

        $consulta->bindValue(
            ':id',
            $idUsuario,
            PDO::PARAM_INT
        );

        $consulta->execute();

        return $consulta->rowCount() > 0;
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
