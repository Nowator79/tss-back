<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Godra\Api\Helpers\Nomenclature;

var_dump(Nomenclature::importFromXls("/var/www/sprut/tss/local/tmp/erpcodes.xlsx"));

?>