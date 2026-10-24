<?php declare(strict_types = 1);

namespace Modules\Backend\Warehouse\Libraries;

/** Segnala un esito del commit non ricostruibile in sicurezza dal solo staging. */
class WarehouseRecoveryException extends \RuntimeException
{
}
