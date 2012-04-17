<?php

class IndexAction extends Action {

	public function index($args) {
		print_r($args);
		$this->render(array('hello' => 'Hello KindPHP.'));
	}

}