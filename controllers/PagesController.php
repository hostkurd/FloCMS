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
        if ($this->request->isMethod('POST') && $this->request->input('action') === 'fix_app_url') {
            $this->fixAppUrl();
        }

        $this->data['appUrlWarning'] = null;
        $this->data['suggestedAppUrl'] = null;
        $this->data['canFixAppUrl'] = $this->canFixAppUrl();
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

    /**
     * The one-click APP_URL fix writes to .env, so it is only offered on a
     * local install viewed from the same machine.
     */
    private function canFixAppUrl(): bool
    {
        $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        return Env::get('APP_ENV') === 'local'
            && in_array($remote, ['127.0.0.1', '::1'], true)
            && is_writable(ROOT . DS . '.env');
    }

    private function fixAppUrl(): void
    {
        $url = AppUrlValidator::getSuggestedUrl($_SERVER);

        if (!$this->canFixAppUrl()
            || !filter_var($url, FILTER_VALIDATE_URL)
            || preg_match('/[\s"\'#]/', $url)) {
            return;
        }

        $file = ROOT . DS . '.env';
        $env  = (string) file_get_contents($file);
        $line = 'APP_URL=' . $url;
        $env  = preg_match('/^APP_URL=.*$/m', $env)
            ? (string) preg_replace('/^APP_URL=.*$/m', $line, $env, 1)
            : rtrim($env) . PHP_EOL . $line . PHP_EOL;

        file_put_contents($file, $env, LOCK_EX);

        \FloCMS\Core\Router::redirect($url);
    }
}
