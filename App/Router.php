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
        /* Quebra a url da requisição em pedaços. */
        $pieces = explode("/",$url);
        $params = array();
        foreach ($this->getRoutes() as $urlRoute => $params) {
            /* Se for uma correspondência exata, retorna os parâmetros */
            if ($url === $urlRoute) {
                return $params;
            }

            /* Quebra a url da rota que está sendo testada em pedaços, para comparação. */
            $piecesRoute = explode("/",$urlRoute);

            /**
             * Se a url da requisição tiver uma quantidade diferente de pedaços da url sendo comparada,
             * Já pula para a próxima, pois um match é impossível
             * Ex: modulo/controller/action e modulo/controller => Match impossível, pois uma tem 3 pedaços e a outra só 2.
             */

            if (count($piecesRoute) !== count($pieces)) {
                continue;
            }

            /*
            * Busca as posições que são ocupadas por parâmetros.
            */
            $paramsPositions = $this->getParamsPositions($piecesRoute);
            $matchAllPieces = true;
            $replaces = array();

            /*
            * Começa a checagem pedaço a pedaço vendo se combinam.
            * Caso algum pedaço falhe a combinação o foreach é interrompido e a rota não serve para essa url.
            */
            foreach ($pieces as $key => $piece) {
                $equivalentRoutePiece = $piecesRoute[$key];
                /* Se os dois pedaços são iguais, então é um match no pedaço, pula a checagem para o próximo pedaço. */
                if ($equivalentRoutePiece === $piece) {
                    continue;
                }

                /*
                * Checa se o pedaço sendo checado é um parâmetro variável,
                * de acordo com o retorno obtido pela função getParamsPositions
                * Se for um parâmetro variável, vê se o pedaço da url da requisição
                * combina com a regex do pedaço da rota, se sim é um match.
                */
                $regexIndex = array_search($key,$paramsPositions);
                if ($regexIndex !== false) {
                    $regex = rtrim($equivalentRoutePiece,"}");
                    $regex = ltrim($regex,"{");
                    $regex = "/$regex/";
                    /*
                    * Se for um match, popula um array com replaces a serem feitos.
                    * Cada parâmetro variável recebe um nome de ${indice}, então o array diz que
                    * todos os lugares onde na rota estiver escrito ${indice}, devem ser substituidos
                    * pelo valor presente na url, populando todos os parâmetros corretamente.
                    */
                    if (preg_match($regex,$piece)) {
                        $replace = "$".($regexIndex+1);
                        $replaces[$replace] = $piece;
                        continue;
                    }
                }

                /*
                * Se não é igual, nem se aplica à regex, então o pedaço não combina.
                * O primeiro pedaço que não combina, automaticamente encerra o foreach.
                * E seta a variável dizendo que nem todos os pedaços combinam.
                */
                $matchAllPieces = false;
                break;
            }

            /*
            * Se todos os pedaços são uma combinação,
            * substitui os pedaços variáveis pelos valores da url
            * de acordo com o array de replaces populado anteriormente.
            */
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

    /**
     * routeToParams
     * Recebe uma string e quebra nas / criando um array associativo
     * array (
     *    "module"=>module,
     *    "controller" => controller,
     *    "action" => action
     * )
     * Caso controller ou action não sejam fornecidos, é usado index como padrão.
     *
     * @param (string) $route -> module/controller/action
     *
     * @return (array) um array associativo com os parâmetros da rota encontrada.
     */
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

    /**
     * getParamsPositions
     *
     * Varre cada pedaço da rota checando quais são ocupados por regex, parâmetros.
     *
     * @pieces (array) Array de pedaços de uma rota.
     *
     * @return array de índices ocupados por parâmetros variáveis.
     */
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
