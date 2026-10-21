<?php declare(strict_types = 1);

namespace Modules\Backend\Products\Libraries;

/**
 * Prepara barre dei collegamenti e asset specifici del modulo Products.
 */
class ProductsClass
{
    public function getLinksBarShowAll(): array
    {
        return [
            ['icon' => '<i class="fa-solid fa-plus"></i>', 'label' => lang('backend/products.linksBar.add'), 'route' => 'backend/products/add'],
        ];
    }

    public function getLinksBarAdd(): array
    {
        return [
            ['icon' => '<i class="fa-solid fa-list"></i>', 'label' => lang('backend/products.linksBar.showAll'), 'route' => 'backend/products/showAll'],
        ];
    }

    public function getLinksBarEdit(?string $uuid = null): array
    {
        if ( ! $uuid):
            return [];
        endif;

        return [
            ['icon' => '<i class="fa-solid fa-list"></i>', 'label' => lang('backend/products.linksBar.showAll'), 'route' => 'backend/products/showAll'],
            ['icon' => '<i class="fa-solid fa-plus"></i>', 'label' => lang('backend/products.linksBar.add'), 'route' => 'backend/products/add'],
            ['icon' => '<i class="fa-solid fa-eye"></i>', 'label' => lang('backend/products.linksBar.show'), 'route' => "backend/products/show/{$uuid}"],
        ];
    }

    public function getLinksBarShow(?string $uuid = null): array
    {
        if ( ! $uuid):
            return [];
        endif;

        return [
            ['icon' => '<i class="fa-solid fa-list"></i>', 'label' => lang('backend/products.linksBar.showAll'), 'route' => 'backend/products/showAll'],
            ['icon' => '<i class="fa-solid fa-plus"></i>', 'label' => lang('backend/products.linksBar.add'), 'route' => 'backend/products/add'],
            ['icon' => '<i class="fa-solid fa-pen"></i>', 'label' => lang('backend/products.linksBar.edit'), 'route' => "backend/products/edit/{$uuid}"],
        ];
    }

    public function getJsShowAll(): array
    {
        $lang = setting('Backend\General')->language;
        $locale = ($lang === 'en' ? 'default' : $lang);

        return [
            ['id' => 'flatpickr-js', 'path' => 'assets/vendor/flatpickr/js/flatpickr.min.js', 'position' => 'before', 'target' => 'products-js'],
            ['id' => $locale . '-js', 'path' => 'assets/vendor/flatpickr/js/' . $locale . '.js', 'position' => 'after', 'target' => 'flatpickr-js'],
        ];
    }

    public function getJsAdd(): array
    {
        return [
            ['id' => 'tom-select-js', 'path' => 'assets/vendor/tom-select/js/tom-select.complete.min.js', 'position' => 'before', 'target' => 'products-js'],
        ];
    }

    public function getJsEdit(): array
    {
        return $this->getJsAdd();
    }

    public function getJsShow(): array
    {
        return [
            ['id' => 'html2pdf-js', 'path' => 'assets/vendor/html2pdf/js/html2pdf.bundle.min.js', 'position' => 'before', 'target' => 'products-js'],
        ];
    }

    public function getCssShowAll(): array
    {
        return [
            ['id' => 'flatpickr-css', 'path' => 'assets/vendor/flatpickr/css/flatpickr.min.css', 'position' => 'before', 'target' => 'backend-css'],
        ];
    }

    public function getCssAdd(): array
    {
        return [
            ['id' => 'tom-select-css', 'path' => 'assets/vendor/tom-select/css/tom-select.bootstrap5.min.css', 'position' => 'before', 'target' => 'backend-css'],
        ];
    }

    public function getCssEdit(): array
    {
        return $this->getCssAdd();
    }
}
