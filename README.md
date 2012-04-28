
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

## Layout

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
