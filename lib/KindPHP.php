<?php
/**
Copyright (c) 2012 Longhao Luo, http://www.kindsoft.net/

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

class KindPHP {

	public $defaultConfig = array(
		'debugMode' => false,
		'defaultController' => 'index',
		'defaultAction' => 'index',
		'defaultView' => 'index',
	);

	public function __construct($config) {
		$this->config = array_merge($this->defaultConfig, $config);
		if ($this->config['debugMode']) {
			error_reporting(E_ALL);
		}
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

		$controllerPath = APP_PATH . '/action/' . $controllerName . '.action.php';

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
		$object->$actionName($actionParams);
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
		$viewName = $viewName == null ? $this->defaultView : $viewName;
		$viewPath = APP_PATH . '/view/' . $this->controllerName . '/' . $viewName . '.view.php';
		extract($data);
		include_once $viewPath;
	}

}