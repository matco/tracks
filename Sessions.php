<?php
/**
 * Session management, using a php function to remplace sessions functions by user-defined function in order to store sessions in database instead of on file system
 * @package core
 */
class Sessions {
	public function __construct() {
	}

	public function __destruct() {
	}

	private static function getConnection() {
		return ConnectionProvider::getConnection();
	}

	public static function open() {
		$session_id = self::getConnection()->quote(session_id());
		//session_regenerate_id();
		try {
			$results = self::getConnection()->query(sprintf('SELECT COUNT(session_id) FROM sessions WHERE session_id = %s', $session_id));
			$session = $results->fetchFirstColumn();
			if($session == 0) {
				self::getConnection()->exec(sprintf('INSERT INTO sessions(session_id, session_last_modified) VALUES(%s, NOW())', $session_id));
			}
			$results->closeCursor();
			return true;
		}
		catch(PDOException $e) {
			$GLOBALS['logger']->addLog(Logger::LEVEL_SEVERE, Logger::TYPE_DB, __METHOD__, null, 'Error when opening session - Error n°'.$e->getCode().' - '.$e->getMessage());
			return false;
		}
	}

	public static function write($session_id, $session_data) {
		$session_id = self::getConnection()->quote($session_id);
		$session_data = self::getConnection()->quote($session_data);
		try {
			self::getConnection()->exec(sprintf('UPDATE sessions SET session_data = %s, session_last_modified = NOW() WHERE session_id = %s', $session_data, $session_id));
			return true;
		}
		catch(PDOException $e) {
			$GLOBALS['logger']->addLog(Logger::LEVEL_SEVERE, Logger::TYPE_DB, __METHOD__, null, 'Error when writing session - Error n°'.$e->getCode().' - '.$e->getMessage());
			return false;
		}
	}

	public static function close() {
		return true;
	}

	public static function read($session_id) {
		$session_id = self::getConnection()->quote($session_id);
		try {
			$results = self::getConnection()->query(sprintf('SELECT session_data FROM sessions WHERE session_id = %s', $session_id));
			$result = $results->fetchFirstColumn();
			return empty($result) ? '' : $result;
		}
		catch(PDOException $e) {
			$GLOBALS['logger']->addLog(Logger::LEVEL_SEVERE, Logger::TYPE_DB, __METHOD__, null, 'Error when reading session - Error n°'.$e->getCode().' - '.$e->getMessage());
			return false;
		}
	}

	public static function destroy($session_id) {
		$session_id = self::getConnection()->quote($session_id);
		try {
			self::getConnection()->exec(sprintf('DELETE FROM sessions WHERE session_id = %s', $session_id));
			return true;
		}
		catch(PDOException $e) {
			$GLOBALS['logger']->addLog(Logger::LEVEL_SEVERE, Logger::TYPE_DB, __METHOD__, null, 'Error when closing session - Error n°'.$e->getCode().' - '.$e->getMessage());
			return false;
		}
	}

	public static function clean($max_lifetime) {
		//if sessions haven't been modified for 1440s, this function is executed
		try {
			self::getConnection()->exec("DELETE FROM sessions WHERE session_last_modified < DATE_SUB(NOW(), INTERVAL $max_lifetime SECOND)");
			return true;
		}
		catch(PDOException $e) {
			$GLOBALS['logger']->addLog(Logger::LEVEL_SEVERE, Logger::TYPE_DB, __METHOD__, null, 'Error when cleaning sessions - Error n°'.$e->getCode().' - '.$e->getMessage());
			return false;
		}
	}

	public static function logged() {
		try {
			$results = self::getConnection()->query("SELECT COUNT(session_id) FROM sessions");
			return $results->fetchFirstColumn();
		}
		catch(PDOException $e) {
			$GLOBALS['logger']->addLog(Logger::LEVEL_SEVERE, Logger::TYPE_DB, __METHOD__, null, 'Error when reading session - Error n°'.$e->getCode().' - '.$e->getMessage());
			return false;
		}
	}
}
