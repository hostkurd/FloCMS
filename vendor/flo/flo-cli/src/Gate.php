<?php
/**
 * Gate Class for FLO-CLI
 */
final class Gate
{
    private $Command;

    public function __construct($data){
        $this->Command = $data;
    }

    public function Execute(){
        switch($this->Command[1]){
            case '--v':
                return 'Version 1.8';
                break;
            case 'Make:Model':
                return 'Create Model Command';
                break;
            case '--help':
                $Help = new Help();
                return print_r($Help -> HelpData(), true);
                break;
            case 'make:controller':
                $Core = new Core($this->Command[2]);
                return $Core -> CreateController();
                break;
            default:
                return 'Command not valid';
                break;
        }
    }



    
}
