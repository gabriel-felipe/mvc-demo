<?php
/**
 * Classe router. Tem objetivo de receber a url e mapear a requisição para o controller correto.
 */
namespace Mvc\App;
class Router
{
    protected $url;

    /**
     * __construct
     *
     * Recebe a url e seta ela na propriedade protegida
     *
     * @url (string) url a ser analisada
     * @return null
     */
    function __construct($url)
    {
        $this->setUrl($url);
    }

    /**
     * exec
     *
     * Analisa a url setada na propriedade self::$url
     * encontra qual module controller action deve executar.
     * @return null
     *
     */
    function exec()
    {

        /**
        * Remove uma possível / no final da url e quebra a url por "/"
        * resultando em um array como:
        * $pieces[0] => "Modulo"
        * $pieces[1] => "Controller"
        * $pieces[2] => "Action"
        */
        $pieces = explode("/",trim($this->getUrl(),"/"));

        /**
        * Coloca a primeira letra do módulo para maiúscula,
        * para seguir o padrão de nomenclatura.
        */
        $modulo = ucfirst($pieces[0]);

        /**
        * Define controller padrão como default
        */
        $controller = "Index";

        /**
        * Caso esteja definido o índice 1 do array $pieces,
        * isso significa que o controller foi especificado,
        * então atualizamos o valor da variável $controller
        */
        if (isset($pieces[1])) {
            $controller = ucfirst($pieces[1]);
        }

        /**
        * Define action padrão como default
        */
        $action = "index";
        /**
        * Caso esteja definido o índice 2 do array $pieces,
        * isso significa que a action foi especificada,
        * então atualizamos o valor da variável $action
        */
        if (isset($pieces[2])) {
            $action = $pieces[2];
        }

        /**
        * Seta no registro os valores do módulo, controller e action
        * Para que seja possível saber seus valores em outras classes se necessário
        * Útil para checagens futuras, como em um sistema ACL
        */
        Registry::set("modulo",$modulo);
        Registry::set("action",$action);
        Registry::set("controller",$controller);

        /**
        * Com os dados encontrados, escreve o nome da classe segundo o padrão adotado.
        */
        $class = "Mvc\\Modules\\".$modulo."\\Controller\\".$controller;

        /**
        * Se o controller existir, instanciamos ele e disparamos a action encontrada.
        */
        if (class_exists($class)) {
            $obj = new $class;
            $obj->dispatch($action);
        } else {
            /**
            * Se não encontrado, disparamos uma exceção parando o código.
            */
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
