<?php

namespace Contabai;

class View
{
    public static function render(string $name, array $data = []): string
    {
        $path = __DIR__ . '/Views/' . str_replace('.', '/', $name) . '.php';

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
