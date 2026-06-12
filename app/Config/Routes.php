<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

/* Definizione del placeholder personalizzato per UUID v1-v5 */
$routes->addPlaceholder('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}');

/* Definizione del placeholder personalizzato per token setPassword */
$routes->addPlaceholder('token', '[0-9a-f]{32}');

/* Caricamento delle rotte suddivise per ambiente */
if (is_file(APPPATH . 'Config/Backend/Routes.php')) {
    require APPPATH . 'Config/Backend/Routes.php';
}

if (is_file(APPPATH . 'Config/Frontend/Routes.php')) {
    require APPPATH . 'Config/Frontend/Routes.php';
}