<?php declare(strict_types = 1);

/** Metadati delle traduzioni: il rendering iniziale rimane interamente server side. */
function ui_binding(?string $key, string $target = 'text', array $parameters = [], bool $escaped = false, string $suffix = '', string $prefix = ''): string
{
    if ($key === null || $key === ''):
        return '';
    endif;

    $binding = ['key' => $key, 'parameters' => $parameters, 'escaped' => $escaped, 'suffix' => $suffix, 'prefix' => $prefix];

    return 'data-i18n-' . $target . '="' . esc(json_encode($binding, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'attr') . '"';
}

function ui_parts(array $parts, string $target): string
{
    return 'data-i18n-' . $target . '="' . esc(json_encode(['parts' => $parts], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'attr') . '"';
}

function ui_text(string $key, array $parameters = [], bool $escaped = false): string
{
    $text = lang($key);
    if ($parameters !== []):
        $text = sprintf($text, ...$parameters);
    endif;

    return '<span ' . ui_binding($key, 'text', $parameters, $escaped) . '>' . ($escaped ? esc($text) : $text) . '</span>';
}

/** Per etichette già tradotte da menu, presenter e configurazioni PHP. */
function ui_value(string $value, bool $escaped = true): string
{
    $binding = \App\Libraries\Backend\InterfaceTranslations::binding($value);
    if ($binding === null):
        return $escaped ? esc($value) : $value;
    endif;

    $binding['escaped'] = $escaped;

    return '<span data-i18n-text="' . esc(json_encode($binding, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'attr') . '">' . ($escaped ? esc($value) : $value) . '</span>';
}

function ui_value_binding(string $value, string $target = 'text', string $suffix = ''): string
{
    $binding = \App\Libraries\Backend\InterfaceTranslations::binding($value);
    if ($binding === null):
        return '';
    endif;

    $binding['suffix'] = $suffix;

    return 'data-i18n-' . $target . '="' . esc(json_encode($binding, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'attr') . '"';
}

/** Registra anche i parametri dei messaggi che utilizzano il formato PHP sprintf. */
function ui_message(string $key, array $parameters = [], ?string $template = null): string
{
    $template ??= lang($key);
    $text = $parameters === [] ? $template : sprintf($template, ...$parameters);
    \App\Libraries\Backend\InterfaceTranslations::remember($text, $key, $parameters, 'sprintf');

    return $text;
}

function ui_value_format(string $template, array $parameters): string
{
    $binding = \App\Libraries\Backend\InterfaceTranslations::binding($template);

    return $binding !== null ? ui_text($binding['key'], $parameters) : sprintf($template, ...$parameters);
}

function ui_date(?string $date, ?string $format = null): string
{
    $variants = [];
    foreach (config('App')->supportedLocales as $locale):
        $variants[$locale] = convertDate($date, $format, $locale);
    endforeach;

    $binding = ['variants' => $variants, 'escaped' => true];

    return '<span data-i18n-text="' . esc(json_encode($binding, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'attr') . '">' . esc(convertDate($date, $format)) . '</span>';
}

function backend_language(): string
{
    return service('request')->getLocale();
}

function backend_theme(): string
{
    return (new \App\Libraries\Backend\InterfacePreferences())->theme();
}
