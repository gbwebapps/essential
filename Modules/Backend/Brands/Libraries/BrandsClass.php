<?php declare(strict_types = 1);

namespace Modules\Backend\Brands\Libraries;

/**
 * Prepara barre dei collegamenti e asset specifici del modulo Brands.
 */
class BrandsClass
{
    /* LINKS BAR ----------------------------------------------------------------------------------- */

    public function getLinksBarShowAll(): array
    {
        return [
            ['icon' => '<i class="fa-solid fa-plus"></i>', 'label' => lang('backend/brands.linksBar.add'), 'route' => 'backend/brands/add'],
        ];
    }

    public function getLinksBarAdd(): array
    {
        return [
            ['icon' => '<i class="fa-solid fa-list"></i>', 'label' => lang('backend/brands.linksBar.showAll'), 'route' => 'backend/brands/showAll'],
        ];
    }

    public function getLinksBarEdit(?string $uuid = null): array
    {
        if ( ! $uuid):
            return [];
        endif;

        return [
            ['icon' => '<i class="fa-solid fa-list"></i>', 'label' => lang('backend/brands.linksBar.showAll'), 'route' => 'backend/brands/showAll'],
            ['icon' => '<i class="fa-solid fa-plus"></i>', 'label' => lang('backend/brands.linksBar.add'), 'route' => 'backend/brands/add'],
            ['icon' => '<i class="fa-solid fa-eye"></i>', 'label' => lang('backend/brands.linksBar.show'), 'route' => "backend/brands/show/{$uuid}"],
        ];
    }

    public function getLinksBarShow(?string $uuid = null): array
    {
        if ( ! $uuid):
            return [];
        endif;

        return [
            ['icon' => '<i class="fa-solid fa-list"></i>', 'label' => lang('backend/brands.linksBar.showAll'), 'route' => 'backend/brands/showAll'],
            ['icon' => '<i class="fa-solid fa-plus"></i>', 'label' => lang('backend/brands.linksBar.add'), 'route' => 'backend/brands/add'],
            ['icon' => '<i class="fa-solid fa-pen"></i>', 'label' => lang('backend/brands.linksBar.edit'), 'route' => "backend/brands/edit/{$uuid}"],
        ];
    }

    /* JAVASCRIPT ---------------------------------------------------------------------------------- */

    public function getJsShowAll(): array
    {
        $lang = setting('Backend\General')->language;
        $locale = ($lang === 'en' ? 'default' : $lang);

        return [
            ['id' => 'flatpickr-js', 'path' => 'assets/vendor/flatpickr/js/flatpickr.min.js', 'position' => 'before', 'target' => 'brands-js'],
            ['id' => $locale . '-js', 'path' => 'assets/vendor/flatpickr/js/' . $locale . '.js', 'position' => 'after', 'target' => 'flatpickr-js'],
        ];
    }

    public function getJsShow(): array
    {
        return [
            ['id' => 'html2pdf-js', 'path' => 'assets/vendor/html2pdf/js/html2pdf.bundle.min.js', 'position' => 'before', 'target' => 'brands-js'],
        ];
    }

    /* CSS ----------------------------------------------------------------------------------------- */

    public function getCssShowAll(): array
    {
        return [
            ['id' => 'flatpickr-css', 'path' => 'assets/vendor/flatpickr/css/flatpickr.min.css', 'position' => 'before', 'target' => 'backend-css'],
        ];
    }

    /* OPTIONS ------------------------------------------------------------------------------------- */

    /* Inserire qui eventuali metodi getOptions{Action} quando saranno definite le viste. */

    /* COMPONENTI FUTURI --------------------------------------------------------------------------- */

    /* Inserire qui configurazioni di componenti specifici del modulo Brands. */
}
