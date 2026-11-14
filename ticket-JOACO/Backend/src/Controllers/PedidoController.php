<?php

//B05: controlador de pedidos (reemplaza al de tickets).

final class PedidoController
{
    public function __construct(
        private PedidoService $pedidoService
    ) {
    }

    public function listarAsignados(): void
    {
        $usuario = AuthMiddleware::verificar();

        $pedidos = $this->pedidoService
            ->listarAsignados($usuario['id_usuario']);

        $estados = $this->pedidoService
            ->listarEstados();

        Response::json([
            'pedidos' => $pedidos,
            'estados' => $estados
        ]);
    }

    public function listarTodos(): void
    {
        $pedidos = $this->pedidoService
            ->listarTodos();

        $prioridades = $this->pedidoService
            ->listarPrioridades();

        $estados = $this->pedidoService
            ->listarEstados();

        Response::json([
            'pedidos' => $pedidos,
            'prioridades' => $prioridades,
            'estados' => $estados
        ]);
    }

    public function crear(): void
    {
        $usuario = AuthMiddleware::verificar();
        $datos = $this->leerJson();

        try {
            $idPedido = $this->pedidoService->crear(
                $usuario['id_usuario'],
                $datos['cliente'] ?? '',
                $datos['telefono'] ?? '',
                $datos['direccion'] ?? '',
                $datos['descripcion'] ?? '',
                (int) ($datos['id_prioridad'] ?? 0)
            );

            Response::json([
                'mensaje' => 'Pedido creado correctamente',
                'id_pedido' => $idPedido
            ], 201);
        } catch (RuntimeException $error) {
            $this->responderError($error);
        }
    }

    public function actualizarComoEncargado(): void
    {
        $usuario = AuthMiddleware::verificar();
        $datos = $this->leerJson();

        try {
            $this->pedidoService->actualizarComoEncargado(
                $usuario['id_usuario'],
                (int) ($datos['id_pedido'] ?? 0),
                $datos['cliente'] ?? '',
                $datos['telefono'] ?? '',
                $datos['direccion'] ?? '',
                $datos['descripcion'] ?? '',
                (int) ($datos['id_prioridad'] ?? 0),
                (int) ($datos['id_estado'] ?? 0),
                $this->obtenerRepartidor($datos)
            );

            Response::json([
                'mensaje' => 'Pedido actualizado correctamente'
            ]);
        } catch (RuntimeException $error) {
            $this->responderError($error);
        }
    }

    public function actualizarEstado(): void
    {
        $usuario = AuthMiddleware::verificar();
        $datos = $this->leerJson();

        try {
            $this->pedidoService->actualizarEstadoRepartidor(
                $usuario['id_usuario'],
                (int) ($datos['id_pedido'] ?? 0),
                (int) ($datos['id_estado'] ?? 0)
            );

            Response::json([
                'mensaje' => 'Estado actualizado correctamente'
            ]);
        } catch (RuntimeException $error) {
            $this->responderError($error);
        }
    }

    private function leerJson(): array
    {
        $contenido = file_get_contents('php://input');

        try {
            return json_decode(
                $contenido,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            Response::json([
                'error' => 'El contenido JSON no es válido'
            ], 400);
        }
    }

    private function obtenerRepartidor(array $datos): ?int
    {
        $idRepartidor = $datos['id_repartidor'] ?? null;

        if (
            $idRepartidor === null ||
            $idRepartidor === '' ||
            (int) $idRepartidor <= 0
        ) {
            return null;
        }

        return (int) $idRepartidor;
    }

    private function responderError(
        RuntimeException $error
    ): never {
        $codigo = $error->getCode();

        if (
            !in_array(
                $codigo,
                [403, 404, 409, 422, 500],
                true
            )
        ) {
            $codigo = 500;
        }

        Response::json([
            'error' => $error->getMessage()
        ], $codigo);
    }
}
