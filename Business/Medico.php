<?php

namespace App\controllers;

// Response es el objeto que usamos para enviar la respuesta al usuario 
use Psr\Http\Message\ResponseInterface as Response;

// El objeto que contiene toda la información de lo que el usuario nos pidió 
// (por ejemplo, si nos envió datos por la URL o por un formulario).
use Psr\Http\Message\ServerRequestInterface as Request;

// El molde oficial para poder recibir nuestra "caja de herramientas".
use Psr\Container\ContainerInterface;
use App\Models\Medico as MedicoModel;
use Illuminate\Database\QueryException;
use App\Models\User;   // ← agrégala
// use RuntimeException;   // ← agrégala


class Medico {
    public function __construct( private ContainerInterface $container) { }

    public function read(Request $request, Response $response, array $args){
        // le pido al contenedor la configuracion, que guarde en conexion.php
        $this->container->get('eloquent'); 

        if( isset($args['id']) ){
            $medicos = ['datos' => 'medico por id'];
            return $this->json($response,['data0' => $medicos], 200);
        }
        $medicos = ['datos' => 'lista de medicos'];
        return $this->json($response,['data0' => $medicos], 200);
    }

    public function filter(Request $request, Response $response, array $args) : Response {
    }

    public function create(Request $request, Response $response, array $args): Response {
        $data = $request->getParsedBody();
        $medico = $data;
        return $this->json($response, ['data' => $medico] , 201);
    }

    public function update(Request $request, Response $response, array $args): Response {
        
    }

    public function delete(Request $request, Response $response, array $args): Response {

    }

    private function json(Response $response, array $payload, int $status) : Response {
        $response->getBody()->write(json_encode($payload,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        // a la respuesta le añadimos una cabecera, con tipo de contenido y codigo de estado
        return $response
            ->withHeader('Content-Type', 'application/json ; charset=utf-8')
            ->withStatus($status);
    }
}

