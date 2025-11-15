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
        $text = sprintf($this->TextData['controller-text'],ucfirst($fileName),ucfirst($fileName));
        
         $path = 'controllers/' . $fileName .'.controller.php';
         if($this->CreateFile($path, $text)){
            return $this->Colors->getColoredString('Info:', 'white','blue')."Controller has been Created Successfully.";
         }else{
            return $this->Colors->getColoredString('Warning:', 'white','red')."Controller already Exist.";
         }
    }

    public function CreateModel(){
        if(!isset($this->Command[2])){
            return $this->Colors->getColoredString('Warning:', 'white','red') . "Model name is required, please enter valid Model name with the command.";
        }
        $fileName = $this->Command[2];
        //echo __DIR__;
        $text = sprintf($this->TextData['model-text'],ucfirst($fileName));
        // print_r($this->TextData['controller-text']);
        
         //$file = fopen('models/' . $fileName .".model.php", "w") or die("Unable to open file!");
         $path = 'models/' . $fileName .'.model.php';
         if($this->CreateFile($path, $text)){
            return $this->Colors->getColoredString('Info:', 'white','blue')."Model has been Created Successfully.";
         }else{
            return $this->Colors->getColoredString('Warning:', 'white','red')."Model already Exist.";
         }
    }

    public function CreateView($fileName = null){
        if(!isset($this->Command[2])){
            return $this->Colors->getColoredString('Warning:', 'white','red') . "View name and route is required, please enter valid View name with the command.";
        }

        if($this->Command[2] == '--help'){
            $title = $this->Colors->getColoredString('How to Use Create View command:', 'green','black');
            return sprintf($this->HelpData['create-view'], $title); 
        }

        $controler = $this->Command[2];

        if(!isset($this->Command[3])){
            if ($fileName == null)
                return $this->Colors->getColoredString('Warning:', 'white','red') . "View route is required, please enter valid route for the View.";
        }else{
           $fileName =  $this->Command[3]; 
        }
        
        $path = 'views/'.$controler.'/' . $fileName .".html";
        $text = sprintf($this->TextData['view-text'],ucfirst($fileName), ucfirst($controler));

        if($this->CreateFile($path, $text)){
            echo $this->Colors->getColoredString('Info:', 'white','blue')."View file has been Created Successfully.";
         }else{
            echo $this->Colors->getColoredString('Warning:', 'white','red')."View file already Exist.";
            return;
         }

        $controllerPath = 'controllers/' . $controler .'.controller.php';
        $methodText = sprintf($this->TextData['method-text'],ucfirst($fileName));  
        try {
            $this->AppendClassMethod($controllerPath, $methodText);
        } catch (Exception $th) {
            echo $th;
        } 
    }

    protected function CreateFile($file, $content){
        if (file_exists($file)) {
            return false;
        }
        // if file dir not exist create it
        $fileDir = dirname($file);
        if (!is_dir($fileDir)) mkdir($fileDir, 0755, true);

        file_put_contents($file, $content);
        return true;
    }

    protected function AppendClassMethod($filePath, $methodCode){
        if (!file_exists($filePath)) {
            echo "File not found: $filePath";
            throw new Exception("File not found: $filePath");
        }
        // Read file
        $content = file_get_contents($filePath);

        // Find last closing bracket of the class
        $pos = strrpos($content, "}");

        if ($pos === false) {
            throw new Exception("Invalid class file, missing closing }");
            
        }

        // Insert method before the last "}"
        $newContent =
            substr($content, 0, $pos) .
            "\n    " . trim($methodCode) . "\n" .
            "}\n";

        // Write updated file
        file_put_contents($filePath, $newContent);

        return true;
    }

    public function CreateRoute(){
        $this->CreateController();
        $this->CreateModel();
        $this->CreateView('index');
    }

    public function ShowAllCommands():void{
        foreach($this->AllCommands as $key => $value){
            print_r($key ." :\t ". $value ."\n");
        }
         
    }
}