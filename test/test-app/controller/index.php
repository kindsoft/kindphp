<?php

class IndexController extends Controller {

	public function index($a = '', $b = '') {
		echo 'IndexController::index';
		$this->render();
		if ($a) {
			echo "a=$a";
		}
		if ($b) {
			echo "b=$b";
		}
	}

	public function view() {
		echo 'IndexController::view';
	}

}