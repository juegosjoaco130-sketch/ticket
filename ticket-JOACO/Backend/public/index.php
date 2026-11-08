<?php



ini_set('display_errors', '0');
ini_set('log_errors', '1');



session_name('ticket_session');

session_set_cookie_params([
    'httponly' => true,
    'secure' => isset($_SERVER['HTTPS']),
    'samesite' => 'Lax',
    'path' => '/'
]);

session_start();


$origenPermitido = 'http://localhost:3000';
$origenRecibido = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origenRecibido === $origenPermitido) {
    header("Access-Control-Allow-Origin: {$origenPermitido}");
    header('Access-Control-Allow-Credentials: true');
}

header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');



if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}   



spl_autoload_register(function (string $nombreClase): void {
    $carpetas = [
        'Core',
        'Models',
        'Services',
        'Controllers',
        'middleware'
    ];

    foreach ($carpetas as $carpeta) {
        $archivo = __DIR__
            . '/../src/'
            . $carpeta
            . '/'
            . $nombreClase
            . '.php';

        if (file_exists($archivo)) {
            require_once $archivo;
            return;
        }
    }
});



try {
    $router = new Router();

    require_once __DIR__ . '/../src/Routes/api.php';

    $metodo = $_SERVER['REQUEST_METHOD'];

    $ruta = parse_url(
        $_SERVER['REQUEST_URI'],
        PHP_URL_PATH
    );

    $ruta = rtrim($ruta, '/');

    if ($ruta === '') {
        $ruta = '/';
    }

    $router->despachar($metodo, $ruta);
} catch (Throwable $error) {
    error_log(
        $error::class . ': ' . $error->getMessage()
    );

    Response::json([
        'error' => 'Ocurrió un error interno en el servidor'
    ], 500);
}