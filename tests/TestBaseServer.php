<?php

include_once(dirname(__FILE__) . "/../lib/BaseServerDatabase.php");
include_once(dirname(__FILE__) . "/../lib/SecondlifeHeader.php");
include_once(dirname(__FILE__) . "/../lib/Utils.php");

class TestBaseServer extends PHPUnit_Framework_TestCase
{
	/**
	 * @var BaseServerDatabase
	 */
	protected $db;
	protected $testServer;

	/**
	 * @param BaseServerDatabase $db
	 * @return array
	 */
	protected function CreateServer($db)
	{
		$server = [
			'shardName' => "Test Shard",
			'ownerKey' => "01234567-89ab-cdef-0123-456789abcdef",
			'objectKey' => "01234567-89ab-cdef-0123-456789abcdeg",
			'ownerName' => "Test User",
			'serverName' => "Test Object",
			'regionName' => "Test Region",
			'address' => "http://google.com",
			'positionX' => 1,
			'positionY' => 2,
			'positionZ' => 3,
			'authToken' => null,
			'publicToken' => null,
			'enabled' => true
		];

		$tokens = $db->RegisterServer($server['shardName'], $server['ownerKey'], $server['ownerName'], $server['objectKey'], $server['serverName'], $server['regionName'], $server['address'], $server['positionX'], $server['positionY'], $server['positionZ'], $server['enabled']);
		$server['authToken'] = $tokens['authToken'];
		$server['publicToken'] = $tokens['publicToken'];

		return $server;
	}

	protected function setUp()
	{
		$this->db = new BaseServerDatabase();

		$this->db->ConnectToDatabase();
		$this->assertNotEmpty($this->db);

		$this->testServer = $this->CreateServer($this->db);
		$this->assertNotEmpty($this->testServer);
		$this->assertNotEmpty($this->testServer['publicToken']);
		$this->assertNotEmpty($this->testServer['authToken']);
	}

	protected function tearDown()
	{
		$this->db->RemoveServer($this->testServer['authToken']);
	}

	public function testGetServer()
	{
		$server = $this->db->GetServer($this->testServer['authToken']);

		$this->assertNotEmpty($server);
		$this->assertEquals($this->testServer['shardName'], $server['shardName']);
		$this->assertEquals($this->testServer['ownerKey'], $server['ownerKey']);
		$this->assertEquals($this->testServer['objectKey'], $server['objectKey']);
		$this->assertEquals($this->testServer['ownerName'], $server['ownerName']);
		$this->assertEquals($this->testServer['serverName'], $server['serverName']);
		$this->assertEquals($this->testServer['regionName'], $server['regionName']);
		$this->assertEquals($this->testServer['address'], $server['address']);
		$this->assertEquals($this->testServer['positionX'], $server['positionX']);
		$this->assertEquals($this->testServer['positionY'], $server['positionY']);
		$this->assertEquals($this->testServer['positionZ'], $server['positionZ']);
		$this->assertEquals($this->testServer['authToken'], $server['authToken']);
		$this->assertEquals($this->testServer['publicToken'], $server['publicToken']);
	}

	public function testUpdateServer()
	{
		$newAddress = $this->testServer['address'] . " [updated address]";
		$newRegionName = $this->testServer['regionName'] . " [updated region name]";
		$newObjectName = $this->testServer['serverName'] . " [updated server name]";
		$newX = $this->testServer['positionX'] + 1;
		$newY = $this->testServer['positionY'] + 1;
		$newZ = $this->testServer['positionZ'] + 1;
		$newEnabled = !$this->testServer['enabled'];

		$this->db->UpdateServer($this->testServer['authToken'], $newAddress, $newObjectName, $this->testServer['shardName'], $newRegionName, $newX, $newY, $newZ, $newEnabled);

		$server = $this->db->GetServer($this->testServer['authToken']);

		$this->assertNotEmpty($server);
		$this->assertEquals($newAddress, $server['address']);
		$this->assertEquals($newRegionName, $server['regionName']);
		$this->assertEquals($newObjectName, $server['serverName']);
	}
}
