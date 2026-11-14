<?php


final class UsuarioController
{
    public function __construct(
        private UsuarioService $usuarioService
    ) {
    }

    public function crear(): void
    {
        $contenido = file_get_contents('php://input');

        try {
            $datos = json_decode(
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

        $cedula = $datos['cedula'] ?? '';
        $nombre = $datos['nombre'] ?? '';
        $apellido = $datos['apellido'] ?? '';
        $usuario = $datos['usuario'] ?? '';
        $password = $datos['password'] ?? '';
        $rol = $datos['rol'] ?? '';

        if (
            $cedula === '' ||
            $nombre === '' ||
            $apellido === '' ||
            $usuario === '' ||
            $password === '' ||
            $rol === ''
        ) {
            Response::json([
                'error' => 'Todos los campos son obligatorios'
            ], 422);
        }

        try {
            $nuevoUsuario = $this->usuarioService->crear(
                $cedula,
                $nombre,
                $apellido,
                $usuario,
                $password,
                $rol
            );

            Response::json([
                'mensaje' => 'Usuario creado correctamente',
                'usuario' => $nuevoUsuario
            ], 201);
        } catch (RuntimeException $error) {
            $codigo = $error->getCode();

            if (!in_array($codigo, [409, 422], true)) {
                $codigo = 500;
            }

            Response::json([
                'error' => $error->getMessage()
            ], $codigo);
        }
    }

    //B05: repartidores activos disponibles para asignar pedidos.
    public function listarRepartidores(): void
    {
        Response::json([
            'repartidores' => $this->usuarioService
                ->listarRepartidoresActivos()
        ]);
    }
}