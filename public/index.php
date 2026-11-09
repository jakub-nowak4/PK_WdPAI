<?php

declare(strict_types=1);

use App\Controller\AuctionController;
use App\Controller\DefaultController;
use App\Controller\ErrorController;
use App\Controller\ProjectController;
use App\Controller\SecurityController;
use App\Exception\HttpException;
use App\Request;
use App\Routing;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$request = Request::fromGlobals();
$routing = new Routing();

$routing->get('/projects', ProjectController::class, 'index');
$routing->get('/projects/{id}', ProjectController::class, 'show');
$routing->get('/auctions/{id}', AuctionController::class, 'show');
$routing->get('/login', SecurityController::class, 'login');
$routing->get('/register', SecurityController::class, 'register');
$routing->get('/register/company', SecurityController::class, 'registerCompany');
$routing->get('/', DefaultController::class, 'index');

try {
    $routing->dispatch($request);
} catch (HttpException $exception) {
    (new ErrorController())->handle($exception, $request);
}
