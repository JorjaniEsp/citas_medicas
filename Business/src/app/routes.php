<?php

namespace App\controllers;

use Slim\Routing\RouteCollectorProxy;

$app->group('/api', function(RouteCollectorProxy $api){
    $api->post('/auth/login', Auth::class . ':login');
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
        //$url->get('/paciente/{id}', Citas::class . ':readPaciente');
        $url->get('/medico/{id}', Citas::class . ':readMedico');
        $url->post('', Citas::class . ':create');
    });
});