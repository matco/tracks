<?php
/**
 * Login controller
 * @package controllers
 */
final class Login extends Component {

	public ?string $login;
	public ?string $password;
	public ?string $error;

	public function getHasError(): bool {
		return !empty($this->error);
	}

	public function authenticate(): void {
		$this->error = null;
		try {
			$user = User::authenticate($this->login, $this->password);
			$_SESSION['authenticated'] = true;
			$_SESSION['user'] = $user;
			$expiration = time() + 3600;
			$GLOBALS['logger']->addLog(Logger::LEVEL_INFO, Logger::TYPE_USER, __METHOD__, null, 'User '.$this->login.' logged in successfully');
		}
		catch(Exception $e) {
			$this->error = 'Wrong login or password';
		}
	}
}
