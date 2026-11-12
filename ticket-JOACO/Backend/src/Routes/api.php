<?php



$conexion = Conexion::conectar();

$usuarioModel = new Usuario($conexion);

$ticketModel = new Ticket($conexion);

$authService = new AuthService($usuarioModel);

$authController = new AuthController($authService);

$usuarioService = new UsuarioService($usuarioModel);

$usuarioController = new UsuarioController($usuarioService);

$estadoModel = new Estado($conexion);

$prioridadModel = new Prioridad($conexion);

$ticketService = new TicketService($ticketModel,$estadoModel,$prioridadModel);

$ticketController = new TicketController($ticketService);


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



//----------Rutas tickets-------------

$router->get('/api/tickets', function () use ($ticketController): void 
{
        RolMiddleware::soloRepartidor();
        $ticketController->listar();
    }
);

$router->post('/api/tickets',function () use ($ticketController): void 
{
        RolMiddleware::soloRepartidor();
        $ticketController->crear();
    }
);

$router->put('/api/tickets', function () use ($ticketController): void 
{
        RolMiddleware::soloRepartidor();
        $ticketController->actualizar();
    }
);

$router->get('/api/encargado/tickets',function () use ($ticketController): void {
        RolMiddleware::soloEncargado();
        $ticketController->listarTodos();
    }
);

$router->post('/api/encargado/tickets',function () use ($ticketController): void {
        RolMiddleware::soloEncargado();
        $ticketController->crear();
    }
);

$router->put('/api/encargado/tickets',function () use ($ticketController): void {
        RolMiddleware::soloEncargado();
        $ticketController->actualizarComoEncargado();
    }
);




