<?php

class KindPHP {

	public $defaultConfig = array(
		'debugMode' => true,
		'defaultController' => 'index',
		'defaultAction' => 'index',
		'defaultView' => 'index',
		'paramPattern' => '/^\d+$/',
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

		define('ACTION_PATH', APP_PATH . '/action');
		define('VIEW_PATH', APP_PATH . '/view');
		define('STATIC_URL', $this->config['staticUrl']);

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

		$controllerPath = ACTION_PATH . '/' . $controllerName . '.action.php';

		include_once $controllerPath;

		$className = self::toCamelName($controllerName) . 'Action';
		if (!class_exists($className)) {
			$this->notFound('Cannot find the class. Name: ' . $className);
		}
		$object = new $className();
		if (!method_exists($object, $actionName)) {
			$this->notFound('Cannot find the method. Name: ' . $actionName);
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

class Action {

	public function render($data = array(), $viewName = null) {
		extract($data);
		include_once VIEW_PATH . '/' . $this->controllerName . '/' . ($viewName == null ? $this->defaultView : $viewName) . '.view.php';
	}

}
