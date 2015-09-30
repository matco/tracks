<?php
/**
 * Border controller
 * @package controllers
 */
class Border extends Component {

	private $start;
	private $stop;

	public function setupRender() {
		//time mesure
		$this->start = microtime(true);
		return true;
	}

	public function getUserIsAuthenticated() {
		return !empty($_SESSION['authenticated']);
	}

	public function getGenerationTime() {
		//calculation of generation time
		$this->stop = microtime(true);
		$time = $this->stop - $this->start;
		$time = round($time * 1000, 5);
		return sprintf('%d', $time);
	}

	public function getQueriesNumber() {
		return ConnectionProvider::getConnection()->getQueryNumber();
	}

	public function getUsername() {
		return $_SESSION['user']->firstname;
	}

	public function logout() {
		if(array_key_exists('authenticated', $_SESSION) and $_SESSION['authenticated']) {
			$_SESSION['authenticated'] = false;
			$user = $_SESSION['user'];
			unset($_SESSION['user']);
			$expiration = time() - 3600;
			$GLOBALS['logger']->addLog(Logger::LEVEL_INFO, Logger::TYPE_USER, __METHOD__, $user->login, 'User '.$user->login.' logged out successfully');
		}
	}
}
