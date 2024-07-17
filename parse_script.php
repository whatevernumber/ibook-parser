<?php
require_once 'vendor/autoload.php';

$labirintHandler = new \IbookParser\Services\LabirintService();
$labirintHandler->handle();
