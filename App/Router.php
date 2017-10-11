<?php
/**
 * Classe router. Tem objetivo de receber a url e mapear a requisição para o controller correto.
 * Também é utilizado para gerar uma url a partir de uma série de parâmetros.
 * TODO: Atualizar comentários, classe foi reescrita e os comentários não estão muito úteis.
 */
namespace Mvc\App;
use Mvc\App\Router\Exception as Exception;
class Router
{
    protected $url;
    protected $routes;
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

        $params = $this->urlToParams();

        if (!$params) {
            throw new Exception("Não foi encontrada uma rota compatível com a url '".$this->getUrl()."'");
        }

        if (!isset($params['route'])) {
            throw new Exception("O parâmetro route é obrigatório em todas as urls.", 1);
        }

        $route = $params['route'];
        unset($params['route']);
        foreach ($params as $key => $value) {
            $_GET[$key] = $value;
        }

        $routeParams = $this->routeToParams($route);
        $module = ucfirst($routeParams['module']);
        $action = lcfirst($routeParams['action']);
        $controller = ucfirst($routeParams['controller']);

        /**
        * Seta no registro os valores do módulo, controller e action
        * Para que seja possível saber seus valores em outras classes se necessário
        * Útil para checagens futuras, como em um sistema ACL
        */
        Registry::set("module",$module);
        Registry::set("action",$action);
        Registry::set("controller",$controller);

        /**
        * Com os dados encontrados, escreve o nome da classe segundo o padrão adotado.
        */
        $class = "Mvc\\Modules\\".$module."\\Controller\\".$controller;

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
            throw new Exception("Controller $controller não encontrado no módulo $module. ", 1);
        }
    }

    /**
     * urlToParams
     *
     * Analisa todas as rotas configuradas buscando por uma combinação com self::$url
     *
     * @return (array) um array associativo com os parâmetros da rota encontrada.
     * Examplo:
     * array(
     *   "module" => "MeuModulo"
     *   "controller"=> "ExampleController",
     *   "action" => "index",
     *   "customParam" => "defaultValue"
     * );
     *
     */
    public function urlToParams()
    {
        $url = $this->getUrl();
        $pieces = explode("/",$url);
        $params = array();
        foreach ($this->getRoutes() as $urlRoute => $params) {
            /* Se for uma correspondência exata, retorna os parâmetros */
            if ($url === $urlRoute) {
                return $params;
            }

            $piecesRoute = explode("/",$urlRoute);

            /**
             * Se a url da requisição tiver uma quantidade diferente de pedaços da url sendo comparada,
             * Já pula para a próxima, pois um match é impossível
             */

            if (count($piecesRoute) !== count($pieces)) {
                continue;
            }
            $paramsPositions = $this->getParamsPositions($piecesRoute);
            $matchAllPieces = true;
            $replaces = array();
            foreach ($pieces as $key => $piece) {
                $equivalentRoutePiece = $piecesRoute[$key];
                /* Se os dois pedaços são iguais, então é um match no pedaço, pula a checagem para o próximo pedaço. */
                if ($equivalentRoutePiece === $piece) {
                    continue;
                }
                $regexIndex = array_search($key,$paramsPositions);
                if ($regexIndex !== false) {
                    $regex = rtrim($equivalentRoutePiece,"}");
                    $regex = ltrim($regex,"{");
                    $regex = "/$regex/";
                    if (preg_match($regex,$piece)) {
                        $replace = "$".($regexIndex+1);
                        $replaces[$replace] = $piece;
                        continue;
                    }
                }
                $matchAllPieces = false;
                break;
            }

            if ($matchAllPieces) {
                if ($replaces) {
                    $params = $this->replaceRegexIntoValues($replaces,$params);
                }

                return $params;
            }


        }
        return false;
    }

    /**
     * url
     *
     * Recebe uma rota e parâmetros adicionais e retorna a url de acordo com as rotas definidas.
     *
     * @route (string) Rota no formato modulo/controller/action
     * @params (array) Array associativo com parâmetros adicionais para a url
     * 
     */
    public function url($route,$params=array())
    {
        $urlParams = $this->routeToParams($route);
        $adicionalParams = $params;
        $urlAllParams = array_merge($urlParams,$adicionalParams);

        foreach ($this->getRoutes() as $routeUrl => $route) {
            $routeParams = $this->routeToParams($route['route']);
            $mapRegex = $this->getMapRegex($routeUrl);
            $routeAllParams = array_merge($routeParams,$route);

            unset($routeAllParams['route']);
            if (count($routeAllParams) !== count($urlAllParams)) {
                continue;
            }

            $matchAllParams = true;
            $regexResult = array();
            foreach ($routeAllParams as $key => $value) {
                $urlValue = (isset($urlAllParams[$key])) ? $urlAllParams[$key] : null;

                if ($value === $urlValue) {
                    continue;
                }

                if (array_key_exists($value,$mapRegex)) {
                    $regex = rtrim($mapRegex[$value],"}");
                    $regex = ltrim($regex,"{");
                    $regex = "/$regex/";
                    if (preg_match($regex,$urlValue)) {
                        $regexResult[$value] = $urlValue;
                        continue;
                    }
                }

                $matchAllParams = false;
                break;

            }

            if ($matchAllParams) {

                $url = explode("/",$routeUrl);

                foreach ($regexResult as $paramKey => $value) {
                    $paramKey = (int)str_replace("$","",$paramKey) + 1;
                    $url[$paramKey] = $value;
                }
                return implode("/",$url);

            }


        }
        $url = "?url=".implode("/",$urlParams)."&".http_build_query($params);
        return $url;
    }

    protected function getMapRegex($url)
    {
        $urlPieces = explode("/",$url);
        $paramsPositions = $this->getParamsPositions($urlPieces);
        $map = array();
        foreach ($paramsPositions as $key => $index) {
            $map["$".($key+1)] = $urlPieces[$index];
        }
        return $map;
    }

    protected function routeToParams($route)
    {
        $route = explode("/",$route);
        $result = array(
            "module"=>$route[0],
            "controller"=>"Index",
            "action"=>"index"
        );

        if (isset($route[1])) {
            $result['controller'] = $route[1];
        }

        if (isset($route[2])) {
            $result['action'] = $route[2];
        }
        return $result;
    }

    protected function replaceRegexIntoValues($regexValuesMap,$params)
    {
        foreach ($params as $key => $value) {
            $params[$key] = str_replace(array_keys($regexValuesMap),array_values($regexValuesMap),$value);
        }
        return $params;
    }

    protected function getParamsPositions($pieces)
    {
        $positions = array();
        foreach ($pieces as $k => $piece) {
            $piece = trim($piece);
            if (preg_match('/^\{[^}]+\}$/',$piece)) {
                $positions[] = $k;
            }
        }
        return $positions;
    }

    protected function matchUrlToRoute($url,$params)
    {

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


    /**
     * Get the value of Routes
     *
     * @return mixed
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Set the value of Routes
     *
     * @param mixed routes
     *
     * @return self
     */
    public function setRoutes($routes)
    {
        $this->routes = $routes;

        return $this;
    }

}
