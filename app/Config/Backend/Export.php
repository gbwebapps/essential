<?php declare(strict_types = 1);

namespace Config\Backend;

use CodeIgniter\Config\BaseConfig;

/**
 * Configura l'elaborazione a blocchi del componente globale Export CSV.
 */
class Export extends BaseConfig
{
    /** Numero di record elaborati per ciascuna richiesta di esportazione. */
    public const CHUNK_SIZE = 5;
}
