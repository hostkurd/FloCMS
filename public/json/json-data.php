<?php
//header('Content-Type: application/json; charset=utf-8');
include ('../conf_global.php');


function getFileList($path, $extension, $type = null){
    //$dir = ROOT.DS.'webroot/assets/img/icons/'.$type.'/';
    $files = scandir($path);
    //$ext = '.svg';
    $result[] = array();

    $id = 0;
    foreach ($files as $file) {
        if ( substr_compare($file, $extension, -strlen($extension), strlen($extension)) === 0 ) {
            $result[$id]['file'] = $file;
            if($type){
                $result[$id]['type'] =$type;
            }
            $id++;
        }
    }
    return json_encode($result);
}

if(isset($_GET['action']) && isset($_GET['type'])){
    if($_GET['action']=='list-icons'){
        echo getFileList(ROOT.DS.'webroot/assets/img/icons/'.$_GET['type'].'/','.svg',$_GET['type']);
    }
}