<?php

class IndexAction extends Action {

	public function index() {
		$this->render(array('pageTitle' => 'KindPHP'));
	}

}