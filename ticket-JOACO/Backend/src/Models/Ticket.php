<?php


final class Ticket
{
    public function __construct(
        private PDO $conexion
    ) {
    }

    public function listarAbiertosPorUsuario(int $idUsuario): array 
    {
        $sql = '
            SELECT
                t.id_ticket,
                t.descripcion,
                t.fecha,
                t.sala,
                t.id_prioridad,
                p.nombre AS prioridad,
                e.nombre AS estado
            FROM tickets AS t

            INNER JOIN prioridades AS p
                ON p.id_prioridad = t.id_prioridad

            INNER JOIN estados AS e
                ON e.id_estado = t.id_estado

            WHERE t.id_usuario = :id_usuario
              AND e.nombre = :estado

            ORDER BY t.fecha DESC
        ';

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            'id_usuario' => $idUsuario,
            'estado' => 'Abierto'
        ]);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $idTicket): ?array
    {
        $sql = '
            SELECT
                t.id_ticket,
                t.descripcion,
                t.fecha,
                t.sala,
                t.id_usuario,
                t.id_estado,
                t.id_prioridad,
                e.nombre AS estado
            FROM tickets AS t

            INNER JOIN estados AS e
                ON e.id_estado = t.id_estado

            WHERE t.id_ticket = :id_ticket
        ';

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            'id_ticket' => $idTicket
        ]);

        $ticket = $consulta->fetch(PDO::FETCH_ASSOC);

        if ($ticket === false) {
            return null;
        }

        return $ticket;
    }



    public function crear(
        string $descripcion,
        ?string $sala,
        int $idUsuario,
        int $idEstado,
        int $idPrioridad
    ): int {
        $sql = '
            INSERT INTO tickets (
                descripcion,
                sala,
                id_usuario,
                id_estado,
                id_prioridad
            ) VALUES (
                :descripcion,
                :sala,
                :id_usuario,
                :id_estado,
                :id_prioridad
            )
        ';

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            'descripcion' => $descripcion,
            'sala' => $sala,
            'id_usuario' => $idUsuario,
            'id_estado' => $idEstado,
            'id_prioridad' => $idPrioridad
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    public function actualizar(int $idTicket, string $descripcion, ?string $sala, int $idPrioridad
    ): void 
    {
        $sql = '
            UPDATE tickets
            SET
                descripcion = :descripcion,
                sala = :sala,
                id_prioridad = :id_prioridad
            WHERE id_ticket = :id_ticket
        ';

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            'descripcion' => $descripcion,
            'sala' => $sala,
            'id_prioridad' => $idPrioridad,
            'id_ticket' => $idTicket
        ]);
    }

//----------metodos paara encargados--------------

    public function listarTodos(): array
    {
        $sql = '
            SELECT
                t.id_ticket,
                t.descripcion,
                t.fecha,
                t.sala,
                t.id_usuario,
                t.id_estado,
                t.id_prioridad,

                u.usuario AS creador,
                e.nombre AS estado,
                p.nombre AS prioridad

            FROM tickets AS t

            INNER JOIN usuarios AS u
                ON u.id_usuario = t.id_usuario

            INNER JOIN estados AS e
                ON e.id_estado = t.id_estado

            INNER JOIN prioridades AS p
                ON p.id_prioridad = t.id_prioridad

            ORDER BY t.fecha DESC
        ';

        $consulta = $this->conexion->query($sql);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarComoEncargado(
        int $idTicket,
        string $descripcion,
        ?string $sala,
        int $idPrioridad,
        int $idEstado
    ): void {
        $sql = '
            UPDATE tickets
            SET
                descripcion = :descripcion,
                sala = :sala,
                id_prioridad = :id_prioridad,
                id_estado = :id_estado
            WHERE id_ticket = :id_ticket
        ';

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            'descripcion' => $descripcion,
            'sala' => $sala,
            'id_prioridad' => $idPrioridad,
            'id_estado' => $idEstado,
            'id_ticket' => $idTicket
        ]);
    }

}