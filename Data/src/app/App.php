<?php
// importa la clase AppFactory
use Slim\Factory\AppFactory;

// importa la clase container
use DI\Container;

// Carga automáticamente todas las librerías externas del proyecto (generado por Composer).
require __DIR__ . '/../../vendor/autoload.php';

// creo la caja vacia
$container = new Container();

$dotenv = Dotenv\Dotenv::createImmutable('/var/www/html');
$dotenv->load();

// almaceno la caja en el AppFactory de slim
AppFactory::setContainer($container);

// se construye el nuclep de la app
$app = AppFactory::create();
$app->addBodyParsingMiddleware();   // <-- esta línea es la que falta


require_once "routes.php";
require_once "config.php";
require_once "conexion.php";
require_once "eloquent.php";

// Arranca la aplicación (el motor de Slim) para que comience a escuchar y responder peticiones.
$app->run();