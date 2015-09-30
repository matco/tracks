<?php
class Datacontext {

	const MODELS_DIRECTORY = 'models';

	public $mapping = array();
	private $objects = array();

	public function __construct() {
		$this->getMapping();
		$this->getShare();
	}

	public function __destruct() {
		$this->setShare();
	}

	public function __sleep() {
		$this->setShare();
		return array();
	}

	public function __wakeup() {
		$this->getShare();
	}

	private function setShare() {
		$query = sprintf('UPDATE share SET share = %s WHERE id = 1', $this->getConnection()->quote(serialize($this->objects)));
		$this->getConnection()->exec($query);
	}

	private function getShare() {
		$result = $this->getConnection()->query('SELECT share FROM share WHERE id = 1');
		if($result->rowCount() > 0) {
			$this->objects = unserialize($result->fetchFirstColumn());
		}
		if(!$this->objects) {
			$this->objects = array();
		}
		foreach($this->objects as $object) {
			$object->setDatacontext($this);
		}
	}

	public function getConnection() {
		return ConnectionProvider::getConnection();
	}

	public function __clone() {
		throw new Exception('Clone is not allowed');
	}

	public function getMapping() {
		if(!$this->mapping) {
			$this->mapping = parse_ini_file(dirname(__FILE__).DIRECTORY_SEPARATOR.self::MODELS_DIRECTORY.DIRECTORY_SEPARATOR.'mapping.ini');
			//load files
			foreach($this->mapping as $class => $database) {
				$file = dirname(__FILE__).DIRECTORY_SEPARATOR.self::MODELS_DIRECTORY.DIRECTORY_SEPARATOR.$class.'.php';
				if(!file_exists($file)) {
					$message = 'Unable to load '.$file.' for '.$class;
					$GLOBALS['logger']->addLog(Logger::LEVEL_SEVERE, Logger::TYPE_DB, __METHOD__, null, $message);
					throw new Exception($message);
				}
				require_once($file);
			}
		}
		return $this->mapping;
	}

	/**
	 * Load a object from the database
	 * @param string $name type of the object
	 * @param string $id id of the object
	 * @return instance of the object
	 */
	public function getObject($name, $id) {
		if(!array_key_exists($name, $this->objects) or !array_key_exists($id, $this->objects[$name]) or !isset($this->objects[$name][$id])) {
			$query = sprintf('SELECT * FROM %s WHERE id = %s LIMIT 1',
				$this->mapping[$name],
				$this->getConnection()->quote($id));
			$result = $this->getConnection()->query($query);
			$result = $result->fetchFirstRow();
			if(!$result) {
				throw new ErrorException(sprintf('Unable to retrieve %s with id %s', $name, $value));
			}
			$id = $result['id'];
			$this->objects[$name][$id] = new $name();
			$this->objects[$name][$id]->setId($id);
			$this->objects[$name][$id]->setState(Model::STATE_SYNC);
			$this->objects[$name][$id]->setDatacontext($this);
			foreach($result as $property => $value) {
				if($property !== 'id') {
					$this->objects[$name][$id]->properties[$property] = $value;
				}
			}
		}
		return $this->objects[$name][$id];
	}

	public function getObjectFromProperty($name, $property, $value) {
		$query = sprintf('SELECT * FROM %s WHERE %s = %s LIMIT 1',
			$this->mapping[$name],
			$property,
			$this->getConnection()->quote($value));
		$result = $this->getConnection()->query($query);
		$result = $result->fetchFirstRow();
		if(!$result) {
			throw new ErrorException(sprintf('Unable to retrieve %s with %s = %s', $name, $property, $value));
		}
		$id = $result['id'];
		$this->objects[$name][$id] = new $name();
		$this->objects[$name][$id]->setId($id);
		$this->objects[$name][$id]->state = Model::STATE_SYNC;
		$this->objects[$name][$id]->setDatacontext($this);
		foreach($result as $property => $value) {
			if($property !== 'id') {
				$this->objects[$name][$id]->properties[$property] = $value;
			}
		}
		return $this->objects[$name][$id];
	}

	public function getObjects($name, $clause, $order, $direction, $count, $offset) {
		$objects = array();
		$query = sprintf('SELECT * FROM %s', $this->mapping[$name]);
		if($clause) {
			$query .= ' WHERE '.$clause;
		}
		if($order) {
			$query .= ' ORDER BY '.$order.' '.$direction;
		}
		if($count) {
			$query .= ' LIMIT '.$count;
		}
		if($offset) {
			$query .= ' OFFSET '.$offset;
		}
		$statement = $this->getConnection()->query($query);
		while($result = $statement->fetch(PDO::FETCH_ASSOC)) {
			$id = $result['id'];
			$this->objects[$name][$id] = new $name();
			$this->objects[$name][$id]->setId($id);
			$this->objects[$name][$id]->state = Model::STATE_SYNC;
			$this->objects[$name][$id]->setDatacontext($this);
			foreach($result as $property => $value) {
				if($property !== 'id') {
					$this->objects[$name][$id]->properties[$property] = $value;
				}
			}
			array_push($objects, $this->objects[$name][$id]);
		}
		return $objects;
	}

	/**
	 * Controller must sometimes be able to control transactions, this method allow to start a transaction
	 * @return bool success or not
	 */
	public function startTransaction() {
		return $this->getConnection()->beginTransaction();
	}

	/**
	 * Controller must sometimes be able to control transactions, this method allow to stop a transaction
	 * @return bool success or not
	 */
	public function stopTransaction() {
		return $this->getConnection()->commit();
	}

	/**
	 * Retrieve data from database
	 * @param string $query sql query
	 * @return array
	 */
	public function DBToArray($query) {
		try {
			$results = $this->getConnection()->query($query);
			return $results->fetchAll(PDO::FETCH_ASSOC);
		}
		catch(PDOException $e) {
			$GLOBALS['logger']->addLog(Logger::LEVEL_SEVERE, Logger::TYPE_DB, __METHOD_, null, 'Error n°'.$e->getCode().' - '.$e->getMessage());
			return false;
		}
	}
}
