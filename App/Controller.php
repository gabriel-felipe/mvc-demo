<?php

namespace Mvc\App;


abstract class Controller
{
    protected $view;
    protected $modulo = null;
    protected $controllerName = null;
    function __construct()
    {
        $view = new View();
        $this->setView($view);
        /*
            Pegando o módulo do controller através do nome da class
            $class[0] = Mvc
            $class[1] = Modules
            $class[2] = NomedoModulo
            $class[3] = Controller
            $class[4,5,6] = Nome do controller
        */
        $class = get_class($this);
        $class = explode("\\",$class);
        $modulo = $class[2];

        /* Pegando o controller da class atual */
        $controller = implode("/",array_slice($class,4));

        $this->setControllerName($controller);

        $this->setModulo($modulo);
    }

    function dispatch($action)
    {
        $view = $this->getView();
        if (method_exists($this,$action)) {
            if (!$view->getPath()) {
                $viewPath = $this->getViewPath($action);
                $view->setPath($viewPath);
                $this->{$action}();
                $content = $view->render();
                echo $content;
            }
        }
    }

    function getViewPath($action)
    {

        $modulo = $this->getModulo();
        $controller = $this->getControllerName();
        return Registry::get("rootPath")."/Modules/$modulo/View/$controller/$action.html";
    }

    /**
     * Get the value of View
     *
     * @return mixed
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * Set the value of View
     *
     * @param mixed view
     *
     * @return self
     */
    public function setView(View $view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Get the value of Modulo
     *
     * @return mixed
     */
    public function getModulo()
    {
        return $this->modulo;
    }

    /**
     * Set the value of Modulo
     *
     * @param mixed modulo
     *
     * @return self
     */
    public function setModulo($modulo)
    {
        $this->modulo = $modulo;

        return $this;
    }


    /**
     * Get the value of Controller Name
     *
     * @return mixed
     */
    public function getControllerName()
    {
        return $this->controllerName;
    }

    /**
     * Set the value of Controller Name
     *
     * @param mixed controllerName
     *
     * @return self
     */
    public function setControllerName($controllerName)
    {
        $this->controllerName = $controllerName;

        return $this;
    }

}
