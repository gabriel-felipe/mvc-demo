<?php
/**
 *
 */
namespace Mvc\App;
class Router
{
    protected $url;
    function __construct($url)
    {
        $this->setUrl($url);
    }


    function exec()
    {
        $pieces = explode("/",trim($this->getUrl(),"/"));
        $modulo = ucfirst($pieces[0]);
        $controller = "Index";
        if (isset($pieces[1])) {
            $controller = ucfirst($pieces[1]);
        }

        $action = "index";
        if (isset($pieces[2])) {
            $action = $pieces[2];
        }
        Registry::set("modulo",$modulo);
        Registry::set("action",$action);
        Registry::set("controller",$controller);

        $class = "Mvc\\Modules\\".$modulo."\\Controller\\".$controller;

        if (class_exists($class)) {
            $obj = new $class;
            $obj->dispatch($action);
        } else {
            throw new \Exception("Controller $controller não encontrado no módulo $modulo. ", 1);
        }
    }
    /**
     * Get the value of Url
     *
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the value of Url
     *
     * @param mixed url
     *
     * @return self
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

}
