<?php

include_once(dirname(__FILE__) . "/../lib/BaseServerDatabase.php");
include_once(dirname(__FILE__) . "/../lib/PopulationDatabase.php");
include_once(dirname(__FILE__) . "/../lib/SecondlifeHeader.php");
include_once(dirname(__FILE__) . "/../lib/Utils.php");

class PopulationServer_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @var BaseServerDatabase
	 */
	protected $db;
	protected $testServers;
	protected $serverType = 'Population Server';


	/**
	 * @param BaseServerDatabase $db
	 * @return array
	 */
	protected function CreateServers($db, $count)
	{
		$servers = array();

		for($i = 0; $i < $count; ++$i)
		{
			$server = [
				'shardName' => "TestShard-" . $i,
				'ownerKey' => "TestOwnerKey-" . $i,
				'objectKey' => "TestObjectKey-" .$i,
				'ownerName' => "TestUser-" . $i,
				'serverName' => "TestObject-" . $i,
				'regionName' => "TestRegion-" . $i,
				'address' => "TestAddress-" . $i,
				'positionX' => $i,
				'positionY' => 0,
				'positionZ' => 1 + $i * $i,
				'authToken' => null,
				'publicToken' => null,
				'enabled' => true
			];

			$tokens = $db->RegisterServer($server['shardName'], $server['ownerKey'], $server['ownerName'], $server['objectKey'], $server['serverName'], $server['regionName'], $server['address'], $server['positionX'], $server['positionY'], $server['positionZ'], $server['enabled']);
			$server['authToken'] = $tokens['authToken'];
			$server['publicToken'] = $tokens['publicToken'];

			$servers []= $server;
		}

		return $servers;
	}

	protected function setUp()
	{
		$this->db = new PopulationServerDatabase();

		try
		{
			$this->db->ConnectToDatabase();
		}
		catch(Exception $ex)
		{
			$this->db = null;
			throw $ex;
		}

		$this->assertNotEmpty($this->db);

		$this->db->DropTestServers();
		$this->testServers = $this->CreateServers($this->db, 1);

		foreach($this->testServers as $server)
		{
			$this->assertNotEmpty($server);
			$this->assertNotEmpty($server['publicToken']);
			$this->assertNotEmpty($server['authToken']);
		}
	}

	protected function tearDown()
	{
		if(	$this->db)
		{
			$this->db->DropTestServers();
		}
	}

	public function testGetPopulationServersForFrontend()
	{

	}

	public function testGetPopulation()
	{

	}

	public function testCreatePopulation()
	{

	}
}
