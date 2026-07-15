<?php 

namespace Config;

/* Caricamento del file fisico */
if (is_file(APPPATH . 'Config/Backend/Email.php')) {
    require APPPATH . 'Config/Backend/Email.php';
}

/* Creiamo il "ponte" che rende disponibile \Config\Email */
class Email extends \Config\Backend\Email {}