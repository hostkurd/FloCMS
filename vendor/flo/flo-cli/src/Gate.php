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
        //$colors = new Colors();
        $Core = new Core($this->Command);
        $num_params = count($this->Command);
        if($num_params<2){
            return "Flo CLI Version 1.0\nFloCMS Command line interface.";
        }
        
        switch($this->Command[1]){
            case '--v':
                return 'Version 1.8';
                break;
            case '--help':
                $Help = new Help();
                return print_r($Help -> HelpData(), true);
                break;
            case 'create:controller':
                return $Core -> CreateController();
                break;
            case 'create:model':
                return $Core -> CreateModel();
                break;
            case 'create:view':
                return $Core -> CreateView();
                break;
            default:
                return "Command not valid \r\nRun \"php flo --help\" to view available FloCMS Commands.";
                break;
        }
    }



    
}
