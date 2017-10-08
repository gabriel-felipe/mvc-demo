<?php
namespace Mvc\Modules\Meumodulo\Controller;
use Mvc\Modules\Meumodulo\Model as Model;
class Index extends \Mvc\App\Controller
{

    function index()
    {
        $model = new Model\MinhaModel();

        $this->getView()->titulo = $model->getTitulo();
    }
}

?>
