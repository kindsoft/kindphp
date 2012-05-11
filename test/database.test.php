<?php

require_once dirname(__FILE__) . '/simpletest/autorun.php';
require_once dirname(__FILE__) .  '/../lib/KindPHP.php';
require_once dirname(__FILE__) . '/config.php';

class TestOfDatabase extends UnitTestCase {

	function testConnect() {

		$db1 = new Database();
		$this->assertIsA($db1, 'Database');

		$dbhMaster1 = Database::$dbhMaster;
		$dbhSlave1 = Database::$dbhSlave;

		$db2 = new Database();
		$this->assertIsA($db2, 'Database');

		$dbhMaster2 = Database::$dbhMaster;
		$dbhSlave2 = Database::$dbhSlave;

		$this->assertEqual($dbhMaster1, $dbhMaster2);
		$this->assertEqual($dbhSlave1, $dbhSlave2);

	}

	function testQuery() {
		$db = new Database();

		// create table
$sql = <<<END
CREATE TABLE IF NOT EXISTS `member` (
	`id` bigint(20) NOT NULL AUTO_INCREMENT,
	`name` varchar(50) NOT NULL,
	`regtime` datetime NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
END;
		$result = $db->execute($sql);
		$this->assertTrue($result);

		// show tables
		$sql = 'SHOW TABLES';
		$data = $db->selectAll($sql);
		$this->assertTrue(count($data) > 0);

		// truncate
		$sql = "TRUNCATE `member`";
		$result = $db->execute($sql);
		$this->assertTrue($result);

		// select empty table
		$sql = 'SELECT `name` FROM `member` LIMIT 10';
		$data = $db->selectAll($sql);
		$this->assertEqual(count($data), 0);

		$row = $db->selectRow($sql);
		$this->assertEqual(count($row), 0);

		$name = $db->selectOne($sql);
		$this->assertNull($name);

		// insert a data
		$sql = "INSERT INTO `member` (`name`, `regtime`) VALUES (?, NOW())";
		$result = $db->execute($sql, array('roddy'));
		$this->assertTrue($result);

		// select
		$sql = 'SELECT `name`,`regtime` FROM `member` LIMIT 10';

		$data = $db->selectAll($sql);
		$this->assertEqual(count($data), 1);
		$this->assertEqual($data[0]['name'], 'roddy');

		$row = $db->selectRow($sql);
		$this->assertEqual($row['name'], 'roddy');

		$name = $db->selectOne($sql);
		$this->assertEqual($name, 'roddy');

		// update
		$sql = "UPDATE `member` SET `name`=? WHERE `name`=?";
		$result = $db->execute($sql, array('roddy++', 'roddy'));
		$this->assertTrue($result);

		$sql = 'SELECT COUNT(*) FROM `member` WHERE `name`=?';
		$count = $db->selectOne($sql, array('roddy++'));
		$this->assertEqual($count, 1);

		// delete
		$sql = "DELETE FROM `member` WHERE `name`=?";
		$result = $db->execute($sql, array('roddy++'));
		$this->assertTrue($result);

		$sql = 'SELECT COUNT(*) FROM `member` WHERE `name`=?';
		$count = $db->selectOne($sql, array('roddy++'));
		$this->assertEqual($count, 0);

	}

}