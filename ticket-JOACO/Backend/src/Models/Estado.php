<?php
final class Estado
{
    public function __construct(
        private PDO $conexion
    ) {
    }
    //invento mío esta función no sé si la voy a usar
    public function listar(): array
    {
        $sql = '
            SELECT id_estado, nombre
            FROM estados
            ORDER BY nombre ASC
        ';

        $consulta = $this->conexion->query($sql);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }   


public function buscarPorNombre(string $nombre): ?array
{
    $sql = '
        SELECT id_estado, nombre
        FROM estados
        WHERE nombre = :nombre
        LIMIT 1
    ';

    $consulta = $this->conexion->prepare($sql);

    $consulta->execute([
        'nombre' => $nombre
    ]);

    $estado = $consulta->fetch(PDO::FETCH_ASSOC);

    if ($estado === false) {
        return null;
    }

    return $estado;
}

public function buscarPorId(int $idEstado): ?array
{
    $sql = '
        SELECT id_estado, nombre
        FROM estados
        WHERE id_estado = :id_estado
    ';

    $consulta = $this->conexion->prepare($sql);

    $consulta->execute([
        'id_estado' => $idEstado
    ]);

    $estado = $consulta->fetch(PDO::FETCH_ASSOC);

    if ($estado === false) {
        return null;
    }

    return $estado;
}


}