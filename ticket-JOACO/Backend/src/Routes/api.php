<?php



//B05: rutas de pedidos (reemplazan a las de tickets).

$conexion = Conexion::conectar();

$usuarioModel = new Usuario($conexion);

$pedidoModel = new Pedido($conexion);

$authService = new AuthService($usuarioModel);

$authController = new AuthController($authService);

$usuarioService = new UsuarioService($usuarioModel);

$usuarioController = new UsuarioController($usuarioService);

$estadoModel = new Estado($conexion);

$prioridadModel = new Prioridad($conexion);

$pedidoService = new PedidoService($pedidoModel,$estadoModel,$prioridadModel,$usuarioModel);

$pedidoController = new PedidoController($pedidoService);


//----------Rutas login-------------

$router->post('/api/login',[$authController, 'login']);

$router->get('/api/sesion',[$authController, 'sesion']);

$router->post('/api/logout',[$authController, 'logout']);



$router->post('/api/usuarios', function () use ($usuarioController): void
{
        RolMiddleware::soloEncargado();

        $usuarioController->crear();
    }
);



//----------Rutas pedidos-------------

//Repartidor: solo sus pedidos asignados.

$router->get('/api/pedidos', function () use ($pedidoController): void
{
        RolMiddleware::soloRepartidor();
        $pedidoController->listarAsignados();
    }
);

$router->put('/api/pedidos',function () use ($pedidoController): void
{
        RolMiddleware::soloRepartidor();
        $pedidoController->actualizarEstado();
    }
);

//Encargado: gestión completa.

$router->get('/api/encargado/pedidos',function () use ($pedidoController): void {
        RolMiddleware::soloEncargado();
        $pedidoController->listarTodos();
    }
);

$router->post('/api/encargado/pedidos',function () use ($pedidoController): void {
        RolMiddleware::soloEncargado();
        $pedidoController->crear();
    }
);

$router->put('/api/encargado/pedidos',function () use ($pedidoController): void {
        RolMiddleware::soloEncargado();
        $pedidoController->actualizarComoEncargado();
    }
);

//Encargado: repartidores activos disponibles para asignar.

$router->get('/api/encargado/repartidores',function () use ($usuarioController): void {
        RolMiddleware::soloEncargado();
        $usuarioController->listarRepartidores();
    }
);




