<?php

// CREATE DATABASE `test`;

define('DSN_MASTER', 'mysql://root:1234@localhost/test');

define('DSN_SLAVE', 'mysql://root:1234@localhost/test');

define('TEST_APP_URL', 'http://' . $_SERVER['HTTP_HOST'] . substr($_SERVER['SCRIPT_NAME'], 0, strripos($_SERVER['SCRIPT_NAME'], '/')));
