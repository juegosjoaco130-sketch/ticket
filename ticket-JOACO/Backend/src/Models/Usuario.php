
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
                nombre,
                apellido,
                usuario,
                rol,
                activo,
                fecha_creacion
            FROM usuarios
            WHERE id_usuario = :id_usuario
        ';

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(['id_usuario' => $idUsuario]);

        $usuario = $consulta->fetch();

        return $usuario ?: null;
    }

    public function buscarPorNombreUsuario(string $nombreUsuario): ?array
    {
        $sql = '
            SELECT
                id_usuario,
                cedula,
                nombre,
                apellido,
                usuario,
                password_hash,
                rol,
                activo
            FROM usuarios
            WHERE usuario = :usuario
        ';

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(['usuario' => $nombreUsuario]);

        $usuario = $consulta->fetch();

        return $usuario ?: null;
    }

    public function listarRepartidores(): array
    {
        $sql = "
            SELECT
                id_usuario,
                cedula,
                nombre,
                apellido,
                usuario,
                rol,
                activo
            FROM usuarios
            WHERE rol = 'repartidor'
            ORDER BY usuario ASC
        ";

        return $this->conexion->query($sql)->fetchAll();
    }

    public function existeNombreUsuario(string $nombreUsuario): bool
    {
        $sql = '
            SELECT COUNT(*)
            FROM usuarios
            WHERE usuario = :usuario
        ';

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(['usuario' => $nombreUsuario]);

        return (int) $consulta->fetchColumn() > 0;
    }
}
