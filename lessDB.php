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

	public function queryFrom($table) {
		return new LessDBQueryTable($this->dsn, $table);
	}

    public function connect() {
        $this->pdo = new PDO($this->dsn);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function close() {
        $this->pdo = null;
    }
}

class LessDBQueryTable extends LessDB
{
	private $table;
	
	function __construct($dsn, $table) {
		parent::__construct($dsn);
		$this->table = $table;
	}
	
	private function query($columns, $condition) {
		$this->connect();
		
		if ($condition == null)
			$sql = sprintf("SELECT %s FROM %s", $columns, $this->table);
		else
			$sql = sprintf("SELECT %s FROM %s %s", $columns, $this->table, $condition);
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute();
		$temp = $stmt->fetchAll();
		
		$keys = explode(", ", $columns);
		$result = array();
		$i = 0;
		foreach ($temp as $t) {
			foreach ($keys as $key) {
				$result[$i][$key] = $temp[$i][$key];
			}
			++$i;
		}
     
        $this->close();   

		return $result;
	}
	
	public function getJson($columns, $condition = null) {
		return json_encode($this->query($columns, $condition));
	}
	
	public function getArray($columns, $conditions = null) {
		return $this->query($columns, $conditions);
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

	private function clearPairs() {
		$this->pairs = array();
	}
}

$db = new LessDB("sqlite:serve.db");
$usersTable = $db->queryFrom("Users");
echo $usersTable->getJson("fname, lname");