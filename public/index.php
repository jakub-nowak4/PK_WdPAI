<?php

declare(strict_types=1);

use App\Controller\AccountController;
use App\Controller\AdminController;
use App\Controller\AuctionController;
use App\Controller\CompanyController;
use App\Controller\DefaultController;
use App\Controller\ErrorController;
use App\Controller\LanguageController;
use App\Controller\SecurityController;
use App\Exception\HttpException;
use App\Database;
use App\Repository\UserRepository;
use App\Request;
use App\Routing;
use App\Service\AuthService;
use App\Service\CurrentUserProvider;
use App\Service\LanguageService;
use App\Service\Translator;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$request = Request::fromGlobals();

// Sesja: konfigurowana raz tutaj (HttpOnly, SameSite=Lax, Secure na HTTPS).
// Secure zależy tylko od HTTPS widzianego przez PHP (Request::isSecure); nagłówka
// X-Forwarded-Proto nie ufamy, bo klient mógłby go podstawić.
// Ciasteczko sesyjne (lifetime 0) znika z zamknięciem przeglądarki, a po stronie
// serwera nieużywana sesja wygasa po 2 godzinach (gc_maxlifetime).
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
ini_set('session.gc_maxlifetime', '7200');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $request->isSecure(),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// Język: sesja -> ciasteczko -> pl. Zależności są tworzone tu i wstrzykiwane kontrolerom.
$languageService = new LanguageService();
$translator = new Translator(dirname(__DIR__) . '/lang', $languageService->resolve($request));

// Baza: Database łączy się leniwie. Serwisy z bazą to fabryki, wywoływane dopiero
// przy pierwszym kontrolerze, który ich potrzebuje (makiety i strony błędów nie łączą się z bazą).
$config = require dirname(__DIR__) . '/config.php';
$database = new Database($config['database']);

// Użytkownik dla layoutu: czyta bazę dopiero dla zalogowanego (getConnection(...) to tylko referencja).
$currentUserProvider = new CurrentUserProvider($database->getConnection(...));

$routing = new Routing([
    Translator::class => $translator,
    LanguageService::class => $languageService,
    CurrentUserProvider::class => $currentUserProvider,
    AuthService::class => static fn (): AuthService => new AuthService(
        new UserRepository($database->getConnection()),
        $translator,
    ),
]);

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
$routing->post('/register', SecurityController::class, 'register');
$routing->post('/login', SecurityController::class, 'login');
$routing->post('/logout', SecurityController::class, 'logout');
$routing->post('/language', LanguageController::class, 'change');

try {
    $routing->dispatch($request);
} catch (HttpException $exception) {
    (new ErrorController($translator, $currentUserProvider))->handle($exception, $request);
}
