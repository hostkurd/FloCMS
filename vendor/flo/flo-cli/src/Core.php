<?php
Class Core{

    private $TextData;
    private $HelpData;
    private $AllCommands;
    private $Command;
    private $Colors;

    public function __construct($command)
    {
        $this->Command = $command;
        $jsonData = json_decode(file_get_contents(__DIR__."/data.json"), true);
        $this->TextData = $jsonData['cli-data']['dummy-texts'];
        $this->HelpData = $jsonData['cli-data']['help-strings'];
        $this->AllCommands = $jsonData['cli-data']['all-commands'];
        
        $this->Colors = new Colors();
    }

    public function CreateController(){
        if(!isset($this->Command[2])){
            return $this->Colors->getColoredString('Warning:', 'white','red') . "Controller name is required, please enter valid controller name with the command.";
        }
        $fileName = $this->Command[2];
        //echo __DIR__;
        $text = sprintf($this->TextData['controller-text'],ucfirst($fileName));
        // print_r($this->TextData['controller-text']);
        
         $file = fopen('controllers/' . $fileName .".controller.php", "w") or die("Unable to open file!");
         if(fwrite($file, $text)){
             return $this->Colors->getColoredString('Info:', 'white','blue')."Controller has been Created Successfully.";
             fclose($file);
         };
        
    }

    public function CreateModel(){
        if(!isset($this->Command[2])){
            return $this->Colors->getColoredString('Warning:', 'white','red') . "Model name is required, please enter valid Model name with the command.";
        }
        $fileName = $this->Command[2];
        //echo __DIR__;
        $text = sprintf($this->TextData['model-text'],ucfirst($fileName));
        // print_r($this->TextData['controller-text']);
        
         $file = fopen('models/' . $fileName .".model.php", "w") or die("Unable to open file!");
         if(fwrite($file, $text)){
             return $this->Colors->getColoredString('Info:', 'white','blue')."Model has been Created Successfully.";
             fclose($file);
         };
    }

    public function CreateView(){
        if(!isset($this->Command[2])){
            return $this->Colors->getColoredString('Warning:', 'white','red') . "View name and route is required, please enter valid View name with the command.";
        }

        if($this->Command[2] == '--help'){
            $title = $this->Colors->getColoredString('How to Use Create View command:', 'green','black');
            $text =  $this->HelpData['create-view'];
            return sprintf($text, $title);
        }

        if(!isset($this->Command[3])){
            return $this->Colors->getColoredString('Warning:', 'white','red') . "View route is required, please enter valid route for the View.";
        }

        $fileName = $this->Command[2];
        $route =  $this->Command[3];
    }

    public function ShowAllCommands():void{
        foreach($this->AllCommands as $key => $value){
            print_r($key ." :\t ". $value ."\n");
        }
         
    }
}