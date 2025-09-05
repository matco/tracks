<?php
/**
 * Session management class, used to override the default session storage mechanism and store the sessions in the database
 * @package core
 */
class DatabaseSessionHandler implements SessionHandlerInterface {
	public function __construct() {
	}

	public function __destruct() {
	}

	private function getConnection() {
		return ConnectionProvider::getConnection();
	}

	public function open($path, $name) {
		$session_id = $this->getConnection()->quote(session_id());
		try {
			$results = $this->getConnection()->query(sprintf('SELECT COUNT(session_id) FROM sessions WHERE session_id = %s', $session_id));
			$session = $results->fetchFirstColumn();
			if($session == 0) {
				$this->getConnection()->exec(sprintf('INSERT INTO sessions(session_id, session_last_modified) VALUES(%s, NOW())', $session_id));
			}
			$results->closeCursor();
			return true;
		}
		catch(PDOException $e) {
			$GLOBALS['logger']->addLog(Logger::LEVEL_SEVERE, Logger::TYPE_DB, __METHOD__, null, 'Error when opening session - Error n°'.$e->getCode().' - '.$e->getMessage());
			return false;
		}
	}

	public function write($session_id, $session_data) {
		$session_id = $this->getConnection()->quote($session_id);
		$session_data = $this->getConnection()->quote($session_data);
		try {
			$this->getConnection()->exec(sprintf('UPDATE sessions SET session_data = %s, session_last_modified = NOW() WHERE session_id = %s', $session_data, $session_id));
			return true;
		}
		catch(PDOException $e) {
			$GLOBALS['logger']->addLog(Logger::LEVEL_SEVERE, Logger::TYPE_DB, __METHOD__, null, 'Error when writing session - Error n°'.$e->getCode().' - '.$e->getMessage());
			return false;
		}
	}

	public function close() {
		return true;
	}

	public function read($session_id) {
		$session_id = $this->getConnection()->quote($session_id);
		try {
			$results = $this->getConnection()->query(sprintf('SELECT session_data FROM sessions WHERE session_id = %s', $session_id));
			$result = $results->fetchFirstColumn();
			return empty($result) ? '' : $result;
		}
		catch(PDOException $e) {
			$GLOBALS['logger']->addLog(Logger::LEVEL_SEVERE, Logger::TYPE_DB, __METHOD__, null, 'Error when reading session - Error n°'.$e->getCode().' - '.$e->getMessage());
			return false;
		}
	}

	public function destroy($session_id) {
		$session_id = $this->getConnection()->quote($session_id);
		try {
			$this->getConnection()->exec(sprintf('DELETE FROM sessions WHERE session_id = %s', $session_id));
			return true;
		}
		catch(PDOException $e) {
			$GLOBALS['logger']->addLog(Logger::LEVEL_SEVERE, Logger::TYPE_DB, __METHOD__, null, 'Error when closing session - Error n°'.$e->getCode().' - '.$e->getMessage());
			return false;
		}
	}

	public function gc($max_lifetime) {
		//if sessions haven't been modified for 1440s, this function is executed
		try {
			$this->getConnection()->exec("DELETE FROM sessions WHERE session_last_modified < DATE_SUB(NOW(), INTERVAL $max_lifetime SECOND)");
			return true;
		}
		catch(PDOException $e) {
			$GLOBALS['logger']->addLog(Logger::LEVEL_SEVERE, Logger::TYPE_DB, __METHOD__, null, 'Error when cleaning sessions - Error n°'.$e->getCode().' - '.$e->getMessage());
			return false;
		}
	}

	public function logged() {
		try {
			$results = $this->getConnection()->query("SELECT COUNT(session_id) FROM sessions");
			return $results->fetchFirstColumn();
		}
		catch(PDOException $e) {
			$GLOBALS['logger']->addLog(Logger::LEVEL_SEVERE, Logger::TYPE_DB, __METHOD__, null, 'Error when reading session - Error n°'.$e->getCode().' - '.$e->getMessage());
			return false;
		}
	}
}
