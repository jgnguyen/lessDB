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

	public function getTable($table) {
		return new LessDBTable($this->dsn, $table);
	}
	
	public function complexQuery($sql) {
		$this->connect();
		
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute();
		$result = $stmt->fetchAll();
		
		$this->close();
		
		return $result;
	}

    private function connect() {
        $this->pdo = new PDO($this->dsn);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function close() {
        $this->pdo = null;
    }
}

class LessDBTable extends LessDB 
{
	private $table;
	
	function __construct($dsn, $table) {
		parent::__construct($dsn);
		$this->table = $table;
	}
	
	function __destruct() {
		parent::__destruct();
	}
	
	/*
	 * Query methods
	 */
	public function getJson($columns, $condition = null) {
		return json_encode($this->query($columns, $condition));
	}
	
	public function getArray($columns, $conditions = null) {
		return $this->query($columns, $conditions);
	}
	
	/*
	 * Insert methods
	 */
	public function addPair($name, $value) {
        $this->pairs[':'.$name] = $value;
    }

    public function execute() {
        $this->connect();
        
        $stmt = $this->pdo->prepare($this->makeSql());
        $result = $stmt->execute($this->pairs);
        $stmt->closeCursor();
        
        $this->close();

        return $result;
    }

	/*
	 * Private methods
	 */ 
	private function clearPairs() {
		$this->pairs = array();
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
	
	private function makeSql() {
        $columns = implode("", explode(":", implode(", ", array_keys($this->pairs))));
        $bindings = implode(", ", array_keys($this->pairs));
        
        return sprintf("INSERT INTO %s (%s) VALUES (%s)", $this->table, $columns, $bindings);
    }
}