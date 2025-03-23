<?php

//namespace App;

class Data{
    
    private $testData;

    public function __construct(string $data){
        $this->testData = $data;
    }

    public function displayData(){
        return 'This is '. $this->testData;
    }
}