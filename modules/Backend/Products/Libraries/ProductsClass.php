<?php declare(strict_types = 1);

namespace Modules\Backend\Products\Libraries;

/**
 * Prepara le barre dei collegamenti e gli asset specifici del modulo Products.
 */
class ProductsClass
{
    /* LINKS BAR ----------------------------------------------------------------------------------- */

    /**
     * Restituisce i collegamenti disponibili nella lista dei prodotti.
     *
     * @return array<int, array<string, string>>
     */
    public function getLinksBarShowAll(): array
    {
        return [
            ['icon' => '<i class="fa-solid fa-plus"></i>', 'label' => lang('backend/products.linksBar.add'), 'route' => 'backend/products/add'],
        ];
    }

    /**
     * Restituisce i collegamenti disponibili durante l'inserimento.
     *
     * @return array<int, array<string, string>>
     */
    public function getLinksBarAdd(): array
    {
        return [
            ['icon' => '<i class="fa-solid fa-list"></i>', 'label' => lang('backend/products.linksBar.showAll'), 'route' => 'backend/products/showAll'],
        ];
    }

    /**
     * Restituisce i collegamenti disponibili durante la modifica.
     *
     * @param string|null $uuid Identificativo del prodotto corrente
     * @return array<int, array<string, string>>
     */
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

    /**
     * Restituisce i collegamenti disponibili nella scheda di dettaglio.
     *
     * @param string|null $uuid Identificativo del prodotto corrente
     * @return array<int, array<string, string>>
     */
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

    /* JAVASCRIPT ---------------------------------------------------------------------------------- */

    /**
     * Restituisce gli script richiesti dalla lista dei prodotti.
     *
     * @return array<int, array<string, string>>
     */
    public function getJsShowAll(): array
    {
        $lang = service('request')->getLocale();
        $locale = ($lang === 'en' ? 'default' : $lang);

        return [
            ['id' => 'flatpickr-js', 'path' => 'assets/vendor/flatpickr/js/flatpickr.min.js', 'position' => 'before', 'target' => 'products-js'],
            ['id' => $locale . '-js', 'path' => 'assets/vendor/flatpickr/js/' . $locale . '.js', 'position' => 'after', 'target' => 'flatpickr-js'],
        ];
    }

    /**
     * Restituisce gli script richiesti dal form di inserimento.
     *
     * @return array<int, array<string, string>>
     */
    public function getJsAdd(): array
    {
        return [
            ['id' => 'tom-select-js', 'path' => 'assets/vendor/tom-select/js/tom-select.complete.min.js', 'position' => 'before', 'target' => 'products-js'],
        ];
    }

    /**
     * Restituisce gli script richiesti dal form di modifica.
     *
     * @return array<int, array<string, string>>
     */
    public function getJsEdit(): array
    {
        return $this->getJsAdd();
    }

    /**
     * Restituisce gli script richiesti dalla scheda di dettaglio.
     *
     * @return array<int, array<string, string>>
     */
    public function getJsShow(): array
    {
        return [
            ['id' => 'html2pdf-js', 'path' => 'assets/vendor/html2pdf/js/html2pdf.bundle.min.js', 'position' => 'before', 'target' => 'products-js'],
        ];
    }

    /* CSS ----------------------------------------------------------------------------------------- */

    /**
     * Restituisce i fogli di stile richiesti dalla lista dei prodotti.
     *
     * @return array<int, array<string, string>>
     */
    public function getCssShowAll(): array
    {
        return [
            ['id' => 'flatpickr-css', 'path' => 'assets/vendor/flatpickr/css/flatpickr.min.css', 'position' => 'before', 'target' => 'backend-css'],
        ];
    }

    /**
     * Restituisce i fogli di stile richiesti dal form di inserimento.
     *
     * @return array<int, array<string, string>>
     */
    public function getCssAdd(): array
    {
        return [
            ['id' => 'tom-select-css', 'path' => 'assets/vendor/tom-select/css/tom-select.bootstrap5.min.css', 'position' => 'before', 'target' => 'backend-css'],
        ];
    }

    /**
     * Restituisce i fogli di stile richiesti dal form di modifica.
     *
     * @return array<int, array<string, string>>
     */
    public function getCssEdit(): array
    {
        return $this->getCssAdd();
    }

    /* OPTIONS ------------------------------------------------------------------------------------- */

    /* Inserire qui eventuali configurazioni di opzioni specifiche del modulo Products. */

    /* COMPONENTI FUTURI --------------------------------------------------------------------------- */

    /* Inserire qui configurazioni di componenti specifici del modulo Products. */
}
