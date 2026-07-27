<?php
declare(strict_types=1);

namespace Panel;

/**
 * Küçük bir yönlendirici. Desendeki {id} yalnızca rakamlarla eşleşir ve
 * denetleyiciye int olarak geçer; {token} onaltılık bir dizeyle eşleşir ve
 * string olarak geçer (check-in bağlantıları giriş yerine jeton taşıyor).
 *
 * Jeton deseni bilinçli olarak gevşek: uzunluğu ya da içeriği bozuk bir
 * bağlantıya 404 vermek yerine denetleyiciye ulaşması gerekiyor, ki kişi
 * "sayfa bulunamadı" değil "bu bağlantı artık geçerli değil" görsün.
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
            [$regex, $names] = self::compile($pattern);
            if (preg_match($regex, $path, $matches)) {
                array_shift($matches);
                [$class, $action] = $handler;
                $args = [];
                foreach ($matches as $index => $value) {
                    $args[] = ($names[$index] ?? 'id') === 'token' ? $value : (int) $value;
                }
                (new $class())->{$action}(...$args);
                return;
            }
        }

        // Yanlış metotla gelen istek (örn. POST rotasına GET) da 404 sayılır.
        View::error(404, 'Sayfa bulunamadı', 'Aradığınız sayfa taşınmış veya hiç var olmamış olabilir.');
    }

    /** @return array{0:string, 1:list<string>} Desenin regex'i ve sıradaki parametre adları. */
    private static function compile(string $pattern): array
    {
        $regex = '';
        $names = [];
        foreach (preg_split('#(\{[a-z_]+\})#', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE) as $part) {
            if (!str_starts_with($part, '{')) {
                $regex .= preg_quote($part, '#');
                continue;
            }
            $name    = trim($part, '{}');
            $names[] = $name;
            $regex  .= $name === 'token' ? '([0-9A-Za-z]{8,128})' : '(\d+)';
        }
        return ['#^' . $regex . '$#', $names];
    }
}
