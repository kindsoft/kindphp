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

		return new PDO($dsnMap['scheme'] . ':host=' . $dsnMap['host'] . ';port=' . $dsnMap['port'] . ';dbname=' . $dsnMap['database'],
			$dsnMap['username'], $dsnMap['password']);
	}

	// Returns an array containing all of the result set rows
	public function selectAll($sql, $bindParams = array(), $useMaster = false) {
		if ($useMaster) {
			$sth = self::$dbhMaster->prepare($sql);
		} else {
			$sth = self::$dbhSlave->prepare($sql);
		}

		$sth->execute($bindParams);

		return $sth->fetchAll();
	}

	// Fetches the first row from a result set
	public function selectRow($sql, $bindParams = array(), $useMaster = false) {
		if ($useMaster) {
			$sth = self::$dbhMaster->prepare($sql);
		} else {
			$sth = self::$dbhSlave->prepare($sql);
		}

		$sth->execute($bindParams);

		$result = $sth->fetch();

		return $result ? $result : array();
	}

	// Fetches the first column of the first row from a result set
	public function selectOne($sql, $bindParams = array(), $useMaster = false) {
		$row = $this->selectRow($sql, $bindParams, $useMaster);

		return isset($row[0]) ? $row[0] : null;
	}

	// Executes an SQL statement
	public function execute($sql, $bindParams = array()) {
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

	public function where($map) {
		$where = '';
		foreach ($map as $key => $val) {
			$where .= ' AND ' . $key . '=?';
			$this->bindParams[] = $val;
		}
		$where = substr($where, 5);

		$this->whereSql = 'WHERE ' . $where;

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

	public function all($fields = array()) {
		$sql = $this->makeSelectSql($fields);

		$result = $this->selectAll($sql, $this->bindParams);

		$this->resetSql();

		return $result;
	}

	public function row($fields = array()) {
		$sql = $this->makeSelectSql($fields);

		$result = $this->selectRow($sql, $this->bindParams);

		$this->resetSql();

		return $result;
	}

	public function one($field) {
		$sql = $this->makeSelectSql(array($field));

		$result = $this->selectOne($sql, $this->bindParams);

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

		$sql = 'INSERT INTO ' . $this->tableName . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $values) . ')';

		$result = $this->execute($sql, $this->bindParams);

		$this->resetSql();

		return $result;
	}

	public function update($map) {
		$set = '';
		$bindParams = array();
		foreach ($map as $key => $val) {
			if ($key{0} === '@') {
				$set .= ',' . substr($key, 1) . '=' . $val;
			} else {
				$set .= ',' . $key . '=?';
				$bindParams[] = $val;
			}
		}
		$set = substr($set, 1);
		$bindParams = array_merge($bindParams, $this->bindParams);

		$sql = 'UPDATE ' . $this->tableName . ' SET ' . $set;

		if ($this->whereSql !== null) {
			$sql .= ' ' . $this->whereSql;
		}

		$result = $this->execute($sql, $bindParams);

		$this->resetSql();

		return $result;
	}

	public function delete() {
		$sql = 'DELETE FROM ' . $this->tableName;

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

		$sql = 'SELECT ' . $selectExpr . ' FROM ' . $this->tableName;

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
