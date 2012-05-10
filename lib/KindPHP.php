<?php
/*******************************************************************************
* KindPHP - a lightweight PHP framework
* Copyright (c) 2012 Longhao Luo, http://www.kindsoft.net/
*
* @author Roddy <luolonghao@gmail.com>
* @licence MIT licence
* @version 1.0
*******************************************************************************/

define('CONTROLLER_PATH', APP_PATH . '/controller');
define('VIEW_PATH', APP_PATH . '/view');

class KindPHP {

	public $defaultConfig = array(
		'debugMode' => true,
		'defaultController' => 'index',
		'defaultAction' => 'index',
		'defaultView' => 'index',
		'paramPattern' => '/^\d+$/',
		'dsnMaster' => '',
		'dsnSlave' => '',
	);

	public function __construct($config) {
		$this->defaultConfig['appName'] = substr(strrchr(APP_PATH, '/'), 1);

		$appUrl = dirname($_SERVER['SCRIPT_NAME']);

		$this->defaultConfig['staticUrl'] = substr($appUrl, 0, strripos($appUrl, '/')) . '/static';

		$this->config = array_merge($this->defaultConfig, $config);

		define('DEBUG_MODE', $this->config['debugMode']);

		error_reporting(DEBUG_MODE ? E_ALL : 0);

		if (strpos($_SERVER['REQUEST_URI'] . '/', '/index.php/') !== false) {
			self::notFound('Cannot includes index.php in the request URL. URL: ' . $_SERVER['REQUEST_URI']);
		}

		define('STATIC_URL', $this->config['staticUrl']);
		define('DSN_MASTER', $this->config['dsnMaster']);
		define('DSN_SLAVE', $this->config['dsnSlave']);

		$this->load();
	}

	private function isParam($param) {
		return $param !== '' && preg_match($this->config['paramPattern'], $param);
	}

	private function load() {
		$pathInfo = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
		$params = $pathInfo !== '' ? explode('/', $pathInfo) : array();

		$first = isset($params[0]) ? $params[0] : '';
		$second = isset($params[1]) ? $params[1] : '';
		$actionParams = isset($params[2]) ? array_slice($params, 2) : array();

		$controllerName = $first !== '' ? $first : $this->config['defaultController'];
		$actionName = $second !== '' ? $second : $this->config['defaultAction'];

		if ($this->isParam($first) || $this->isParam($second)) {
			$actionName = $this->config['defaultAction'];
		}

		if ($this->isParam($second)) {
			array_unshift($actionParams, $second);
		}

		if ($this->isParam($first)) {
			array_unshift($actionParams, $first);
			$controllerName = $this->config['defaultController'];
		}

		$controllerPath = CONTROLLER_PATH . '/' . $controllerName . '.php';

		include_once $controllerPath;

		$className = self::toCamelName($controllerName) . 'Controller';
		if (!class_exists($className)) {
			self::notFound('Cannot find the controller: ' . $className);
		}

		$object = new $className();
		if (!method_exists($object, $actionName)) {
			self::notFound('Cannot find the action: ' . $actionName);
		}

		$object->controllerName = $controllerName;
		$object->defaultView = $this->config['defaultView'];

		switch (count($actionParams)) {
			case 0:
				$object->$actionName();
				break;
			case 1:
				$object->$actionName($actionParams[0]);
				break;
			case 2:
				$object->$actionName($actionParams[0], $actionParams[1]);
				break;
			case 3:
				$object->$actionName($actionParams[0], $actionParams[1], $actionParams[2]);
				break;
			case 4:
				$object->$actionName($actionParams[0], $actionParams[1], $actionParams[2], $actionParams[3]);
				break;
			case 5:
				$object->$actionName($actionParams[0], $actionParams[1], $actionParams[2], $actionParams[3], $actionParams[4]);
				break;
			default:
				$object->$actionName($actionParams);
				break;
		}
	}

	public static function error($message, $httpMessage) {
		if (DEBUG_MODE) {
			throw new Exception($message);
		} else {
			header('HTTP/1.0 ' . $httpMessage);
			exit;
		}
	}

	public static function notFound($message) {
		self::error($message, '404 Not Found');
	}

	public static function toCamelName($string) {
		$array = explode('-', $string);

		$array = array_map(function($val) {
			return ucwords($val);
		}, $array);

		return implode('', $array);
	}

}


class Controller {

	public function render($data = array(), $viewName = null) {
		extract($data);

		include_once VIEW_PATH . '/' . $this->controllerName . '/' . ($viewName == null ? $this->defaultView : $viewName) . '.view.php';
	}

}


class Database {

	public static $dbhMaster;
	public static $dbhSlave;

	// Creates a Database instance
	public function __construct() {
		$dsnMaster = DSN_MASTER;
		$dsnSlave = DSN_SLAVE;

		if (!self::$dbhMaster) {
			self::$dbhMaster = self::connect($dsnMaster);
		}

		if (!self::$dbhSlave) {
			if ($dsnMaster === $dsnSlave) {
				self::$dbhSlave = self::$dbhMaster;
			} else {
				self::$dbhSlave = self::connect($dsnSlave);
			}
		}
	}

	// Connect to a database
	private static function connect($dsn) {
		$dsnMap = self::parseDSN($dsn);

		try {
			return new PDO($dsnMap['scheme'] . ':host=' . $dsnMap['host'] . ';port=' . $dsnMap['port'] . ';dbname=' . $dsnMap['database'],
				$dsnMap['username'], $dsnMap['password']);
		} catch (PDOException $e) {
			self::error('Connection failed: ' . $e->getMessage());
		}
	}

	// Returns an array containing all of the result set rows
	public function select($sql, $bindParams = array(), $useMaster = false) {
		if ($useMaster) {
			$sth = self::$dbhMaster->prepare($sql);
		} else {
			$sth = self::$dbhSlave->prepare($sql);
		}

		$sth->execute($bindParams);

		return $sth->fetchAll();
	}

	// Fetches the first row from a result set
	public function row($sql, $bindParams = array(), $useMaster = false) {
		if ($useMaster) {
			$sth = self::$dbhMaster->prepare($sql);
		} else {
			$sth = self::$dbhSlave->prepare($sql);
		}

		$sth->execute($bindParams);

		return $sth->fetch();
	}

	// Fetches the first column of the first row from a result set
	public function one($sql, $bindParams = array(), $useMaster = false) {
		$row = $this->row($sql, $bindParams, $useMaster);

		return isset($row[0]) ? $row[0] : null;
	}

	// Executes an SQL statement
	public function exec($sql, $bindParams = array()) {
		$sth = self::$dbhMaster->prepare($sql);

		return $sth->execute($bindParams);
	}

	// Parses DSN string, mysql://username:passwd@localhost:3306/DbName
	public static function parseDSN($dsn) {
		$info = parse_url($dsn);

		return array(
			'scheme' => $info['scheme'],
			'username' => isset($info['user']) ? $info['user'] : '',
			'password' => isset($info['pass']) ? $info['pass'] : '',
			'host' => isset($info['host']) ? $info['host'] : '',
			'port' => isset($info['port']) ? $info['port'] : '',
			'database'   => isset($info['path']) ? substr($info['path'], 1) : '',
		);
	}

	public static function error($message) {
		KindPHP::error($message, '500 Internal Server Error');
	}

}


class Module {

	public $tableName;
	public $limitNum;

	public function __construct($tableName) {
		$this->tableName = $tableName;
	}

	public function limit($limitNum) {
		$this->limitNum = $limitNum;
		return $this;
	}

	public function select() {
		$this->limitNum = $limitNum;
	}

}
