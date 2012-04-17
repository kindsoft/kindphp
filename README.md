
KindPHP
=================================================

KindPHP is a lightweight PHP framework.

## URL

	Call IndexAction::index at action/index.action.php, The first parameter is 100.
	http://www.app.com/100

	Call MemberAction::index at action/member.action.php, The first parameter is 100.
	http://www.app.com/member/100

	Call MemberAction::edit at action/member.action.php, The first parameter is 100.
	http://www.app.com/member/edit/100

## layout

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
		css
		img
		js
