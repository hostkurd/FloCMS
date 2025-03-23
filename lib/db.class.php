<?php
class DB{
    protected $connection;
    public $connectionIssue = false;
    public $tableIssue = false;

    public function __construct($host,$db_user,$db_pass,$db_name){
        // Enable custom reporting throwgh catch
        mysqli_report(MYSQLI_REPORT_STRICT);

        try{
            // Connecting to the database
            $this->connection = new mysqli($host,$db_user,$db_pass,$db_name);

            // changing musql connection's charset to utf8 for better supporting of unicode character
            $this->connection->set_charset("utf8");

         }
         catch(Exception $e)
         {
            $this->connectionIssue = $e->getMessage();
         }
    }

    public function query($sql){
        if (!$this->connection){
            return false;
        }
        $result = $this->connection->query($sql);

        // On Error return the error
        if (mysqli_error($this->connection)){ 
            //Display Database Error Page
            $layout_path = VIEWS_PATH.DS.'dbQueryError.html';
            //echo"Method Not Found!";
            $layout_view_object = new View(array('error'=>mysqli_error($this->connection)),$layout_path);
            //$layout_view_object = new View(compact('content'),$layout_path);
            echo $layout_view_object->render();

            die();
        }

         //If operation is boolean then return it Like Delete
        if (is_bool($result)){
            return $result;
        }

        // If operation returns record the return records
        $data=array();
        while ($row = mysqli_fetch_assoc($result)){
            $data[]=$row;
        }
        return $data;
    }

    public function multiQuery($sql){
        if (!$this->connection){
            return false;
        }
        $result = $this->connection->multi_query($sql);

        // On Error return the error
        if (mysqli_error($this->connection)){
            //Session::setFlash('DB Multi Error: '.mysqli_error($this->connection));
            $this->tableIssue = true;
            return false;
            //die(mysqli_error($this->connection));
        }

        //If operation is boolean then return it Like Delete
        if (is_bool($result)){
            return $result;
        }

    }

    public function escape($str){
        return mysqli_escape_string($this->connection, $str);
    }


}