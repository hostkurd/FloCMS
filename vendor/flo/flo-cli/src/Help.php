<?php

Class Help{
    public $Param;
    public $Description;

    public function __construct($param  = NULL, $desc  = NULL){
        $this->Param = $param;
        $this->Description = $desc;
    }
    
    public function HelpData(): void{
        $array =  [
            new Help ("--v\t\t", "Displays the Version of the FLO Framework."),
            new Help ("--help\t\t", "Displays the available Commands of Flo-CLI."),
            new Help ("make:model\t", "Creates model element in the script"),
            new Help ("make:controller\t", "Create Controller Class for the Object")
        ];

        echo "Options:\r\n";
        foreach($array as $value){
            echo $value->Param . " ". $value->Description . "\r\n";
        }
    }
}