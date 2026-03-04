<?php
namespace HostKurd\Flocms\Lib;
use HostKurd\Flocms\Lib\Http\Request;

class Controller{

    protected $data;
    protected $model;
    protected $params;
    protected Request $request;

    public function setRequest(Request $request): void {
        $this->request = $request;
    }
    
    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }


    public function __construct($data=array()){
        $this->data = $data;
        $this->params = App::getRouter()->getParams();
    }



}