<?php

class LessDB
{
    protected $dsn;
    protected $pdo;
    
    function __construct($dsn) {
        $this->dsn = $dsn;
    }

    function __destruct() {
        $this->close();
    }

    public function insertInto($table) {
        return new LessDBInsertTable($this->dsn, $table);
    }

    public function connect() {
        $this->pdo = new PDO($this->dsn);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function close() {
        $this->pdo = null;
    }
}

class LessDBInsertTable extends LessDB
{
    private $table;
    private $pairs;
    
    function __construct($dsn, $table) {
        parent::__construct($dsn);
        $this->table = $table;
        $pairs = array();
    }

    function __destruct() {
        parent::__destruct();
    }

    public function addPair($name, $value) {
        $this->pairs[':'.$name] = $value;
    }

    public function execute() {
        $this->connect();
        
        $stmt = $this->pdo->prepare($this->getSql());
        $result = $stmt->execute($this->pairs);
        $stmt->closeCursor();
            
        $this->close();

        return $result;
    }

    public function getSql() {
        $columns = implode("", explode(":", implode(", ", array_keys($this->pairs))));
        $bindings = implode(", ", array_keys($this->pairs));
        
        return sprintf("INSERT INTO %s (%s) VALUES (%s)", $this->table, $columns, $bindings);
    }
}
