<?php

namespace Contabai;

class View
{
    private const OVERRIDABLE = [
        'listing.index',
        'listing.show',
        'listing.random',
        'search.index',
        'components.listing-card',
        'components.listing-card-static',
        'components.listing-card-helpers',
        'components.image-gallery',
        'components.star-rating',
    ];

    public static function path(string $name): string
    {
        $relative = str_replace('.', '/', $name) . '.php';
        $override = in_array($name, self::OVERRIDABLE, true) ? locate_template('contabai/' . $relative) : '';

        return $override ?: __DIR__ . '/Views/' . $relative;
    }

    public static function render(string $name, array $data = []): string
    {
        $path = self::path($name);

        if (! file_exists($path)) {
            return '';
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $path;

        return ob_get_clean();
    }

    public static function component(string $name, array $data = []): string
    {
        return self::render($name, $data);
    }
}
