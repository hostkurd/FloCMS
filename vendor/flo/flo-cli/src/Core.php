<?php
Class Core{
    private $ControllerFile;
    private $ModelFile;
    private $TextData;
    private $FileName;

    public function __construct($fileName)
    {
        $this->FileName = $fileName;
        $data = json_decode(file_get_contents(__DIR__."/data.json"), true);
        $this->TextData[] = $data['cli-data'];
        $this->TextData = $this->TextData[0]['dummy-texts'];
    }

    private function ControllerText(){
        return '<?php
class '.$this->FileName.'Controller extends Controller{
    public function __construct(array $data = array())
    {
        parent::__construct($data);
        $this->model = new PagesModel();
    }

    public function index(){ 
        $this->data[\'title\'] = \'Pages Index Title\';
    }
}';


    }
    public function CreateController(){
        //echo __DIR__;
        print_r($this->TextData['controller-text']);
        // $colors = new Colors();
        // $file = fopen('controllers/' . $this->FileName .".controller.php", "w") or die("Unable to open file!");
        // if(fwrite($file, $this->ControllerText())){
        //     return $colors->getColoredString('Info:', 'white','blue')."Controller has been Created Successfully.";
        //     fclose($file);
        // };
        
    }
}