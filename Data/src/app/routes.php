<?php


namespace App\controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;


$app->get('/', function (Request $request, Response $response, array $args) {
    $response->getBody()->write("Hello");
    return $response;
});

$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");
    return $response;
});

// todo lo que este aqui dentro empezara con /api "localhost:8080/api"
$app->group('/api', function(RouteCollectorProxy $api){
    //$api heredo el contexto
    $api->post('/auth/credentials', Auth::class . ':credentials');
    // todo lo que este aqui adentro tendra /medico | "localhost:8080/api/medico"
    $api->group('/medicos', function(RouteCollectorProxy $endpoint){

        // se registra un endpoint a la URL localhost:8080/api/medico
        // no se le agrego nada más
        $endpoint->get('[/{id}]', Medico::class . ':read');
        // Medico::class devuelve el nombre de la clase junto a su namespace y lo 
        // concate con : y nombre del metodo, slim lee la parte izquierda y crea
        // el objeto, le inyecta sus dependencias, al lado derecho ejecuta el metodo
        // y pasa el request y response
        $endpoint->get('/filter/{offset}/{limit}', Medico::class . ':filter');
        
        $endpoint->put('/{id}', Medico::class . ':update');
        $endpoint->delete('/{id}', Medico::class . ':delete');
        $endpoint->post('',Medico::class . ':create');
    });

    $api->group('/pacientes', function(RouteCollectorProxy $endpoint){

        $endpoint->get('[/{id}]', Paciente::class . ':read');

        $endpoint->post('',Paciente::class . ':create');

        $endpoint->put('/{id}', Paciente::class . ':update');

        $endpoint->delete('/{id}', Paciente::class . ':delete');

        $endpoint->get('/filter/{offset}/{limit}', Paciente::class . ':filter');
    });

    $api->group('/citas', function(RouteCollectorProxy $url) {
        $url->get('[/paciente/{id}]', Citas::class . ':read');
        $url->post('', Citas::class . ':create');
    });

});