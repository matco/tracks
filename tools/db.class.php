<?php
/**
 * DB class
 * @package globals
 */
class DB extends PDO {

	private $queries = array();

	public function __construct($dsn, $user = '', $password = '', $driver_options = array()) {
		parent::__construct($dsn, $user, $password, $driver_options);
		$this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('DBStatement', array($this)));
	}

	public function prepare($statement, $driver_options = array()): PDOStatement|false {
		array_push($this->queries, $statement.' - preparation');
		return parent::prepare($statement, $driver_options);
	}

	public function exec($query): int|false {
		array_push($this->queries, $query);
		return parent::exec($query);
	}

	public function run($query) {
		array_push($this->queries, $query);
		return parent::run($query, $fetch_mode, $fetch_mode_args);
	}

	public function getLastQuery() {
		return $this->queries[count($this->queries) - 1];
	}

	public function getQueryNumber() {
		return count($this->queries);
	}

	public function getQueries() {
		return $this->queries;
	}

	/**
	 * Insert a row in a simple table after checking it does not exist
	 * @param string $table table concerned by the insertion
	 * @param string $field primary key field
	 * @param string $name name of the new insertion
	 * @return string name
	 */
	public function addToSimpleTable($table, $field, $name) {
		$results = $this->query('SELECT %1$s FROM %2$s WHERE %1$s LIKE %3$s',
			$field,
			$table,
			$this->quote(utf8_encode($name)));
		$id = $results->fetchFirst();
		if(empty($id)) {
			$this->exec("INSERT INTO $table($field) VALUES($quoted_name)");
		}
		return $name;
	}
}

/**
 * DBStatement class
 * @package global class
 */
class DBStatement extends PDOStatement {
	protected $pdo;

	protected function __construct($pdo) {
		$this->pdo = $pdo;
	}

	//return first row
	public function fetchFirstRow() {
		$row = $this->fetch(PDO::FETCH_ASSOC);
		$this->closeCursor();
		return $row;
	}

	//return first column of first row
	public function fetchFirstColumn() {
		$row = $this->fetch(PDO::FETCH_NUM);
		$this->closeCursor();
		if($row) {
			return $row[0];
		}
		return null;
	}

	public function execute($parameters = null): bool {
		array_push($this->pdo->queries, $this->queryString.' - '.implode(',',$parameters));
		return parent::execute($parameters);
	}
}
