<?php
namespace FloCMS\Controllers;

use FloCMS\Core\Controller;
use FloCMS\Models\PagesModel;
//use HostKurd\Flocms\Models\UsersModel;

class PagesController extends Controller{
    public function __construct(array $data = array())
    {
        parent::__construct($data);
        
        //Warning: Database should be configured in order to use Model
        $this->model = new PagesModel();
    }

    public function index(){ 
        // $name = \HostKurd\Flocms\Lib\Input::str($this->request->input('name'));
        // $age  = \HostKurd\Flocms\Lib\Input::int($this->request->input('age'));
        $this->data=[
            'title'=> 'The Most lightweight PHP Framework',
            'test'=> 'This is Test Parameter',
            //'users'=>$this->model->getUsers()
        ];
    }

}