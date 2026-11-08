<?php

final class Prioridad
{
    public function __construct(
        private PDO $conexion
    ) {
    }

   public function buscarPorId(int $idPrioridad): ?array
{
    $sql = '
        SELECT id_prioridad, nombre
        FROM prioridades
        WHERE id_prioridad = :id_prioridad
    ';

    $consulta = $this->conexion->prepare($sql);

    $consulta->execute([
        'id_prioridad' => $idPrioridad
    ]);

    $prioridad = $consulta->fetch(PDO::FETCH_ASSOC);

    if ($prioridad === false) {
        return null;
    }

    return $prioridad;
}

public function listar(): array
{
    $sql = '
        SELECT id_prioridad, nombre
        FROM prioridades
        ORDER BY id_prioridad
    ';

    $consulta = $this->conexion->query($sql);

    return $consulta->fetchAll(PDO::FETCH_ASSOC);
}

    
}