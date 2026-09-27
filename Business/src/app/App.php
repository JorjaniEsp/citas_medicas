<?php

use Slim\Factory\AppFactory;
use DI\Container;
use JimTools\JwtAuth\Decoder\FirebaseDecoder;
use JimTools\JwtAuth\Exceptions\AuthorizationException;
use JimTools\JwtAuth\Middleware\JwtAuthentication;
use JimTools\JwtAuth\Options;
use JimTools\JwtAuth\Rules\RequestPathRule;
use JimTools\JwtAuth\Secret;

require __DIR__ . '/../../vendor/autoload.php';

$container = new Container();

$dotenv = Dotenv\Dotenv::createImmutable('/var/www/html');
$dotenv->load();

AppFactory::setContainer($container);

// se construye el nuclep de la app
$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

require_once "config.php";
require_once "routes.php";

$app->add(new JwtAuthentication(
    new Options(isSecure: false),
    new FirebaseDecoder(new Secret($_ENV['KEY'], 'HS256')),
    [new RequestPathRule(['/'], ['/api/auth/login'])]
));

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setErrorHandler(AuthorizationException::class,
    function ($request, AuthorizationException $exception, bool $displayErrorDetails) use ($app) {
        $response = $app->getResponseFactory()->createResponse(401);
        $response->getBody()->write(json_encode([
            'error' => 'Token inválido o ausente.',
        ], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
);

$app->run();