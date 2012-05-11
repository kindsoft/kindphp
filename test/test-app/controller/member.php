<?php

class MemberController extends Controller {

	public function index($id) {
		echo 'MemberController::index' . $id;

	}

	public function edit($id) {
		echo 'MemberController::edit' . $id;
	}

}