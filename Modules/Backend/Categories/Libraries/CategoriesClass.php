<?php declare(strict_types = 1);

namespace Modules\Backend\Categories\Libraries;

/**
 * Prepara barre dei collegamenti e asset specifici del modulo Categories.
 */
class CategoriesClass
{
    /* LINKS BAR ----------------------------------------------------------------------------------- */

    private function getCategoryTreeLink(): array
    {
        return [
            'icon' => '<i class="fa-solid fa-folder-tree"></i>',
            'label' => lang('backend/categories.linksBar.tree'),
            'href' => '#categoriesTreeModal',
            'data-bs-toggle' => 'modal',
            'data-bs-target' => '#categoriesTreeModal',
            'aria-controls' => 'categoriesTreeModal',
        ];
    }

    public function getLinksBarShowAll(): array
    {
        return [
            ['icon' => '<i class="fa-solid fa-plus"></i>', 'label' => lang('backend/categories.linksBar.add'), 'route' => 'backend/categories/add'],
            $this->getCategoryTreeLink(),
        ];
    }

    public function getLinksBarAdd(): array
    {
        return [
            ['icon' => '<i class="fa-solid fa-list"></i>', 'label' => lang('backend/categories.linksBar.showAll'), 'route' => 'backend/categories/showAll'],
            $this->getCategoryTreeLink(),
        ];
    }

    public function getLinksBarEdit(?string $uuid = null): array
    {
        if ( ! $uuid):
            return [];
        endif;

        return [
            ['icon' => '<i class="fa-solid fa-list"></i>', 'label' => lang('backend/categories.linksBar.showAll'), 'route' => 'backend/categories/showAll'],
            ['icon' => '<i class="fa-solid fa-plus"></i>', 'label' => lang('backend/categories.linksBar.add'), 'route' => 'backend/categories/add'],
            ['icon' => '<i class="fa-solid fa-eye"></i>', 'label' => lang('backend/categories.linksBar.show'), 'route' => "backend/categories/show/{$uuid}"],
            $this->getCategoryTreeLink(),
        ];
    }

    public function getLinksBarShow(?string $uuid = null): array
    {
        if ( ! $uuid):
            return [];
        endif;

        return [
            ['icon' => '<i class="fa-solid fa-list"></i>', 'label' => lang('backend/categories.linksBar.showAll'), 'route' => 'backend/categories/showAll'],
            ['icon' => '<i class="fa-solid fa-plus"></i>', 'label' => lang('backend/categories.linksBar.add'), 'route' => 'backend/categories/add'],
            ['icon' => '<i class="fa-solid fa-pen"></i>', 'label' => lang('backend/categories.linksBar.edit'), 'route' => "backend/categories/edit/{$uuid}"],
            $this->getCategoryTreeLink(),
        ];
    }

    /* JAVASCRIPT ---------------------------------------------------------------------------------- */

    public function getJsShowAll(): array
    {
        $lang = setting('Backend\General')->language;
        $locale = ($lang === 'en' ? 'default' : $lang);

        return [
            ['id' => 'flatpickr-js', 'path' => 'assets/vendor/flatpickr/js/flatpickr.min.js', 'position' => 'before', 'target' => 'categories-js'],
            ['id' => $locale . '-js', 'path' => 'assets/vendor/flatpickr/js/' . $locale . '.js', 'position' => 'after', 'target' => 'flatpickr-js'],
        ];
    }

    public function getJsShow(): array
    {
        return [
            ['id' => 'html2pdf-js', 'path' => 'assets/vendor/html2pdf/js/html2pdf.bundle.min.js', 'position' => 'before', 'target' => 'categories-js'],
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

    /* Inserire qui configurazioni di componenti specifici del modulo Categories. */
}
