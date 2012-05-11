<?php

define('APP_PATH', dirname(__FILE__));

require_once APP_PATH . '/../../lib/KindPHP.php';

new KindPHP(array(
	'debugMode' => false,
	'dsnMaster' => 'mysql://root:1234@localhost/test',
	'dsnSlave' => 'mysql://root:1234@localhost/test',
));
