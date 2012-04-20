
KindPHP
=================================================

KindPHP is a lightweight PHP framework.

## Features

* Simple and quick.
* Includes Frontend framework.
* No cache or configure files.

## URL Routing

	URL: http://www.app.com/100
	Mapping: IndexAction->index(100)

	URL: http://www.app.com/member/100/2
	Mapping: MemberAction->index(100, 2)

	URL: http://www.app.com/member/edit/100
	Mapping: MemberAction->edit(100)

	URL: http://www.app.com/post/view/recent/2001
	Mapping: PostAction->view('recent', 2001)

## Layout

	lib/
		KindPHP.php
	app/
		action/
			index.action.php
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
		app/
			css/
			img/
			js/
