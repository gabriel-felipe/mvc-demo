<?php
error_reporting(E_ALL);
ini_set("display_errors", "on");
require_once("autoload.php");
$rootPath = dirname(__FILE__);

Mvc\App\Registry::set("rootPath",$rootPath);

$routesFile = file_get_contents("Config/routes.json");

if (!isset($_GET['url'])) {
    $_GET['url'] = "";
}
$router = new Mvc\App\Router($_GET['url']);

if ($routes = json_decode($routesFile,true)) {
    $router->setRoutes($routes);
}

$router->exec();
