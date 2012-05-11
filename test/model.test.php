<?php

require_once dirname(__FILE__) . '/simpletest/autorun.php';
require_once dirname(__FILE__) .  '/../lib/KindPHP.php';

/*
CREATE DATABASE test;

CREATE TABLE IF NOT EXISTS `member` (
	`id` bigint(20) NOT NULL AUTO_INCREMENT,
	`name` varchar(50) NOT NULL,
	`regtime` datetime NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
*/

define('DSN_MASTER', 'mysql://root:1234@localhost/test');
define('DSN_SLAVE', 'mysql://root:1234@localhost/test');

class TestOfModel extends UnitTestCase {

	function testEmptySelect() {
		$memberModel = new Model('member');

		$data = $memberModel->all();
		$this->assertEqual(count($data), 0);

		$data = $memberModel->limit('10,10')->all();
		$this->assertEqual(count($data), 0);

		$data = $memberModel->order('name')->limit(10)->all();
		$this->assertEqual(count($data), 0);

		$row = $memberModel->where(array('id' => 1))->order('name')->limit(10)->row();
		$this->assertEqual(count($row), 0);

		$name = $memberModel->where(array('id' => 1))->one('name');
		$this->assertNull($name);
	}

	function testNotEmpty() {
		$memberModel = new Model('member');

		// insert
		$result = $memberModel->insert(array(
			'name' => 'roddy',
			'@regtime' => 'NOW()',
		));
		$this->assertTrue($result);

		// select
		$data = $memberModel->order('name')->limit(10)->all(array('name', 'regtime'));
		$this->assertEqual(count($data), 1);
		$this->assertEqual($data[0]['name'], 'roddy');
		$this->assertEqual(substr($data[0]['regtime'], 0, 10), date('Y-m-d'));

		$row = $memberModel->order('name')->limit(10)->row(array('name', 'regtime'));
		$this->assertEqual($row['name'], 'roddy');
		$this->assertEqual(substr($row['regtime'], 0, 10), date('Y-m-d'));

		$name = $memberModel->where(array('name' => 'roddy'))->one('name');
		$this->assertEqual($name, 'roddy');

		// update
		$result = $memberModel->where(array('name' => 'roddy'))->update(array(
			'name' => 'roddy++',
			'@regtime' => 'NOW()',
		));
		$this->assertTrue($result);

		$count = $memberModel->where(array('name' => 'roddy++'))->count();
		$this->assertEqual($count, 1);

		// delete
		$result = $memberModel->where(array('name' => 'roddy++'))->delete();
		$this->assertTrue($result);

		$count = $memberModel->where(array('name' => 'roddy++'))->count();
		$this->assertEqual($count, 0);
	}

}