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

    public function CreateController(){
        $file = fopen('controllers/' . $this->FileName .".controller.php", "w") or die("Unable to open file!");
        $txt = "John Doe\n";
        fwrite($file, $txt);
        $txt = "Jane Doe\n";
        fwrite($file, $txt);
        fclose($file);
    }
}