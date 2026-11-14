<?php



//B05: modelo adaptado a la tabla real `pedidos` de database/init.sql.
//La fecha y fecha_actualizacion las gestiona MySQL (DEFAULT/ON UPDATE).

final class Pedido
{
    public function __construct(
        private PDO $conexion
    ) {
    }

    public function listarPorRepartidor(int $idRepartidor): array
    {
        $sql = '
            SELECT
                p.id_pedido,
                p.cliente,
                p.telefono,
                p.direccion,
                p.descripcion,
                p.fecha,
                p.fecha_actualizacion,
                p.id_prioridad,
                pri.nombre AS prioridad,
                p.id_estado,
                e.nombre AS estado,
                p.id_repartidor,
                CONCAT(rep.nombre, \' \', rep.apellido) AS repartidor,
                rep.usuario AS repartidor_usuario,
                p.id_encargado,
                CONCAT(enc.nombre, \' \', enc.apellido) AS encargado
            FROM pedidos AS p

            INNER JOIN prioridades AS pri
                ON pri.id_prioridad = p.id_prioridad

            INNER JOIN estados AS e
                ON e.id_estado = p.id_estado

            INNER JOIN usuarios AS rep
                ON rep.id_usuario = p.id_repartidor

            INNER JOIN usuarios AS enc
                ON enc.id_usuario = p.id_encargado

            WHERE p.id_repartidor = :id_repartidor

            ORDER BY p.fecha DESC
        ';

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            'id_repartidor' => $idRepartidor
        ]);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarTodos(): array
    {
        $sql = '
            SELECT
                p.id_pedido,
                p.cliente,
                p.telefono,
                p.direccion,
                p.descripcion,
                p.fecha,
                p.fecha_actualizacion,
                p.id_prioridad,
                pri.nombre AS prioridad,
                p.id_estado,
                e.nombre AS estado,
                p.id_repartidor,
                CONCAT(rep.nombre, \' \', rep.apellido) AS repartidor,
                rep.usuario AS repartidor_usuario,
                p.id_encargado,
                CONCAT(enc.nombre, \' \', enc.apellido) AS encargado
            FROM pedidos AS p

            INNER JOIN prioridades AS pri
                ON pri.id_prioridad = p.id_prioridad

            INNER JOIN estados AS e
                ON e.id_estado = p.id_estado

            LEFT JOIN usuarios AS rep
                ON rep.id_usuario = p.id_repartidor

            INNER JOIN usuarios AS enc
                ON enc.id_usuario = p.id_encargado

            ORDER BY p.fecha DESC
        ';

        $consulta = $this->conexion->query($sql);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $idPedido): ?array
    {
        $sql = '
            SELECT
                p.id_pedido,
                p.cliente,
                p.telefono,
                p.direccion,
                p.descripcion,
                p.fecha,
                p.fecha_actualizacion,
                p.id_encargado,
                p.id_repartidor,
                p.id_estado,
                p.id_prioridad,
                e.nombre AS estado
            FROM pedidos AS p

            INNER JOIN estados AS e
                ON e.id_estado = p.id_estado

            WHERE p.id_pedido = :id_pedido
        ';

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            'id_pedido' => $idPedido
        ]);

        $pedido = $consulta->fetch(PDO::FETCH_ASSOC);

        if ($pedido === false) {
            return null;
        }

        return $pedido;
    }

    public function crear(
        string $cliente,
        string $telefono,
        string $direccion,
        string $descripcion,
        int $idEncargado,
        int $idEstado,
        int $idPrioridad
    ): int {
        $sql = '
            INSERT INTO pedidos (
                cliente,
                telefono,
                direccion,
                descripcion,
                id_encargado,
                id_estado,
                id_prioridad
            ) VALUES (
                :cliente,
                :telefono,
                :direccion,
                :descripcion,
                :id_encargado,
                :id_estado,
                :id_prioridad
            )
        ';

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            'cliente' => $cliente,
            'telefono' => $telefono,
            'direccion' => $direccion,
            'descripcion' => $descripcion,
            'id_encargado' => $idEncargado,
            'id_estado' => $idEstado,
            'id_prioridad' => $idPrioridad
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    public function actualizarComoEncargado(
        int $idPedido,
        string $cliente,
        string $telefono,
        string $direccion,
        string $descripcion,
        int $idPrioridad,
        int $idEstado,
        ?int $idRepartidor,
        bool $cambioEstado,
        int $idUsuarioSesion
    ): void {
        $this->conexion->beginTransaction();

        try {
            $sql = '
                UPDATE pedidos
                SET
                    cliente = :cliente,
                    telefono = :telefono,
                    direccion = :direccion,
                    descripcion = :descripcion,
                    id_prioridad = :id_prioridad,
                    id_estado = :id_estado,
                    id_repartidor = :id_repartidor
                WHERE id_pedido = :id_pedido
            ';

            $consulta = $this->conexion->prepare($sql);

            $consulta->execute([
                'cliente' => $cliente,
                'telefono' => $telefono,
                'direccion' => $direccion,
                'descripcion' => $descripcion,
                'id_prioridad' => $idPrioridad,
                'id_estado' => $idEstado,
                'id_repartidor' => $idRepartidor,
                'id_pedido' => $idPedido
            ]);

            if ($cambioEstado) {
                $this->insertarHistorial(
                    $idPedido,
                    $idEstado,
                    $idUsuarioSesion
                );
            }

            $this->conexion->commit();
        } catch (Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $error;
        }
    }

    public function actualizarEstado(
        int $idPedido,
        int $idEstado,
        int $idUsuarioSesion,
        bool $cambioEstado
    ): void {
        $this->conexion->beginTransaction();

        try {
            $sql = '
                UPDATE pedidos
                SET id_estado = :id_estado
                WHERE id_pedido = :id_pedido
            ';

            $consulta = $this->conexion->prepare($sql);

            $consulta->execute([
                'id_estado' => $idEstado,
                'id_pedido' => $idPedido
            ]);

            if ($cambioEstado) {
                $this->insertarHistorial(
                    $idPedido,
                    $idEstado,
                    $idUsuarioSesion
                );
            }

            $this->conexion->commit();
        } catch (Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $error;
        }
    }

    private function insertarHistorial(
        int $idPedido,
        int $idEstado,
        int $idUsuario
    ): void {
        $sql = '
            INSERT INTO historial_pedidos (
                id_pedido,
                id_estado,
                id_usuario
            ) VALUES (
                :id_pedido,
                :id_estado,
                :id_usuario
            )
        ';

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            'id_pedido' => $idPedido,
            'id_estado' => $idEstado,
            'id_usuario' => $idUsuario
        ]);
    }
}
