<?php

declare(strict_types=1);

/*
Skrypt CRON: zamyka aukcje po czasie i ustala zwycięzców (reguła 7, kryterium 32). Uruchamiany co minutę przez
usługę `cron` z docker-compose.yaml (docker/cron/crontab); ręcznie:
    docker compose exec php php bin/close-auctions.php
Idempotentny: kolejne uruchomienie nie zmienia już zamkniętych aukcji i nie wysyła drugiego e-maila. Log idzie na
standardowe wyjście (jedna linia na zdarzenie). Kod wyjścia: 0 = sukces, 1 = błąd przy części aukcji (spróbuje
następny przebieg), 2 = błąd krytyczny (np. baza niedostępna). Tu jest tylko składanie zależności; logika
w App\Service\AuctionCloser.
*/

use App\Database;
use App\Repository\AuctionRepository;
use App\Repository\BidRepository;
use App\Repository\CompanyRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Service\AuctionCloser;
use App\Service\LineLogger;
use App\Service\MailService;
use App\Service\WonNotifier;

if (PHP_SAPI !== 'cli') {
    // Skrypt nie jest częścią aplikacji webowej (Nginx i tak wykonuje tylko /index.php).
    http_response_code(404);

    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/vendor/autoload.php';

$logger = new LineLogger();

try {
    $config = require $root . '/config.php';
    $database = new Database($config['database']);
    $pdo = $database->getConnection();

    $auctions = new AuctionRepository($pdo);
    $mailer = new MailService(
        $config['mail']['host'],
        $config['mail']['port'],
        $config['mail']['from'],
        $config['mail']['fromName'],
    );

    $closer = new AuctionCloser(
        $auctions,
        new BidRepository($pdo),
        new CompanyRepository($pdo),
        new NotificationRepository($pdo),
        new WonNotifier($mailer, new UserRepository($pdo), $auctions, $root . '/lang', $config['app']['url']),
        $pdo,
        $logger,
    );

    $report = $closer->closeDueAuctions();
} catch (\Throwable $exception) {
    $logger->error(sprintf('run=aborted reason=%s: %s', $exception::class, $exception->getMessage()));

    exit(2);
}

$logger->info(sprintf(
    'run=done closed=%d withWinner=%d skipped=%d failed=%d activated=%d mailsSent=%d mailsFailed=%d',
    $report->getClosed(),
    $report->getWithWinner(),
    $report->getSkipped(),
    $report->getFailed(),
    $report->getActivated(),
    $report->getMailsSent(),
    $report->getMailsFailed(),
));

exit($report->hasFailures() ? 1 : 0);
