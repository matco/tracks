<?php
class Logger {

	const LEVEL_CRITICAL = 0;
	const LEVEL_SEVERE = 1;
	const LEVEL_WARNING = 2;
	const LEVEL_INFO = 3;
	const LEVEL_DEBUG = 4;

	const TYPE_DB = 0;
	const TYPE_USER = 1;
	const TYPE_SYSTEM = 2;

	private int $stopOnLevel;
	private ?int $fileLevel;
	private ?int $dbLevel;

	private array $logs = array();

	private static function getConnection() {
		return ConnectionProvider::getConnection();
	}

	public function __construct(int $stop_on_level = LEVEL_WARNING, ?int $file_level = null, ?int $db_level = null) {
		$this->stopOnLevel = $stop_on_level;
		$this->fileLevel = $file_level;
		$this->dbLevel = $db_level;
	}

	/**
	 * Return logs variable
	 * @return array logs
	 */
	public function getLogs() {
		return $this->logs;
	}

	/**
	 * Add a log in database
	 * @param int $level level of the log
	 * @param string $source source of the log
	 * @param string $type type of the log
	 * @param string $user user who generate the log
	 * @param string $description description of the log
	 */
	public function addLog(int $level, int $type, string $source, ?string $user, string $description) {
		global $config;
		$log = array(
			'time' => date('c'),
			'level' => $level,
			'type' => $type,
			'source' => $source,
			'user' => $user,
			'ip' => $_SERVER['REMOTE_ADDR'],
			'description' => $description);
		//retrieve last query for a database log if connection is available
		if($type === self::TYPE_DB and ConnectionProvider::isConnected()) {
			$log['query'] = self::getConnection()->getLastQuery();
		}
		//adding in memory for displaying
		array_push($this->logs, $log);
		//stop script if event is too important
		if($level <= 1 or isset($this->stopOnLevel) && $level <= $this->stopOnLevel) {
			echo $description;
			if(ConnectionProvider::isConnected()) {
				echo '<pre>';
					print_r(self::getConnection()->queries_done);
				echo '</pre>';
			}
			die('Critical error : Script stopped');
		}
		//save log in file
		if(isset($this->fileLevel) && $level <= $this->fileLevel) {
			switch($level) {
				case (self::LEVEL_WARNING) : $level_text = 'warning';
				case (self::LEVEL_INFO) : $level_text = 'info';
				case (self::LEVEL_DEBUG) : $level_text = 'debug';
			}
			$filename = $config['log']['file'];
			$dirname = dirname($filename);
			if(!is_dir($dirname)) {
				mkdir($dirname, 0755, true);
			}
			$handle = fopen($filename, "a");
			fwrite($handle, $log['time'].' ['.$level_text.'] ['.$type.'] ['.$source.'] ['.$user.'] ['.$log['ip'].'] '.$description."\n");
			fclose($handle);
		}
		//save log in database
		if(isset($this->dbLevel) && $level <= $this->dbLevel) {
			//except do not save log in database if there is a problem with the database
			if(ConnectionProvider::isConnected()) {
				$query = sprintf('INSERT INTO logs(level, type, source, user, ip, description) VALUES(%s, "%s", "%s", "%s", "%s", "%s")',
					$level,
					$type,
					$source,
					$user,
					self::getConnection()->quote($log['ip']),
					self::getConnection()->quote($description));
				self::getConnection()->exec($query);
			}
		}
	}
}
