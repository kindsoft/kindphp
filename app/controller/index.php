<?php

class IndexController extends Controller {

	public function index() {
		$this->render(array('pageTitle' => 'KindPHP'));
	}

}