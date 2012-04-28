<?php

class IndexController extends Controller {

	public function index() {
		$mysql = new Database();
		$this->render(array('pageTitle' => 'KindPHP'));
	}

}