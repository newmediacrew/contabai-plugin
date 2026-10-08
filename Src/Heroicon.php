<?php

namespace Contabai;

class Heroicon
{
    public static function render(string $name, string $style = 'outline', string $class = 'w-5 h-5'): string
    {

        if (str_contains($name, '..') || str_contains($name, '/')) {
            return '';
        }

        $path = dirname(__DIR__) . '/assets/icons/' . $style . '/' . $name . '.svg';

        if (! file_exists($path)) {
            return '';
        }

        $svg = file_get_contents($path);

        return str_replace('<svg', '<svg class="' . esc_attr($class) . '"', $svg);
    }

    public static function outline(string $name, string $class = 'w-5 h-5'): string
    {
        return self::render($name, 'outline', $class);
    }

    public static function solid(string $name, string $class = 'w-5 h-5'): string
    {
        return self::render($name, 'solid', $class);
    }
}
