<?php
declare(strict_types=1);

namespace Panel;

/**
 * Küçük bir yönlendirici. Desendeki {id} yalnızca rakamlarla eşleşir;
 * eşleşen değerler denetleyici metoduna sırayla int olarak geçirilir.
 */
final class Router
{
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $pattern, array $handler): void
    {
        $this->routes['GET'][$pattern] = $handler;
    }

    public function post(string $pattern, array $handler): void
    {
        $this->routes['POST'][$pattern] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $method = $method === 'HEAD' ? 'GET' : $method;
        $path   = '/' . trim($path, '/');

        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            if (preg_match(self::toRegex($pattern), $path, $matches)) {
                array_shift($matches);
                [$class, $action] = $handler;
                (new $class())->{$action}(...array_map('intval', $matches));
                return;
            }
        }

        // Yanlış metotla gelen istek (örn. POST rotasına GET) da 404 sayılır.
        View::error(404, 'Sayfa bulunamadı', 'Aradığınız sayfa taşınmış veya hiç var olmamış olabilir.');
    }

    private static function toRegex(string $pattern): string
    {
        $regex = '';
        foreach (preg_split('#(\{[a-z_]+\})#', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE) as $part) {
            $regex .= str_starts_with($part, '{') ? '(\d+)' : preg_quote($part, '#');
        }
        return '#^' . $regex . '$#';
    }
}
