<?php
class PagesController extends Controller{
    public function __construct(array $data = array())
    {
        parent::__construct($data);
        
        //Warning: Database should be configured in order to use Model
        //$this->model = new PagesModel();
    }

    public function index(){ 
        $this->data=[
            'title'=> 'The Most lightweight PHP Framework',
            'test'=> 'This is Test Parameter',
            //'users'=>$this->model->getUsers()
        ];
    }

}