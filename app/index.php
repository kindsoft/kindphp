<?php

define('APP_PATH', dirname(__FILE__));

require_once APP_PATH . '/../lib/KindPHP.php';

new KindPHP(array(
	'dbMaster' => 'mysql://root:@localhost/test',
	'dbSlave' => 'mysql://root:@localhost/test',
));
