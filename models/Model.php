<?php
/**
 * Abstract class for all models
 * @package models
 */
/**
 * abstract class model
 * @abstract
 * @package models
 */
abstract class Model {

	const STATE_NEW = 0;
	const STATE_MODIFIED = 1;
	const STATE_SYNC = 2;

	private $datacontext;

	private $state;
	private $id;
	public $properties = array();

	public function __toString() {
		$string = sprintf('%s : id = %d, state = %s', get_class($this), $this->id, $this->state);
		$string .= '<ul>';
		foreach($this->properties as $property => $value) {
			$string .= sprintf('<li>%s = %s</li>', $property, $value);
		}
		$string .= '</ul>';
		return $string;
	}

	public function getDatacontext() {
		return $this->datacontext;
	}

	public function setDatacontext(&$datacontext) {
		return $this->datacontext = &$datacontext;
	}

	public function setId($id) {
		$this->id = $id;
	}

	public function getId() {
		return $this->id;
	}

	public function __set($property, $value) {
		if(!isset($this->properties[$property]) or $value != $this->properties[$property]) {
			$this->properties[$property] = $value;
			$this->state = (!$this->state or $this->state === self::STATE_NEW) ? self::STATE_NEW : self::STATE_MODIFIED;
		}
	}

	public function __get($property) {
		if(array_key_exists($property, $this->properties)) {
			return $this->properties[$property];
		}
		throw new ErrorException(sprintf('Property %s undefined for object %s', $property, get_class($this)));
		return null;
	}

	public function getState() {
		return $this->state;
	}

	public function setState($state) {
		return $this->state = $state;
	}

	/**
	 * Get a field in database
	 * @param string $table table in database
	 * @param string $field field in table
	 * @return string field content
	 */
	public function getField($table,$field) {
		try {
			$results = $this->datacontext->getConnection()->query("SELECT $field FROM $table LIMIT 1");
			return $results->fetchFirst();
		}
		catch(PDOException $e) {
			$this->addEvent(1,__METHOD__,'db','Error when retrieving field '.$field.' in table '.$table.' - Error n°'.$e->getCode().' - '.$e->getMessage());
			return false;
		}
	}

	/**
	 * ORM management
	 */
	public function readProperty($property) {
		$query = sprintf('SELECT %s FROM %s WHERE id = %d',
			$property,
			$this->datacontext->mapping[get_class($this)],
			$this->id);
		$result = $this->datacontext->getConnection()->query($query);
		return $result->fetchFirstColumn();
	}

	public function writeProperty($property, $value) {
		$query = sprintf('UPDATE %s SET `%s` = %s WHERE id = %d',
			$this->datacontext->mapping[get_class($this)],
			$property,
			$this->datacontext->getConnection()->quote($value),
			$this->id);
		$this->datacontext->getConnection()->exec($query);
		return true;
	}

	public function commit() {
		if($this->state === self::STATE_MODIFIED) {
			$first = true;
			$update = '';
			foreach($this->properties as $property => $value) {
				if($property != 'id' and $property != 'state') {
					if($first) {
						$first = false;
					}
					else {
						$update .= ', ';
					}
					$update .= $property.' = ';
					$update .= $value === null ? 'NULL' : $this->datacontext->getConnection()->quote($value);
				}
			}
			$query = sprintf('UPDATE %s SET %s WHERE id = %d',
				$this->datacontext->mapping[get_class($this)],
				$update,
				$this->id);
			$this->datacontext->getConnection()->exec($query);
		}
		else if($this->state === self::STATE_NEW) {
			$query = sprintf('INSERT INTO %s(%s) VALUES (%s)',
				$this->datacontext->mapping[get_class($this)],
				implode(',', array_keys($this->properties)),
				implode(',', array_values($this->properties)));
			$this->datacontext->getConnection()->exec($query);
			$this->id = $this->datacontext->getConnection()->lastInsertId();
		}
		$this->state = self::STATE_SYNC;
	}

	public function rollback() {
		$query = sprintf('SELECT * FROM %s WHERE id = %s LIMIT 1',
			$this->datacontext->mapping[get_class($this)],
			$this->datacontext->getConnection()->quote($id));
		$result = $this->datacontext->getConnection()->query($query);
		$result = $result->fetchFirstRow();
		foreach($result as $property => $value) {
			if($property != 'id') {
				$this->objects[$name][$id]->properties[$property] = $value;
			}
		}
		$this->state = self::STATE_SYNC;
	}
}
