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
            new Help ("create:model\t", "Creates model element in the script"),
            new Help ("create:controller\t", "Create Controller Class for the Object."),
            new Help ("create:view\t", "Create View template for the Object."),
            new Help ("create:route\t", "Creates all the required elements or the route such as (Controler, model and view).")
        ];

        echo "Options:\r\n";
        foreach($array as $value){
            echo $value->Param . " ". $value->Description . "\r\n";
        }
    }

    public function HelpStrings():array{
        $colors = new Colors();
        return [
            "create-view"=> $colors->getColoredString('How to use create view command:', 'green','black') . "\nCreate View Command accepts two arguement (view-name and controller-name) \nThe Command should be Writen as follow\nphp flo create:view page-name controller-name\nfor example: 'php flo create:view index pages' which index is the view of pages controller http://website.com/pages/index",
            "create-controller"=> $colors->getColoredString('How to use create Controller command:', 'green','black') . "\nCreate Controller Command accepts one arguement (controller-name) \nThe Command should be Writen as follow\nphp flo create:controller page-name controller-name\nfor example: 'php flo create:view index pages' which index is the view of pages controller http://website.com/pages/index"
            ];
    }
}