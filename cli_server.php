<?php
namespace noFlash\ImageODS;

error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once("vendor/autoload.php");
use noFlash\Shout\Shout;


$logger = new Shout();

//if(PHP_OS !== 'Linux') {
//    $logger->fatal("ImageODS works only on Linux OS (you're using ".PHP_OS.")");
//    die(1);
//}

$testDisc = new VirtualDisc('/Users/grzegorz/Downloads/test.iso', null, VirtualDisc::TYPE_CD);

$logger->info("Starting CLI server...");
$server = new Server("01:23:45:67:89:ab", '::', null, 8080, $logger);
$server->addDisc($testDisc);
$server->run();