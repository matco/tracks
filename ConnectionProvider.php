<?php
class ConnectionProvider {
	private static $error;
	private static $connection;

	public static function isConnected() {
		return empty(self::$error);
	}

	public static function getConnection() {
		//dot not log anything here (except if there is an error) to avoid a infinite loop
		if(!self::$connection) {
			global $config;
			try {
				self::$connection = new DB($config['db']['type'].':host='.$config['db']['host'].';port='.$config['db']['port'].';dbname='.$config['db']['base'], $config['db']['user'], $config['db']['password']);
				self::$connection->setAttribute(PDO::ATTR_PERSISTENT, false);
				self::$connection->setAttribute(PDO::ATTR_CASE,PDO::CASE_LOWER); //fields name in lower case
				self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //errors will triggered exceptions
				self::$connection->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('DBStatement', array(self::$connection))); //association with a special statement class
				self::$connection->exec('SET CHARACTER SET utf8');
				self::$error = null;
			}
			catch(PDOException $e) {
				self::$error = $e->getMessage();
				$GLOBALS['logger']->addLog(Logger::LEVEL_CRITICAL, Logger::TYPE_DB, __METHOD__, null, 'Unable to login to database - Error n°'.$e->getCode().' - '.$e->getMessage());
			}
		}
		return self::$connection;
	}
}
