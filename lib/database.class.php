<?php
class Database{

    protected $pdo;
    protected $table;
    protected $fields = '*';
    protected $where = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function table ($table)
    {
        $this->table = $table;
        return $this;
    }

    public function select($fields)
    {
        $this->fields = $fields;
        return $this;
    }

    public function where($column, $operator, $value)
    {
        $this->where[] = [
            'type'=>'AND',
            'column'=>$column,
            'operator'=>$operator,
            'value'=>$value
        ];
        return $this;
    }

    public function orWhere($column, $operator, $value)
    {
        $this->where[] = [
            'type'=>'OR',
            'column'=>$column,
            'operator'=>$operator,
            'value'=>$value
        ];
        return $this;
    }

    public function get()
    {
       $sql = 'SELECT '.$this->fields. ' From ' . $this->table;

       if(!empty($this->where)){
        $sql .= ' WHERE ';
        foreach($this->where as $index => $where){
            if($index > 0){
                $sql.= $where['type']. ' ';
            }
            $sql .= $where['column'] . ' '. $where['operator'].' ?';
        }
       }
       $stmt = $this->pdo->prepare($sql);
       $bindedValues = array_column($this->where, 'value');
       $stmt->execute($bindedValues);
       return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}