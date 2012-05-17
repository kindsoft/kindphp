<?php

require_once dirname(__FILE__) . '/simpletest/autorun.php';
require_once dirname(__FILE__) .  '/../lib/KindPHP.php';
require_once dirname(__FILE__) . '/config.php';

class TestOfCore extends UnitTestCase {

	function testUrlRouting() {

		$result = file_get_contents(TEST_APP_URL . '/test-app');
		$this->assertEqual($result, 'IndexController::indexindex/index.view.php');

		$result = file_get_contents(TEST_APP_URL . '/test-app/index');
		$this->assertEqual($result, 'IndexController::indexindex/index.view.php');

		$result = file_get_contents(TEST_APP_URL . '/test-app/index/index');
		$this->assertEqual($result, 'IndexController::indexindex/index.view.php');

		$result = file_get_contents(TEST_APP_URL . '/test-app/10/20');
		$this->assertEqual($result, 'IndexController::indexindex/index.view.phpa=10b=20');

		$result = file_get_contents(TEST_APP_URL . '/test-app/index/10/20');
		$this->assertEqual($result, 'IndexController::indexindex/index.view.phpa=10b=20');

		$result = file_get_contents(TEST_APP_URL . '/test-app/index/10/recent');
		$this->assertEqual($result, 'IndexController::indexindex/index.view.phpa=10b=recent');

		$result = file_get_contents(TEST_APP_URL . '/test-app/index/index/10/recent');
		$this->assertEqual($result, 'IndexController::indexindex/index.view.phpa=10b=recent');

		$result = file_get_contents(TEST_APP_URL . '/test-app/index/view');
		$this->assertEqual($result, 'IndexController::view');

		$result = file_get_contents(TEST_APP_URL . '/test-app/member/10');
		$this->assertEqual($result, 'MemberController::index10');

		$result = file_get_contents(TEST_APP_URL . '/test-app/member/edit/10');
		$this->assertEqual($result, 'MemberController::edit10');

		// 404 requests
		$result = @file_get_contents(TEST_APP_URL . '/test-app/index.php/index/index');
		$this->assertFalse($result);

		$result = @file_get_contents(TEST_APP_URL . '/test-app/index.php/member/index');
		$this->assertFalse($result);

	}

}