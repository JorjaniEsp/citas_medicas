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

$app->group('/api', function(RouteCollectorProxy $api){

    $api->post('/auth/credentials', Auth::class . ':credentials');
    $api->group('/medicos', function(RouteCollectorProxy $endpoint){

        $endpoint->get('[/{id}]', Medico::class . ':read');
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
        //$url->get('[/paciente/{id}]', Citas::class . ':read');
        $url->get('/paciente/{id}', Citas::class . ':readPaciente');
        $url->get('/medico/{id}', Citas::class . ':readMedico');
        $url->post('', Citas::class . ':create');
    });

});