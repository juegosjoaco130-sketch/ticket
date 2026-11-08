<?php



final class TicketService
{
    private array $salasPermitidas = [
        'Salón 5',
        'Salón 6',
        'Salón 7',
        'Adscripción',
        'Dirección',
        'Campus'
    ];

    public function __construct(
        private Ticket $ticketModel,
        private Estado $estadoModel,
        private Prioridad $prioridadModel
    ) {
    }

    public function listarDelDocente(
        int $idUsuario
    ): array {
        return $this->ticketModel
            ->listarAbiertosPorUsuario($idUsuario);
    }

    public function listarPrioridades(): array
    {
        return $this->prioridadModel->listar();
    }

    public function crear(
        int $idUsuario,
        string $descripcion,
        ?string $sala,
        int $idPrioridad
    ): int {
        $this->validarDatos(
            $descripcion,
            $sala,
            $idPrioridad
        );

        $estadoAbierto = $this->estadoModel
            ->buscarPorNombre('Abierto');

        if ($estadoAbierto === null) {
            throw new RuntimeException(
                'El estado Abierto no está registrado',
                500
            );
        }

        return $this->ticketModel->crear(
            trim($descripcion),
            $sala,
            $idUsuario,
            (int) $estadoAbierto['id_estado'],
            $idPrioridad
        );
    }

    public function actualizar(
        int $idUsuario,
        int $idTicket,
        string $descripcion,
        ?string $sala,
        int $idPrioridad
    ): void {
        $ticket = $this->ticketModel
            ->buscarPorId($idTicket);

        if ($ticket === null) {
            throw new RuntimeException(
                'El ticket no existe',
                404
            );
        }

        if ((int) $ticket['id_usuario'] !== $idUsuario) {
            throw new RuntimeException(
                'No puedes modificar un ticket ajeno',
                403
            );
        }

        if (strcasecmp($ticket['estado'], 'Abierto') !== 0) {
            throw new RuntimeException(
                'El ticket ya no puede modificarse',
                409
            );
        }

        $this->validarDatos(
            $descripcion,
            $sala,
            $idPrioridad
        );

        $this->ticketModel->actualizar(
            $idTicket,
            trim($descripcion),
            $sala,
            $idPrioridad
        );
    }

    private function validarDatos(
        string $descripcion,
        ?string $sala,
        int $idPrioridad
    ): void {
        if (trim($descripcion) === '') {
            throw new RuntimeException(
                'La descripción es obligatoria',
                422
            );
        }

        if (
            $sala !== null &&
            !in_array($sala, $this->salasPermitidas, true)
        ) {
            throw new RuntimeException(
                'La sala seleccionada no es válida',
                422
            );
        }

        if (
            $this->prioridadModel
                ->buscarPorId($idPrioridad) === null
        ) {
            throw new RuntimeException(
                'La prioridad seleccionada no existe',
                422
            );
        }
    }



//-------------------------------------

//comienzan la funciones de encargado

//--------------------------------------



public function listarTodos(): array
{
    return $this->ticketModel->listarTodos();
}

public function listarEstados(): array
{
    return $this->estadoModel->listar();
}



public function actualizarComoEncargado(
    int $idTicket,
    string $descripcion,
    ?string $sala,
    int $idPrioridad,
    int $idEstado
): void {
    $ticket = $this->ticketModel
        ->buscarPorId($idTicket);

    if ($ticket === null) {
        throw new RuntimeException(
            'El ticket no existe',
            404
        );
    }

    $estadoActual = strtolower(
        trim($ticket['estado'])
    );

    if (
        $estadoActual !== 'abierto' &&
        $estadoActual !== 'en proceso'
    ) {
        throw new RuntimeException(
            'Este ticket ya no puede modificarse',
            409
        );
    }

    $this->validarDatos(
        $descripcion,
        $sala,
        $idPrioridad
    );

    $estadoNuevo = $this->estadoModel
        ->buscarPorId($idEstado);

    if ($estadoNuevo === null) {
        throw new RuntimeException(
            'El estado seleccionado no existe',
            422
        );
    }

    $this->ticketModel->actualizarComoEncargado(
        $idTicket,
        trim($descripcion),
        $sala,
        $idPrioridad,
        $idEstado
    );
}



}