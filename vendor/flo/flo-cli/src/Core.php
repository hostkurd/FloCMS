<?php
Class Core{
    private $ControllerFile;
    private $ModelFile;
    private $ViewFile;
    private $FileName;

    public function __construct($fileName)
    {
        $this->FileName = $fileName;
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
        $file = fopen('controllers/' . $this->FileName .".controller.php", "w") or die("Unable to open file!");
        fwrite($file, $this->ControllerText());
        fclose($file);
    }
}