<?php

require_once dirname(__FILE__) . '/simpletest/autorun.php';

class AllTests extends TestSuite {
	function AllTests() {
		$this->TestSuite('All tests');
		$this->addFile('core.test.php');
		$this->addFile('database.test.php');
		$this->addFile('model.test.php');
	}
}