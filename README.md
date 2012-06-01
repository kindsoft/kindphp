
KindPHP
=================================================

KindPHP is a lightweight PHP framework.

## Features

* Simple and quick.
* Includes Frontend framework.
* No cache files or configure files.

## URL Routing

	URL: http://www.app.com/100
	Mapping: IndexController->index(100)

	URL: http://www.app.com/member/100/2
	Mapping: MemberController->index(100, 2)

	URL: http://www.app.com/member/edit/100
	Mapping: MemberController->edit(100)

	URL: http://www.app.com/post/view/recent/2001
	Mapping: PostController->view('recent', 2001)

## Directory Structure

	lib/
		KindPHP.php
	app/
		controller/
			index.php
		view/
			index/
				index.view.php
		lib/
			common.php
		.htaccess
		index.php
	static/
		bootstrap/
			css/
			img/
			js/
		jquery/
			jquery.min.js
		seajs/
			seajs.js
		app/
			css/
			img/
			js/

## Access database

```php
<?php

// execute SQL
$mysql = new Database();

$sql = 'SELECT * FROM `member` LIMIT 10';
$data = $mysql->selectAll($sql);

$sql = 'SELECT * FROM `member` WHERE `id`=?';
$memberRow = $mysql->selectRow($sql, array(1));

$sql = 'SELECT `name` FROM `member` WHERE `id`=?';
$name = $mysql->selectOne($sql, array(1));

$sql = "INSERT INTO `member` (`name`, `regtime`) VALUES (?, NOW())";
$mysql->exec($sql, array('roddy'));

// use Model
$memberModel = new Model('member');

$data = $memberModel->limit(10)->all();

$data = $memberModel->where('name like %?% AND regtime>=?', array('ro', '2012-01-01 00:00:00'))->order('name DESC')->limit('0,20')->all();

$memberRow = $memberModel->where(array('id' => 1))->row();

$name = $memberModel->where(array('id' => 1))->one('name');

$count = $memberModel->count();

$memberModel->insert(array(
	'name' => $name,
	'@regtime' => 'NOW()',
));

$memberModel->where(array('id' => 1))->update(array(
	'name' => $name,
);

?>
```
