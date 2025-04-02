<?php
class PagesController extends Controller{
    public function __construct(array $data = array())
    {
        parent::__construct($data);
        $this->model = new PagesModel();
    }

    public function index(){ 
        $this->data['title'] = 'Pages Index Title';
        $this->data['temp'] = 'THis is Temp';

        // $this->data=[
        //     'title'=> 'This is Page Title',
        //     'temp'=> 'this is temp'
        // ];
    }

}