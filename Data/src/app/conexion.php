<?php
// importa la clase ContainerInterface del contenedor Psr
use Psr\Container\ContainerInterface;

/**
 * guarda en el contenedor bajo la etiqueta la nueva función que va a ser la receta
 * de como generar una conexion a la bd
 * al pasarle como parametro el contenedor a la función, la función tiene accesos a 
 * otra funciones del contenedor
 */
$container->set("base_datos", function(ContainerInterface $c){

/*
    del contenedor ejecuta la función que guardo config.php en el contenedor
    y le devuelve el objeto con las credenciales
*/
    $conf = $c->get('config_db');

    $opc  = [
        // si hay un error en mis consultas lanza una exepcion grave
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

        // cuando la bd devuelva un resultado lo devueleve como objeto y así usar ->
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ];

    // DSN (Data Source Name) Es la cadena de texto con el formato exacto 
    // que exige PDO para saber a qué tipo de motor conectarse (mysql), 
    // en qué servidor ($conf->host) y a qué base de datos.
    $dsn = "mysql:host={$conf->host};dbname={$conf->database};charset={$conf->charset}";

    //intentar
    try {
        // hace la conexion, le pasa de DSN, el usuario, contraseña y opciones
        $conexion = new PDO($dsn, $conf->username, $conf->password, $opc);
    
    // atrapar si falla
    } catch (PDOException $e) {
        // mata el proceso y lanza un mensaje
        die("Error de conexión" . $e->getMessage());
    }
    // si sale bien retorna el objeto PDO con la conexion lista
    return $conexion;
});