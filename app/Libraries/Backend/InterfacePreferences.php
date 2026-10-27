<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

/** Preferenze di presentazione della sola sessione amministrativa. */
class InterfacePreferences
{
    public const SESSION_KEY = 'backendInterfacePreferences';
    public const THEMES = ['white', 'black', 'grey', 'dark'];

    private function read(): array
    {
        $preferences = session()->get(self::SESSION_KEY);

        return is_array($preferences) ? $preferences : [];
    }

    public function language(): string
    {
        $language = $this->read()['language'] ?? setting('Backend\General', 'language');

        return in_array($language, config('App')->supportedLocales, true) ? $language : 'en';
    }

    public function theme(): string
    {
        $theme = $this->read()['theme'] ?? setting('Backend\General', 'theme');

        return in_array($theme, self::THEMES, true) ? $theme : 'grey';
    }

    public function set(string $key, string $value): void
    {
        $allowed = $key === 'language' ? config('App')->supportedLocales : ($key === 'theme' ? self::THEMES : []);

        if ( ! in_array($value, $allowed, true)):
            throw new \InvalidArgumentException('Invalid interface preference.');
        endif;

        $preferences = $this->read();
        $preferences[$key] = $value;
        session()->set(self::SESSION_KEY, $preferences);
    }

    public function clear(): void
    {
        session()->remove(self::SESSION_KEY);
    }

    /** Usa esclusivamente cataloghi backend locali, senza percorsi forniti dal client. */
    public function translations(string $locale): array
    {
        if ( ! in_array($locale, config('App')->supportedLocales, true)):
            throw new \InvalidArgumentException('Invalid interface language.');
        endif;

        $catalogues = [APPPATH . 'Language'];
        foreach (glob(ROOTPATH . 'modules/Backend/*/Language', GLOB_ONLYDIR) ?: [] as $directory):
            $catalogues[] = $directory;
        endforeach;

        $keys = [];
        $flatten = static function(array $values, string $prefix) use (&$flatten, &$keys): void {
            foreach ($values as $key => $value):
                if (is_array($value)):
                    $flatten($value, $prefix . '.' . $key);
                elseif (is_string($value)):
                    $keys[$prefix . '.' . $key] = true;
                endif;
            endforeach;
        };

        foreach ($catalogues as $directory):
            foreach (array_unique(['en', $locale]) as $language):
                $root = $directory . '/' . $language . '/backend';
                if ( ! is_dir($root)):
                    continue;
                endif;

                $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
                foreach ($files as $file):
                    if ($file->getExtension() !== 'php'):
                        continue;
                    endif;

                    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1, -4));
                    $values = require $file->getPathname();
                    if (is_array($values)):
                        $flatten($values, 'backend/' . $relative);
                    endif;
                endforeach;
            endforeach;
        endforeach;

        $translations = [];
        foreach (array_keys($keys) as $key):
            $value = lang($key, [], $locale);
            if (is_string($value)):
                $translations[$key] = $value;
            endif;
        endforeach;

        return $translations;
    }
}
