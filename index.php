<?php
error_reporting(E_ALL);
ini_set("display_errors", "on");
require_once("autoload.php");
$rootPath = dirname(__FILE__);

Mvc\App\Registry::set("rootPath",$rootPath);

$router = new Mvc\App\Router($_GET['url']);
$router->exec();
