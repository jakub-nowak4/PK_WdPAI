<?php

declare(strict_types=1);

use App\Controller\AccountController;
use App\Controller\AdminController;
use App\Controller\AuctionController;
use App\Controller\BidApiController;
use App\Controller\CompanyController;
use App\Controller\DefaultController;
use App\Controller\ErrorController;
use App\Controller\LanguageController;
use App\Controller\SecurityController;
use App\Exception\HttpException;
use App\Database;
use App\Repository\AuctionImageRepository;
use App\Repository\AuctionRepository;
use App\Repository\BidRepository;
use App\Repository\BrandRepository;
use App\Repository\CompanyRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Request;
use App\Routing;
use App\Service\AuctionDetailsService;
use App\Service\AuctionListService;
use App\Service\AuctionStateService;
use App\Service\AuthService;
use App\Service\BidService;
use App\Service\CompanyReviewService;
use App\Service\CurrentUserProvider;
use App\Service\LanguageService;
use App\Service\MailService;
use App\Service\MyBidsService;
use App\Service\OutbidNotifier;
use App\Service\Translator;
use App\Service\WelcomeMailService;
use App\Service\WonAuctionService;

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

// Poczta: MailService łączy się z SMTP dopiero przy wysyłce, więc samo utworzenie obiektu nic nie kosztuje.
$mailer = new MailService(
    $config['mail']['host'],
    $config['mail']['port'],
    $config['mail']['from'],
    $config['mail']['fromName'],
);
$welcomeMail = new WelcomeMailService(
    $mailer,
    dirname(__DIR__) . '/lang',
    $config['app']['url'],
);

// Użytkownik dla layoutu: czyta bazę dopiero dla zalogowanego (getConnection(...) to tylko referencja).
$currentUserProvider = new CurrentUserProvider($database->getConnection(...));

$routing = new Routing([
    Translator::class => $translator,
    LanguageService::class => $languageService,
    CurrentUserProvider::class => $currentUserProvider,
    CompanyRepository::class => static fn (): CompanyRepository => new CompanyRepository($database->getConnection()),
    AuthService::class =>static fn (): AuthService => new AuthService(
        new UserRepository($database->getConnection()),
        new CompanyRepository($database->getConnection()),
        $database->getConnection(),
        $translator,
        $welcomeMail,
    ),
    AuctionListService::class => static fn (): AuctionListService => new AuctionListService(
        new AuctionRepository($database->getConnection()),
        new BrandRepository($database->getConnection()),
    ),
    AuctionDetailsService::class => static fn (): AuctionDetailsService => new AuctionDetailsService(
        new AuctionRepository($database->getConnection()),
        new AuctionImageRepository($database->getConnection()),
        new BidRepository($database->getConnection()),
    ),
    // Ekran "Aukcja wygrana": tylko odczyt wyniku ustawionego przez CRON (aukcja i zwycięska oferta).
    WonAuctionService::class => static fn (): WonAuctionService => new WonAuctionService(
        new AuctionRepository($database->getConnection()),
        new BidRepository($database->getConnection()),
    ),
    UserRepository::class => static fn (): UserRepository => new UserRepository($database->getConnection()),
    // Oferta: BidService prowadzi transakcję na tym samym PDO, na którym działają repozytoria (docs/api.md 1.8).
    BidService::class => static fn (): BidService => new BidService(
        new AuctionRepository($database->getConnection()),
        new BidRepository($database->getConnection()),
        new CompanyRepository($database->getConnection()),
        new NotificationRepository($database->getConnection()),
        $database->getConnection(),
        $translator,
    ),
    // E-mail o przebiciu idzie po COMMIT oferty, w języku konta przebitego (users.locale).
    OutbidNotifier::class => static fn (): OutbidNotifier => new OutbidNotifier(
        $mailer,
        new UserRepository($database->getConnection()),
        new AuctionRepository($database->getConnection()),
        dirname(__DIR__) . '/lang',
        $config['app']['url'],
    ),
    MyBidsService::class => static fn (): MyBidsService => new MyBidsService(
        new BidRepository($database->getConnection()),
        new AuctionRepository($database->getConnection()),
    ),
    AuctionStateService::class => static fn (): AuctionStateService => new AuctionStateService(
        new AuctionDetailsService(
            new AuctionRepository($database->getConnection()),
            new AuctionImageRepository($database->getConnection()),
            new BidRepository($database->getConnection()),
        ),
        new BidRepository($database->getConnection()),
        new CompanyRepository($database->getConnection()),
        new NotificationRepository($database->getConnection()),
        $translator,
    ),
    CompanyReviewService::class => static fn (): CompanyReviewService => new CompanyReviewService(
        new CompanyRepository($database->getConnection()),
        new AuctionRepository($database->getConnection()),
        $database->getConnection(),
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
$routing->get('/api/auctions/{id}/state', BidApiController::class, 'state');
$routing->post('/api/auctions/{id}/bids', BidApiController::class, 'placeBid');
$routing->post('/admin/companies/{id}/approve', AdminController::class, 'approve');
$routing->post('/admin/companies/{id}/reject', AdminController::class, 'reject');
$routing->post('/register', SecurityController::class, 'register');
$routing->post('/register/company', SecurityController::class, 'registerCompany');
$routing->post('/login',SecurityController::class, 'login');
$routing->post('/logout', SecurityController::class, 'logout');
$routing->post('/language', LanguageController::class, 'change');

// Nieoczekiwany błąd (np. PDOException): jedna linia w logu, bez sesji, formularza i
// query (mogą zawierać hasła), potem ogólna odpowiedź 500 bez treści wyjątku.
// handleServerError() nie dotyka bazy, więc nie rzuca dalej (brak pętli).
$failWithServerError = static function (\Throwable $exception) use ($request, $translator): void {
    error_log(sprintf(
        'Unhandled %s: %s in %s:%d [%s %s]',
        $exception::class,
        preg_replace('/\s+/', ' ', $exception->getMessage()),
        $exception->getFile(),
        $exception->getLine(),
        $request->method(),
        $request->path(),
    ));

    if (!headers_sent()) {
        (new ErrorController($translator))->handleServerError($request);
    }
};

try {
    $routing->dispatch($request);
} catch (HttpException $exception) {
    // Wyjątek z catch nie trafia do sąsiedniego catch (\Throwable), więc render strony
    // błędu (np. layout czytający bazę zalogowanego) ma własny try/catch.
    try {
        (new ErrorController($translator, $currentUserProvider))->handle($exception, $request);
    } catch (\Throwable $inner) {
        $failWithServerError($inner);
    }
} catch (\Throwable $exception) {
    $failWithServerError($exception);
}
