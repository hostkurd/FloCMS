<?php
/**
 * Gate Class for FLO-CLI
 */
final class Gate
{
    private $Command;
    private $AllCommands;

    public function __construct($data){
        $this->Command = $data;
        $jsonData = json_decode(file_get_contents(__DIR__."/data.json"), true);
        $this->AllCommands = $jsonData['cli-data']['all-commands'];
    }

    public function Execute(): void{
        //$colors = new Colors();
        $Help = new Help();
        $Core = new Core($this->Command);
        $num_params = count($this->Command);
        if($num_params<2){
            echo "Flo CLI Version 1.0\nFloCMS Command line interface.";
        }
        
        switch($this->Command[1]){
            case '--v':
                echo 'Version 1.8';
                break;
            case '--help':
                echo $Core -> ShowAllCommands();
                break;
            case 'create:controller':
                echo $Core -> CreateController();
                break;
            case 'create:model':
                echo $Core -> CreateModel();
                break;
            case 'create:view':
                echo $Core -> CreateView();
                break;
            default:
                echo "Command not valid \r\nRun \"php flo --help\" to view available FloCMS Commands.";
                break;
        }
    }
    
}
