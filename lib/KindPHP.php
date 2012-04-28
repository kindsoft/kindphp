<?php
/*******************************************************************************
* KindPHP - a lightweight PHP framework
* Copyright (c) 2012 Longhao Luo, http://www.kindsoft.net/
*
* @author Roddy <luolonghao@gmail.com>
* @licence MIT licence
* @version 1.0
*******************************************************************************/

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
		if ($this->config['debugMode']) {
			error_reporting(E_ALL);
		}

		if (strpos($_SERVER['REQUEST_URI'] . '/', '/index.php/') !== false) {
			$this->notFound('Cannot includes index.php in the request URL. URL: ' . $_SERVER['REQUEST_URI']);
		}

		define('CONTROLLER_PATH', APP_PATH . '/controller');
		define('VIEW_PATH', APP_PATH . '/view');
		define('STATIC_URL', $this->config['staticUrl']);
		define('DSN_MASTER', $this->config['dsnMaster']);
		define('DSN_SLAVE', $this->config['dsnSlave']);

		$this->load();
	}

	private function isParam($param) {
		return $param !== '' && preg_match($this->config['paramPattern'], $param);
	}

	private function load() {
		$pathInfo = trim($_SERVER['PATH_INFO'], '/');
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
			$this->notFound('Cannot find the controller. Name: ' . $className);
		}
		$object = new $className();
		if (!method_exists($object, $actionName)) {
			$this->notFound('Cannot find the action. Name: ' . $actionName);
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

	public function notFound($message) {
		if ($this->config['debugMode']) {
			throw new Exception($message);
		} else {
			header('HTTP/1.0 404 Not Found');
			exit;
		}
	}

	public static function toCamelName($str) {
		$arr = explode('-', $str);
		$arr = array_map(function($val) {
			return ucwords($val);
		}, $arr);
		return implode('', $arr);
	}

}

// controller class
class Controller {

	public function render($data = array(), $viewName = null) {
		extract($data);
		include_once VIEW_PATH . '/' . $this->controllerName . '/' . ($viewName == null ? $this->defaultView : $viewName) . '.view.php';
	}

}

// database class
class Database {

	public function __construct($dsnMaster = '', $dsnSlave = '') {
		if ($dsnMaster == '') {
			$dsnMaster = DSN_MASTER;
		}
		if ($dsnSlave == '') {
			$dsnSlave = DSN_SLAVE;
		}

		$needReplication = ($dsnMaster !== $dsnSlave);

		$master = self::parseDSN($dsnMaster);
		$this->dbhMaster = new PDO($master['phptype'] . ':host=' . $master['hostspec'] . ';port=' . $master['port'] . ';dbname=' . $master['database'],
			$master['username'], $master['password']);

		$this->dbhSlave = null;
		if ($needReplication) {
			$slave = self::parseDSN($dsnSlave);
			$this->dbhSlave = new PDO($slave['phptype'] . ':host=' . $slave['hostspec'] . ';port=' . $slave['port'] . ';dbname=' . $slave['database'],
				$slave['username'], $slave['password']);
		}
	}

	// from PEAR::DB, http://pear.php.net/package/DB
	public static function parseDSN($dsn) {
		$parsed = array(
			'phptype'  => false,
			'dbsyntax' => false,
			'username' => false,
			'password' => false,
			'protocol' => false,
			'hostspec' => false,
			'port'     => false,
			'socket'   => false,
			'database' => false,
		);

		if (is_array($dsn)) {
			$dsn = array_merge($parsed, $dsn);
			if (!$dsn['dbsyntax']) {
				$dsn['dbsyntax'] = $dsn['phptype'];
			}
			return $dsn;
		}

		// Find phptype and dbsyntax
		if (($pos = strpos($dsn, '://')) !== false) {
			$str = substr($dsn, 0, $pos);
			$dsn = substr($dsn, $pos + 3);
		} else {
			$str = $dsn;
			$dsn = null;
		}

		// Get phptype and dbsyntax
		// $str => phptype(dbsyntax)
		if (preg_match('|^(.+?)\((.*?)\)$|', $str, $arr)) {
			$parsed['phptype']  = $arr[1];
			$parsed['dbsyntax'] = !$arr[2] ? $arr[1] : $arr[2];
		} else {
			$parsed['phptype']  = $str;
			$parsed['dbsyntax'] = $str;
		}

		if (!count($dsn)) {
			return $parsed;
		}

		// Get (if found): username and password
		// $dsn => username:password@protocol+hostspec/database
		if (($at = strrpos($dsn,'@')) !== false) {
			$str = substr($dsn, 0, $at);
			$dsn = substr($dsn, $at + 1);
			if (($pos = strpos($str, ':')) !== false) {
				$parsed['username'] = rawurldecode(substr($str, 0, $pos));
				$parsed['password'] = rawurldecode(substr($str, $pos + 1));
			} else {
				$parsed['username'] = rawurldecode($str);
			}
		}

		// Find protocol and hostspec

		if (preg_match('|^([^(]+)\((.*?)\)/?(.*?)$|', $dsn, $match)) {
			// $dsn => proto(proto_opts)/database
			$proto       = $match[1];
			$proto_opts  = $match[2] ? $match[2] : false;
			$dsn         = $match[3];

		} else {
			// $dsn => protocol+hostspec/database (old format)
			if (strpos($dsn, '+') !== false) {
				list($proto, $dsn) = explode('+', $dsn, 2);
			}
			if (strpos($dsn, '/') !== false) {
				list($proto_opts, $dsn) = explode('/', $dsn, 2);
			} else {
				$proto_opts = $dsn;
				$dsn = null;
			}
		}

		// process the different protocol options
		$parsed['protocol'] = (!empty($proto)) ? $proto : 'tcp';
		$proto_opts = rawurldecode($proto_opts);
		if (strpos($proto_opts, ':') !== false) {
			list($proto_opts, $parsed['port']) = explode(':', $proto_opts);
		}
		if ($parsed['protocol'] == 'tcp') {
			$parsed['hostspec'] = $proto_opts;
		} elseif ($parsed['protocol'] == 'unix') {
			$parsed['socket'] = $proto_opts;
		}

		// Get dabase if any
		// $dsn => database
		if ($dsn) {
			if (($pos = strpos($dsn, '?')) === false) {
				// /database
				$parsed['database'] = rawurldecode($dsn);
			} else {
				// /database?param1=value1&param2=value2
				$parsed['database'] = rawurldecode(substr($dsn, 0, $pos));
				$dsn = substr($dsn, $pos + 1);
				if (strpos($dsn, '&') !== false) {
					$opts = explode('&', $dsn);
				} else { // database?param1=value1
					$opts = array($dsn);
				}
				foreach ($opts as $opt) {
					list($key, $value) = explode('=', $opt);
					if (!isset($parsed[$key])) {
						// don't allow params overwrite
						$parsed[$key] = rawurldecode($value);
					}
				}
			}
		}

		return $parsed;
	}
}

// model class
class Module {

	


}