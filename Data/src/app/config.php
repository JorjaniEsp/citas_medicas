<?php
// aqui va a estar la configuración de la bd

// en el contenedor se guarda con esa etiqueta, esa función anonima que solo se va a ejecutar
// cuando se invoque
$container->set('config_db', function () {

// va a retornar un array que va a castear en un objeto y acceder a sus datos con ->
// ['username'] = 'root' || objeto->username = 'root'
    return (object) [
        'host'     => $_ENV['DB_HOST'],
        'username' => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASSW'],
        'database' => $_ENV['DB_NAME'],
        'charset'  => 'utf8mb4'
    ];
});