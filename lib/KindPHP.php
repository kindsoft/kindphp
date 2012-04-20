<?php

class KindPHP {

	public $defaultConfig = array(
		'debugMode' => false,
		'defaultController' => 'index',
		'defaultAction' => 'index',
		'defaultView' => 'index',
	);

	public function __construct($config) {
		$this->defaultConfig['appName'] = substr(strrchr(APP_PATH, '/'), 1);
		$appUrl = dirname($_SERVER['SCRIPT_NAME']);
		$this->defaultConfig['staticUrl'] = substr($appUrl, 0, strripos($appUrl, '/')) . '/static';

		$this->config = array_merge($this->defaultConfig, $config);
		if ($this->config['debugMode']) {
			error_reporting(E_ALL);
		}

		define('ACTION_PATH', APP_PATH . '/action');
		define('VIEW_PATH', APP_PATH . '/view');
		define('STATIC_URL', $this->config['staticUrl']);

		$this->load();
	}

	private function load() {
		$pathInfo = trim($_SERVER['PATH_INFO'], '/');
		$pathArray = $pathInfo !== '' ? explode('/', $pathInfo) : array();

		$controllerName = isset($pathArray[0]) ? $pathArray[0] : $this->config['defaultController'];
		$actionName = isset($pathArray[1]) ? $pathArray[1] : $this->config['defaultAction'];
		$actionParams = isset($pathArray[2]) ? array_slice($pathArray, 2) : array();

		if (isset($pathArray[0]) && is_numeric($pathArray[0]) || isset($pathArray[1]) && is_numeric($pathArray[1])) {
			$actionName = $this->config['defaultAction'];
		}

		if (isset($pathArray[1]) && is_numeric($pathArray[1])) {
			array_unshift($actionParams, $pathArray[1]);
		}

		if (isset($pathArray[0]) && is_numeric($pathArray[0])) {
			array_unshift($actionParams, $pathArray[0]);
			$controllerName = $this->config['defaultController'];
		}

		$controllerPath = ACTION_PATH . '/' . $controllerName . '.action.php';

		if (!file_exists($controllerPath)) {
			$this->notFound('Cannot find the file. Path: ' . $controllerPath);
		}

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
