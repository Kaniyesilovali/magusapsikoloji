<?php
declare(strict_types=1);

namespace Panel;

/** Salt okunur yapılandırma erişimi: Config::get('db.host'). */
final class Config
{
    private static array $values = [];

    public static function init(array $values): void
    {
        self::$values = $values;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $node = self::$values;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($node) || !array_key_exists($segment, $node)) {
                return $default;
            }
            $node = $node[$segment];
        }
        return $node;
    }

    public static function isLocal(): bool
    {
        return self::get('app.env', 'production') === 'local';
    }
}
