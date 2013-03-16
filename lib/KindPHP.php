<?php
/*******************************************************************************
* KindPHP - a lightweight PHP framework
* Copyright (c) 2012 Longhao Luo, http://www.kindsoft.net/
*
* @author Roddy <luolonghao@gmail.com>
* @licence MIT licence
* @version 1.0
*******************************************************************************/

define('DS', DIRECTORY_SEPARATOR);

define('LIB_PATH', dirname(__FILE__));

if (!defined('APP_PATH')) {
	define('APP_PATH', LIB_PATH);
}

define('CONTROLLER_PATH', APP_PATH . DS .'controller');

define('VIEW_PATH', APP_PATH . DS . 'view');

define('MODULE_PATH', APP_PATH . DS . 'module');

class KindPHP {

	public $defaultConfig = array(
		'debugMode' => true,

		'autoload' => array(),

		'defaultController' => 'index',

		'defaultAction' => 'index',

		'defaultView' => 'index',

		'paramPattern' => '/^\d+$/',

		'dsnMaster' => '',

		'dsnSlave' => '',

		'staticTime' => '20120516',
	);

	public function __construct($config) {

		$appName = substr(strrchr(APP_PATH, DS), 1);

		$scriptName = dirname($_SERVER['SCRIPT_NAME']);

		$rootUrl = substr($scriptName, 0, strripos($scriptName, '/'));

		$this->defaultConfig['appName'] = $appName;

		$this->defaultConfig['appUrl'] = $rootUrl . '/' . $appName;

		$this->defaultConfig['staticUrl'] = $rootUrl . '/static';

		$this->defaultConfig['autoload'] = array(MODULE_PATH . '/common.php');

		$this->config = array_merge($this->defaultConfig, $config);

		define('DEBUG_MODE', $this->config['debugMode']);

		if (strpos($_SERVER['REQUEST_URI'] . '/', '/index.php/') !== false) {
			self::notFound('Cannot includes index.php in the request URL. URL: ' . $_SERVER['REQUEST_URI']);
		}

		define('APP_URL', $this->config['appUrl']);

		define('STATIC_URL', $this->config['staticUrl']);

		define('STATIC_TIME', $this->config['staticTime']);

		define('DSN_MASTER', $this->config['dsnMaster']);

		define('DSN_SLAVE', $this->config['dsnSlave']);

		// load controller
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

		define('CONTROLLER_NAME', $controllerName);
		define('ACTION_NAME', $actionName);

		foreach ($this->config['autoload'] as $path) {
			if (file_exists($path)) {
				require_once $path;
			}
		}

		$controllerPath = CONTROLLER_PATH . DS . $controllerName . '.php';

		require_once $controllerPath;
		if (!file_exists($controllerPath)) {
			self::notFound('File not found: ' . $controllerPath);
		}

		$className = self::toCamelName($controllerName) . 'Controller';
		if (!class_exists($className)) {
			self::notFound('Controller not found: ' . $className);
		}

		$object = new $className();
		if (!method_exists($object, $actionName)) {
			self::notFound('Action not found: ' . $actionName);
		}

		$object->controllerName = $controllerName;
		$object->defaultView = $this->config['defaultView'];

		call_user_func_array(array($object, $actionName), $actionParams);
	}

	public static function httpError($message, $httpMessage) {
		if (DEBUG_MODE) {
			throw new Exception($message);
		} else {
			header('HTTP/1.0 ' . $httpMessage);
			exit;
		}
	}

	public static function notFound($message) {
		self::httpError($message, '404 Not Found');
	}

	public static function toCamelName($string) {
		$array = explode('-', $string);

		$array = array_map(function($val) {
			return ucwords($val);
		}, $array);

		return implode('', $array);
	}

	public static function redirect($path) {
		$url = preg_match('/https?:\/\//i', $path) ? $path : APP_URL . $path;
		if (headers_sent()) {
			echo '<script>location.href="' . $url . '";</script>';
		} else {
			header("Location: " . $url);
		}
		exit;
	}

	// Print absolute URL
	public static function url($path) {
		echo APP_URL . $path;
	}

	// Print link tag
	public static function css($path) {
		echo '<link href="' . STATIC_URL . $path . '?t=' . STATIC_TIME . '.css" rel="stylesheet">' . "\n";
	}

	// Print script tag
	public static function js($path) {
		echo '<script src="' . STATIC_URL . $path . '?t=' . STATIC_TIME . '.js"></script>' . "\n";
	}

}


class Controller {

	public function render($data = array(), $viewName = null) {
		extract($data);

		require_once VIEW_PATH . DS . $this->controllerName . DS . ($viewName == null ? $this->defaultView : $viewName) . '.view.php';
	}

}


class Database {

	public static $dbhMaster;

	public static $dbhSlave;

	public function __construct() {
		$this->connect();
	}

	// Connect to database
	public function connect() {
		$dsnMaster = DSN_MASTER;
		$dsnSlave = DSN_SLAVE;

		if (!self::$dbhMaster) {
			if (DEBUG_MODE) {
				error_log('Connect master database. DSN: ' . $dsnMaster);
			}
			self::$dbhMaster = self::createInstance($dsnMaster);
		}

		if (!self::$dbhSlave) {
			if ($dsnMaster === $dsnSlave) {
				self::$dbhSlave = self::$dbhMaster;
			} else {
				if (DEBUG_MODE) {
					error_log('Connect slave database. DSN: ' . $dsnSlave);
				}
				self::$dbhSlave = self::createInstance($dsnSlave);
			}
		}
	}

	// Close database connection
	public function close() {
		self::$dbhMaster = null;
		self::$dbhSlave = null;
	}

	// Returns an array containing all of the result set rows
	public function selectAll($sql, $bindParams = array(), $useMaster = false) {
		if (DEBUG_MODE) {
			error_log('SQL: ' . $sql . '; PARAMS: ("' . implode('","', $bindParams) . '"); MASTER: ' . ($useMaster ? 'true' : 'false'));
		}

		if ($useMaster) {
			$sth = self::$dbhMaster->prepare($sql);
		} else {
			$sth = self::$dbhSlave->prepare($sql);
		}

		$sth->execute($bindParams);

		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

	// Fetches the first row from a result set
	public function selectRow($sql, $bindParams = array(), $useMaster = false) {
		if (DEBUG_MODE) {
			error_log('SQL: ' . $sql . '; PARAMS: ("' . implode('","', $bindParams) . '"); MASTER: ' . ($useMaster ? 'true' : 'false'));
		}

		if ($useMaster) {
			$sth = self::$dbhMaster->prepare($sql);
		} else {
			$sth = self::$dbhSlave->prepare($sql);
		}

		$sth->execute($bindParams);

		$result = $sth->fetch(PDO::FETCH_ASSOC);

		return $result ? $result : array();
	}

	// Fetches the first column of the first row from a result set
	public function selectOne($sql, $bindParams = array(), $useMaster = false) {
		$row = $this->selectRow($sql, $bindParams, $useMaster);

		$values = array_values($row);

		return isset($values[0]) ? $values[0] : null;
	}

	// Executes an SQL statement
	public function execute($sql, $bindParams = array()) {
		if (DEBUG_MODE) {
			error_log('SQL: ' . $sql . '; PARAMS: ("' . implode('","', $bindParams) . '"); MASTER: true');
		}

		$sth = self::$dbhMaster->prepare($sql);

		return $sth->execute($bindParams);
	}

	// Returns the ID of the last inserted row or sequence value
	public function lastInsertId($name = null) {
		return self::$dbhMaster->lastInsertId($name);
	}

	// Parses DSN string, mysql://username:passwd@localhost:3306/DbName
	private static function parseDSN($dsn) {
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

	private static function createInstance($dsn) {
		$dsnMap = self::parseDSN($dsn);

		$dbh = new PDO($dsnMap['scheme'] . ':host=' . $dsnMap['host'] . ';port=' . $dsnMap['port'] . ';dbname=' . $dsnMap['database'],
			$dsnMap['username'], $dsnMap['password']);

		if (DEBUG_MODE) {
			$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}

		return $dbh;
	}
}


class Model extends Database {

	public $tableName;

	public $whereSql;

	public $orderSql;

	public $limitSql;

	public $bindParams = array();

	public function __construct($tableName) {
		$this->tableName = $tableName;

		parent::__construct();
	}

	public function where($map, $bindParams = array()) {
		if (is_string($map) && $map !== '') {
			$this->whereSql = 'WHERE ' . $map;
			$this->bindParams = array_merge($this->bindParams, $bindParams);
			return $this;
		}

		if (is_array($map) && count($map) > 0) {
			$where = '';
			foreach ($map as $key => $val) {
				$where .= ' AND `' . $key . '`=?';
				$this->bindParams[] = $val;
			}
			$where = substr($where, 5);

			$this->whereSql = 'WHERE ' . $where;
		}
		return $this;
	}

	public function order($order) {
		$this->orderSql = 'ORDER BY ' . $order;

		return $this;
	}

	public function limit($limit) {
		$this->limitSql = 'LIMIT ' . $limit;

		return $this;
	}

	public function all($fields = array(), $useMaster = false) {
		$sql = $this->makeSelectSql($fields);

		$result = $this->selectAll($sql, $this->bindParams, $useMaster);

		$this->resetSql();

		return $result;
	}

	public function row($fields = array(), $useMaster = false) {
		$sql = $this->makeSelectSql($fields);

		$result = $this->selectRow($sql, $this->bindParams, $useMaster);

		$this->resetSql();

		return $result;
	}

	public function one($field, $useMaster = false) {
		$sql = $this->makeSelectSql(array($field));

		$result = $this->selectOne($sql, $this->bindParams, $useMaster);

		$this->resetSql();

		return $result;
	}

	public function count() {
		return $this->one('COUNT(*)');
	}

	public function insert($map) {
		$fields = array();
		$values = array();
		foreach ($map as $key => $val) {
			if ($key{0} === '@') {
				$fields[] = substr($key, 1);
				$values[] = $val;
			} else {
				$fields[] = $key;
				$values[] = '?';
				$this->bindParams[] = $val;
			}
		}

		$sql = 'INSERT INTO `' . $this->tableName . '` (`' . implode('`,`', $fields) . '`) VALUES (' . implode(',', $values) . ')';

		$result = $this->execute($sql, $this->bindParams);

		$this->resetSql();

		return $result;
	}

	public function update($map) {
		$set = '';
		$bindParams = array();
		foreach ($map as $key => $val) {
			if ($key{0} === '@') {
				$set .= ',`' . substr($key, 1) . '`=' . $val;
			} else {
				$set .= ',`' . $key . '`=?';
				$bindParams[] = $val;
			}
		}
		$set = substr($set, 1);
		$bindParams = array_merge($bindParams, $this->bindParams);

		$sql = 'UPDATE `' . $this->tableName . '` SET ' . $set;

		if ($this->whereSql !== null) {
			$sql .= ' ' . $this->whereSql;
		}

		$result = $this->execute($sql, $bindParams);

		$this->resetSql();

		return $result;
	}

	public function delete() {
		$sql = 'DELETE FROM `' . $this->tableName . '`';

		if ($this->whereSql !== null) {
			$sql .= ' ' . $this->whereSql;
		}

		$result = $this->execute($sql, $this->bindParams);

		$this->resetSql();

		return $result;
	}

	private function makeSelectSql($fields = array()) {
		if (count($fields) > 0) {
			$selectExpr = implode(',', $fields);
		} else {
			$selectExpr = '*';
		}

		$sql = 'SELECT ' . $selectExpr . ' FROM `' . $this->tableName . '`';

		if ($this->whereSql !== null) {
			$sql .= ' ' . $this->whereSql;
		}

		if ($this->orderSql !== null) {
			$sql .= ' ' . $this->orderSql;
		}

		if ($this->limitSql !== null) {
			$sql .= ' ' . $this->limitSql;
		}
		return $sql;
	}

	private function resetSql() {
		$this->whereSql = null;

		$this->orderSql = null;

		$this->limitSql = null;

		$this->bindParams = array();
	}

}
