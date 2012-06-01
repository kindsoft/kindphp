<?php

require_once dirname(__FILE__) . '/simpletest/autorun.php';
require_once dirname(__FILE__) .  '/../lib/KindPHP.php';
require_once dirname(__FILE__) . '/config.php';

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

		$data = $memberModel->where('name like ? AND regtime>=? AND regtime<=?', array('%ro%', '2012-01-01 00:00:00', date('Y-m-d H:i:s')))->order('name DESC')->limit('0,20')->all();
		$this->assertEqual(count($data), 1);

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