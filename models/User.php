<?php
/**
 * User model
 * @package models
 */
class User extends Model {

	/**
	 * Authenticate a user
	 * @param string $login login of the user
	 * @param string $password password of a user
	 */
	public static function authenticate($login, $password, $ip = null) {
		global $config;
		try {
			$user = $GLOBALS['datacontext']->getObjectFromProperty('User', 'login', $login);
		}
		catch(Exception $e) {
			throw new Exception('Invalid login');
		}
		if($user->password === md5($password)) {
			$user->connected = 1;
			return $user;
		}
		throw new Exception('Invalid password');
	}
}
