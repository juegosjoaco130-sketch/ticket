<?php


final class Router
{
    private array $rutas = [];

    public function get(string $ruta, callable $accion): void
    {
        $this->registrar('GET', $ruta, $accion);
    }

    public function post(string $ruta, callable $accion): void
    {
        $this->registrar('POST', $ruta, $accion);
    }

    public function put(string $ruta, callable $accion): void
    {
    $this->registrar('PUT', $ruta, $accion);
    }

    private function registrar(
        string $metodo,
        string $ruta,
        callable $accion
    ): void {
        $this->rutas[$metodo][$ruta] = $accion;
    }

    public function despachar(
        string $metodo,
        string $ruta
    ): void {
        if (!isset($this->rutas[$metodo][$ruta])) {
            Response::json([
                'error' => 'Ruta no encontrada'
            ], 404);
        }

        $accion = $this->rutas[$metodo][$ruta];

        call_user_func($accion);
    }
}