
KindPHP
=================================================

KindPHP is a lightweight PHP framework.

## Features

* Simple and quick.
* Includes Frontend framework.
* No cache or configure files.

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

## Database

	// execute SQL
	$mysql = new Database();

	$sql = 'SELECT * FROM `member` LIMIT 10';
	$data = $mysql->select($sql);

	$sql = 'SELECT * FROM `member` WHERE `id`=1';
	$memberRow = $mysql->row($sql);

	$sql = 'SELECT `name` FROM `member` WHERE `id`=1';
	$name = $mysql->one($sql);

	$sql = "INSERT INTO `member` (`name`, `regtime`) VALUES ('" . $mysql->escape($name) . "', NOW())";
	$mysql->query($sql);

	// use Model
	$memberModel = Model('member');

	$data = $memberModel->limit(10)->select();

	$memberRow = $memberModel->where(array('id' => 1))->row();

	$name = $memberModel->where(array('id' => 1))->one('name');

	$memberModel->insert(array(
		'name' => $name,
		'regtime:function' => 'NOW()',
	));

	$memberModel->where(array('id' => 1))->update(array(
		'name' => $name,
	);
