<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

/** Conserva la provenienza dei messaggi prodotti durante la sola richiesta corrente. */
class InterfaceTranslations
{
    private static array $bindings = [];

    public static function remember(string $text, string $key, array $parameters = [], string $format = 'icu'): void
    {
        if ($text === $key || (! str_starts_with($key, 'backend/') && ! str_starts_with($key, 'Validation.'))):
            return;
        endif;

        $arguments = [];
        foreach ($parameters as $name => $value):
            $arguments[$name] = in_array($name, ['field', 'param'], true) && is_string($value)
                ? (self::$bindings[$value] ?? $value)
                : $value;
        endforeach;

        self::$bindings[$text] = ['key' => $key, 'parameters' => $arguments, 'format' => $format, 'escaped' => true];
    }

    public static function binding(string $text): ?array
    {
        return self::$bindings[$text] ?? null;
    }

    public static function variants(array $binding): array
    {
        $variants = [];
        foreach (config('App')->supportedLocales as $locale):
            $language = new \CodeIgniter\Language\Language($locale);
            $render = static function(array $descriptor) use (&$render, $language): string {
                $parameters = [];
                foreach ($descriptor['parameters'] ?? [] as $key => $value):
                    $parameters[$key] = is_array($value) && isset($value['key']) ? $render($value) : $value;
                endforeach;

                if (($descriptor['format'] ?? 'icu') === 'sprintf'):
                    return sprintf($language->getLine($descriptor['key']), ...$parameters);
                endif;

                return $language->getLine($descriptor['key'], $parameters);
            };
            $variants[$locale] = $render($binding);
        endforeach;

        return ['variants' => $variants, 'escaped' => $binding['escaped'] ?? true];
    }

    public static function messages(array $data): array
    {
        $bindings = [];
        $collect = static function($value) use (&$collect, &$bindings): void {
            if (is_array($value)):
                foreach ($value as $item):
                    $collect($item);
                endforeach;
            elseif (is_string($value) && isset(self::$bindings[$value])):
                $bindings[$value] = self::variants(self::$bindings[$value]);
            endif;
        };
        $collect($data['message'] ?? null);
        $collect($data['errors'] ?? null);
        $collect($data['progressMessage'] ?? null);

        return $bindings;
    }
}
