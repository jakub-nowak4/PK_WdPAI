<?php

declare(strict_types=1);

namespace App;

use App\Controller\AppController;
use App\Exception\HttpException;

/*
Router: metoda HTTP + ścieżka -> publiczna metoda kontrolera.
Trasy to biała lista rejestrowana w public/index.php. Nazwa kontrolera i akcji
nigdy nie pochodzi z URL. Router nie zawiera logiki biznesowej.

Dopasowanie jest dwuetapowe (docs/api.md, 1.2):
1. kształt ścieżki: każdy {parametr} to jeden segment [^/]+, reszta identyczna;
2. walidacja wartości parametru ({id}: ^[1-9][0-9]{0,9}$ i <= 2147483647).
Kształt pasuje, wartość nie -> 400. Żaden kształt nie pasuje -> 404.
*/
final class Routing
{
    private const MAX_ID = 2147483647;

    /** @var list<array{method: string, pattern: string, static: bool, controller: class-string<AppController>, action: string}> */
    private array $routes = [];

    /**
     * Zależności tworzone w public/index.php, wstrzykiwane do konstruktorów
     * kontrolerów według typu parametru (klucz = nazwa klasy).
     *
     * @param array<class-string, object> $services
     */
    public function __construct(private readonly array $services = [])
    {
    }

    /**
     * @param class-string<AppController> $controller
     */
    public function get(string $path, string $controller, string $action): void
    {
        $this->add('GET', $path, $controller, $action);
    }

    /**
     * @param class-string<AppController> $controller
     */
    public function post(string $path, string $controller, string $action): void
    {
        $this->add('POST', $path, $controller, $action);
    }

    private function add(
        string $method,
        string $path,
        string $controller,
        string $action,
    ): void {
        if (!is_subclass_of($controller, AppController::class)) {
            throw new \InvalidArgumentException('Invalid controller: ' . $controller);
        }

        if (!method_exists($controller, $action)) {
            throw new \InvalidArgumentException(
                'Missing action: ' . $controller . '::' . $action,
            );
        }

        $this->routes[] = [
            'method' => $method,
            'pattern' => $this->createPattern($path),
            'static' => !str_contains($path, '{'),
            'controller' => $controller,
            'action' => $action,
        ];
    }

    /*
    Funkcja dzieli ścieżkę na fragmenty. Zwykłe fragmenty escapuje, a fragment
    zapisany w klamrach, na przykład {id}, zamienia na nazwany parametr
    pasujący do jednego segmentu. Wartość sprawdza dopiero validateParameter().
    Modyfikator D sprawia, że $ nie akceptuje znaku nowej linii na końcu.
    */
    private function createPattern(string $path): string
    {
        $parts = explode('/', $path);

        foreach ($parts as &$part) {
            if (str_starts_with($part, '{') && str_ends_with($part, '}')) {
                $parameterName = substr($part, 1, -1);

                if (preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]*\z/', $parameterName) !== 1) {
                    throw new \InvalidArgumentException('Invalid route parameter: ' . $part);
                }

                $part = '(?P<' . $parameterName . '>[^/]+)';
                continue;
            }

            $part = preg_quote($part, '~');
        }
        unset($part);

        return '~^' . implode('/', $parts) . '$~D';
    }

    /*
    Wywołuje akcję pasującej trasy albo rzuca HttpException (400 lub 404).
    Trasy statyczne mają pierwszeństwo przed trasami z parametrem.
    Trasa tylko dla innej metody HTTP jest traktowana jak brak trasy (404).
    */
    public function dispatch(Request $request): void
    {
        $path = $request->path();
        $invalidParameter = false;

        foreach ([true, false] as $static) {
            foreach ($this->routes as $route) {
                if ($route['static'] !== $static || $route['method'] !== $request->method()) {
                    continue;
                }

                if (preg_match($route['pattern'], $path, $matches) !== 1) {
                    continue;
                }

                $parameters = $this->extractParameters($matches);

                if ($parameters === null) {
                    $invalidParameter = true;
                    continue;
                }

                $this->invoke($route['controller'], $route['action'], $parameters, $request);

                return;
            }
        }

        throw $invalidParameter
            ? HttpException::badRequest('Invalid path parameter')
            : HttpException::notFound('Route not found');
    }

    /*
    Zostawia tylko nazwane grupy regexu i waliduje ich wartości.
    Zwraca null, gdy któraś wartość ma zły format.
    */
    private function extractParameters(array $matches): ?array
    {
        $parameters = [];

        foreach ($matches as $name => $value) {
            if (!is_string($name)) {
                continue;
            }

            $parsed = $this->parseParameter($name, $value);

            if ($parsed === null) {
                return null;
            }

            $parameters[$name] = $parsed;
        }

        return $parameters;
    }

    /*
    {id} musi być dodatnią liczbą całkowitą bez zer wiodących mieszczącą się w INTEGER
    PostgreSQL i trafia do akcji jako int. Pozostałe parametry zostają tekstem.
    */
    private function parseParameter(string $name, string $value): int|string|null
    {
        if ($name !== 'id') {
            return $value;
        }

        if (preg_match('/\A[1-9][0-9]{0,9}\z/', $value) !== 1) {
            return null;
        }

        $id = (int) $value;

        return $id <= self::MAX_ID ? $id : null;
    }

    /*
    Tworzy kontroler, podając do konstruktora zależności z mapy $services
    dobrane po typie parametru. Brak zależności to błąd konfiguracji (500).
    */
    private function createController(string $controllerClass): AppController
    {
        $constructor = (new \ReflectionClass($controllerClass))->getConstructor();
        $arguments = [];

        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();
            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : '';

            if (!isset($this->services[$typeName])) {
                throw new \LogicException(
                    'Missing dependency ' . $typeName . ' for ' . $controllerClass,
                );
            }

            $arguments[] = $this->services[$typeName];
        }

        return new $controllerClass(...$arguments);
    }

    /*
    Tworzy kontroler i wywołuje akcję. Jeśli akcja deklaruje parametr typu Request,
    router przekazuje do niej obiekt żądania (np. index(Request $request)).
    */
    private function invoke(
        string $controllerClass,
        string $action,
        array $parameters,
        Request $request,
    ): void {
        $controller = $this->createController($controllerClass);

        foreach ((new \ReflectionMethod($controller, $action))->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof \ReflectionNamedType && $type->getName() === Request::class) {
                $parameters[$parameter->getName()] = $request;
            }
        }

        $controller->{$action}(...$parameters);
    }
}
