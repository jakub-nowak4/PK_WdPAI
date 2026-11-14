<?php

declare(strict_types=1);

use App\Controller\AccountController;
use App\Controller\AdminController;
use App\Controller\AuctionController;
use App\Controller\CompanyController;
use App\Controller\DefaultController;
use App\Controller\ErrorController;
use App\Controller\LanguageController;
use App\Controller\ProjectController;
use App\Controller\SecurityController;
use App\Exception\HttpException;
use App\Request;
use App\Routing;
use App\Service\LanguageService;
use App\Service\Translator;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$request = Request::fromGlobals();

// Sesja: konfigurowana raz tutaj (HttpOnly, SameSite=Lax, Secure na HTTPS).
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'path' => '/',
    'secure' => $request->isSecure(),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// Język: sesja -> ciasteczko -> pl. Zależności są tworzone tu i wstrzykiwane kontrolerom.
$languageService = new LanguageService();
$translator = new Translator(dirname(__DIR__) . '/lang', $languageService->resolve($request));

$routing = new Routing([
    Translator::class => $translator,
    LanguageService::class => $languageService,
]);

$routing->get('/projects', ProjectController::class, 'index');
$routing->get('/projects/{id}', ProjectController::class, 'show');
$routing->get('/auctions', AuctionController::class, 'index');
$routing->get('/auctions/{id}',AuctionController::class, 'show');
$routing->get('/auctions/{id}/won', AuctionController::class, 'won');
$routing->get('/my-bids', AccountController::class, 'myBids');
$routing->get('/notifications', AccountController::class, 'notifications');
$routing->get('/login',SecurityController::class, 'login');
$routing->get('/register', SecurityController::class, 'register');
$routing->get('/register/company', SecurityController::class, 'registerCompany');
$routing->get('/account/pending', AccountController::class, 'pending');
$routing->get('/company', CompanyController::class, 'index');
$routing->get('/company/auctions/new', CompanyController::class, 'create');
$routing->get('/company/auctions/{id}/delete', CompanyController::class, 'delete');
$routing->get('/admin', AdminController::class, 'index');
$routing->get('/', DefaultController::class, 'index');
$routing->post('/language', LanguageController::class, 'change');

try {
    $routing->dispatch($request);
} catch (HttpException $exception) {
    (new ErrorController($translator))->handle($exception, $request);
}
