<?php

final class TicketController
{
    public function __construct(
        private TicketService $ticketService
    ) {
    }

    public function listar(): void
    {
        $usuario = AuthMiddleware::verificar();

        $tickets = $this->ticketService
            ->listarDelRepartidor($usuario['id_usuario']);

        $prioridades = $this->ticketService
            ->listarPrioridades();

        Response::json([
            'tickets' => $tickets,
            'prioridades' => $prioridades
        ]);
    }

    public function crear(): void
    {
        $usuario = AuthMiddleware::verificar();
        $datos = $this->leerJson();

        try {
            $idTicket = $this->ticketService->crear(
                $usuario['id_usuario'],
                $datos['descripcion'] ?? '',
                $this->obtenerSala($datos),
                (int) ($datos['id_prioridad'] ?? 0)
            );

            Response::json([
                'mensaje' => 'Ticket creado correctamente',
                'id_ticket' => $idTicket
            ], 201);
        } catch (RuntimeException $error) {
            $this->responderError($error);
        }
    }

    public function actualizar(): void
    {
        $usuario = AuthMiddleware::verificar();
        $datos = $this->leerJson();

        try {
            $this->ticketService->actualizar(
                $usuario['id_usuario'],
                (int) ($datos['id_ticket'] ?? 0),
                $datos['descripcion'] ?? '',
                $this->obtenerSala($datos),
                (int) ($datos['id_prioridad'] ?? 0)
            );

            Response::json([
                'mensaje' => 'Ticket actualizado correctamente'
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

    private function obtenerSala(array $datos): ?string
    {
        $sala = $datos['sala'] ?? null;

        if ($sala === '') {
            return null;
        }

        return $sala;
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

    public function listarTodos(): void
{
    Response::json([
        'tickets' => $this->ticketService->listarTodos(),
        'prioridades' => $this->ticketService->listarPrioridades(),
        'estados' => $this->ticketService->listarEstados()
    ]);
}

public function actualizarComoEncargado(): void
{
    $datos = $this->leerJson();

    try {
        $this->ticketService->actualizarComoEncargado(
            (int) ($datos['id_ticket'] ?? 0),
            $datos['descripcion'] ?? '',
            $this->obtenerSala($datos),
            (int) ($datos['id_prioridad'] ?? 0),
            (int) ($datos['id_estado'] ?? 0)
        );

        Response::json([
            'mensaje' => 'Ticket actualizado correctamente'
        ]);
    } catch (RuntimeException $error) {
        $this->responderError($error);
    }
}
}