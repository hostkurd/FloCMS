<?php
class PagesController extends Controller{
    public function __construct(array $data = array())
    {
        parent::__construct($data);
        $this->model = new PagesModel();
    }

    public function index(){ 
        $this->data=[
            'title'=> 'The Most lightweight PHP Framework',
            'test'=> 'This is Test Parameter',
            'users'=>$this->model->getUsers()
        ];
    }

}