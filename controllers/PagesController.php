<?php
namespace FloCMS\Controllers;

use FloCMS\Core\App;
use FloCMS\Core\Controller;
use FloCMS\Models\PagesModel;
use FloCMS\Core\AppUrlValidator;
use FloCMS\Core\Env;

class PagesController extends Controller{

    /**
     * Created on first use, so pages that never query the database (like the
     * welcome page) work before a database is set up.
     */
    protected function model(): PagesModel
    {
        return $this->model ??= new PagesModel();
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

        // Shown as a setup card on the welcome page; the driver detail only in debug mode
        $this->data['dbStatus'] = App::dbStatus();
        $this->data['showDbDetail'] = Env::get('APP_DEBUG') === true;
        
        // $name = \HostKurd\Flocms\Lib\Input::str($this->request->input('name'));
        // $age  = \HostKurd\Flocms\Lib\Input::int($this->request->input('age'));
        $this->data['title'] = 'The Most lightweight PHP Framework';
        $this->data['test']  = 'This is Test Parameter';
    }

}
