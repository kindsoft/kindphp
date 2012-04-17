
KindPHP
=================================================

KindPHP is a lightweight PHP framework.

## URL

	# Call IndexAction::index at action/index.action.php, The first parameter is 100.
	http://www.application.com/100

	# Call MemberAction::index at action/member.action.php, The first parameter is 100.
	http://www.application.com/member/100

	# Call MemberAction::edit at action/member.action.php, The first parameter is 100.
	http://www.application.com/member/edit/100


## layout

	lib/
		KindPHP.php
	application/
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
