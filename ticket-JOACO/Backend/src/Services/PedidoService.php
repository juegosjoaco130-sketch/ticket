<?php



//B05: reglas de negocio de pedidos sobre database/init.sql.
//Largos máximos tomados de las columnas reales (VARCHAR 100/20/255).

final class PedidoService
{
    private const ESTADO_INICIAL = 'En preparación';
    private const ESTADO_EN_CAMINO = 'En camino';
    private const ESTADO_ENTREGADO = 'Entregado';

    private const MAX_CLIENTE = 100;
    private const MAX_TELEFONO = 20;
    private const MAX_DIRECCION = 255;

    public function __construct(
        private Pedido $pedidoModel,
        private Estado $estadoModel,
        private Prioridad $prioridadModel,
        private Usuario $usuarioModel
    ) {
    }

    public function listarAsignados(
        int $idRepartidor
    ): array {
        return $this->pedidoModel
            ->listarPorRepartidor($idRepartidor);
    }

    public function listarTodos(): array
    {
        return $this->pedidoModel->listarTodos();
    }

    public function listarPrioridades(): array
    {
        return $this->prioridadModel->listar();
    }

    public function listarEstados(): array
    {
        return $this->estadoModel->listar();
    }

    public function listarRepartidoresActivos(): array
    {
        return $this->usuarioModel->listarRepartidoresActivos();
    }

    public function crear(
        int $idEncargado,
        string $cliente,
        string $telefono,
        string $direccion,
        string $descripcion,
        int $idPrioridad
    ): int {
        $this->validarDatos(
            $cliente,
            $telefono,
            $direccion,
            $descripcion,
            $idPrioridad
        );

        $estadoInicial = $this->estadoModel
            ->buscarPorNombre(self::ESTADO_INICIAL);

        if ($estadoInicial === null) {
            throw new RuntimeException(
                'El estado En preparación no está registrado',
                500
            );
        }

        return $this->pedidoModel->crear(
            trim($cliente),
            trim($telefono),
            trim($direccion),
            trim($descripcion),
            $idEncargado,
            (int) $estadoInicial['id_estado'],
            $idPrioridad
        );
    }

    public function actualizarComoEncargado(
        int $idUsuarioSesion,
        int $idPedido,
        string $cliente,
        string $telefono,
        string $direccion,
        string $descripcion,
        int $idPrioridad,
        int $idEstado,
        ?int $idRepartidor
    ): void {
        $pedido = $this->pedidoModel
            ->buscarPorId($idPedido);

        if ($pedido === null) {
            throw new RuntimeException(
                'El pedido no existe',
                404
            );
        }

        $this->validarDatos(
            $cliente,
            $telefono,
            $direccion,
            $descripcion,
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

        $this->validarTransicion(
            (int) $pedido['id_estado'],
            $estadoNuevo['nombre']
        );

        $this->validarRepartidor($idRepartidor);

        $cambioEstado =
            (int) $pedido['id_estado'] !== $idEstado;

        $this->pedidoModel->actualizarComoEncargado(
            $idPedido,
            trim($cliente),
            trim($telefono),
            trim($direccion),
            trim($descripcion),
            $idPrioridad,
            $idEstado,
            $idRepartidor,
            $cambioEstado,
            $idUsuarioSesion
        );
    }

    public function actualizarEstadoRepartidor(
        int $idUsuarioSesion,
        int $idPedido,
        int $idEstado
    ): void {
        $pedido = $this->pedidoModel
            ->buscarPorId($idPedido);

        if ($pedido === null) {
            throw new RuntimeException(
                'El pedido no existe',
                404
            );
        }

        if ((int) $pedido['id_repartidor'] !== $idUsuarioSesion) {
            throw new RuntimeException(
                'No puedes modificar un pedido que no tienes asignado',
                403
            );
        }

        $estadoNuevo = $this->estadoModel
            ->buscarPorId($idEstado);

        if ($estadoNuevo === null) {
            throw new RuntimeException(
                'El estado seleccionado no existe',
                422
            );
        }

        $this->validarTransicion(
            (int) $pedido['id_estado'],
            $estadoNuevo['nombre']
        );

        $cambioEstado =
            (int) $pedido['id_estado'] !== $idEstado;

        $this->pedidoModel->actualizarEstado(
            $idPedido,
            $idEstado,
            $idUsuarioSesion,
            $cambioEstado
        );
    }

    //B05-corrección: el backend valida la transición con los nombres
    //reales de la tabla estados: En preparación -> En camino -> Entregado.
    //Repetir el estado actual no cambia nada y se acepta.
    private function validarTransicion(
        int $idEstadoActual,
        string $nombreEstadoNuevo
    ): void {
        $estadoActual = $this->estadoModel
            ->buscarPorId($idEstadoActual);

        if ($estadoActual === null) {
            throw new RuntimeException(
                'El estado actual del pedido no está registrado',
                500
            );
        }

        $nombreActual = $estadoActual['nombre'];

        if ($nombreActual === $nombreEstadoNuevo) {
            return;
        }

        if ($nombreActual === self::ESTADO_ENTREGADO) {
            throw new RuntimeException(
                'El pedido ya fue entregado y no admite cambios de estado',
                422
            );
        }

        $permitido =
            ($nombreActual === self::ESTADO_INICIAL &&
                $nombreEstadoNuevo === self::ESTADO_EN_CAMINO) ||
            ($nombreActual === self::ESTADO_EN_CAMINO &&
                $nombreEstadoNuevo === self::ESTADO_ENTREGADO);

        if (!$permitido) {
            throw new RuntimeException(
                'Cambio de estado no permitido desde ' . $nombreActual,
                422
            );
        }
    }

    private function validarDatos(
        string $cliente,
        string $telefono,
        string $direccion,
        string $descripcion,
        int $idPrioridad
    ): void {
        if (trim($cliente) === '') {
            throw new RuntimeException(
                'El cliente es obligatorio',
                422
            );
        }

        if (trim($telefono) === '') {
            throw new RuntimeException(
                'El teléfono es obligatorio',
                422
            );
        }

        if (trim($direccion) === '') {
            throw new RuntimeException(
                'La dirección es obligatoria',
                422
            );
        }

        if (trim($descripcion) === '') {
            throw new RuntimeException(
                'La descripción es obligatoria',
                422
            );
        }

        if (strlen(trim($cliente)) > self::MAX_CLIENTE) {
            throw new RuntimeException(
                'El cliente no puede superar los 100 caracteres',
                422
            );
        }

        if (strlen(trim($telefono)) > self::MAX_TELEFONO) {
            throw new RuntimeException(
                'El teléfono no puede superar los 20 caracteres',
                422
            );
        }

        if (strlen(trim($direccion)) > self::MAX_DIRECCION) {
            throw new RuntimeException(
                'La dirección no puede superar los 255 caracteres',
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

    private function validarRepartidor(?int $idRepartidor): void
    {
        if ($idRepartidor === null) {
            return;
        }

        $repartidor = $this->usuarioModel
            ->buscarPorId($idRepartidor);

        if ($repartidor === null) {
            throw new RuntimeException(
                'El repartidor seleccionado no existe',
                422
            );
        }

        if ($repartidor['rol'] !== 'repartidor') {
            throw new RuntimeException(
                'Solo se puede asignar un usuario con rol repartidor',
                422
            );
        }

        if ((int) $repartidor['activo'] !== 1) {
            throw new RuntimeException(
                'El repartidor seleccionado no está activo',
                422
            );
        }
    }
}
