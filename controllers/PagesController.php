<?php
namespace FloCMS\Controllers;

use FloCMS\Core\Controller;
use FloCMS\Models\PagesModel;
use FloCMS\Core\AppUrlValidator;
use FloCMS\Core\Env;
//use HostKurd\Flocms\Models\UsersModel;

class PagesController extends Controller{
    public function __construct(array $data = array())
    {
        parent::__construct($data);
        
        //Warning: Database should be configured in order to use Model
        $this->model = new PagesModel();
    }

    public function index(){ 
        $this->data['appUrlWarning'] = null;
        $this->data['suggestedAppUrl'] = null;
        $mismatch = AppUrlValidator::detectStrictMismatch(
            (string) Env::get('APP_URL'),
            $_SERVER
        );
        if ($mismatch !== null) {
            $this->data['appUrlWarning'] = $mismatch['message'];
            $this->data['suggestedAppUrl'] = AppUrlValidator::getSuggestedUrl($_SERVER);
        }
        
        // $name = \HostKurd\Flocms\Lib\Input::str($this->request->input('name'));
        // $age  = \HostKurd\Flocms\Lib\Input::int($this->request->input('age'));
        $this->data['title'] = 'The Most lightweight PHP Framework';
        $this->data['test']  = 'This is Test Parameter';
    }

}