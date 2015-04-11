<?php

include_once(dirname(__FILE__) . "/../lib/BaseServerDatabase.php");
include_once(dirname(__FILE__) . "/../lib/PopulationDatabase.php");
include_once(dirname(__FILE__) . "/../lib/SecondlifeHeader.php");
include_once(dirname(__FILE__) . "/../lib/Utils.php");

class PopulationServer_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @var PopulationServerDatabase
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

			$db->InitServer($tokens['authToken']);

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
		$this->testServers = $this->CreateServers($this->db, 5);

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

	/*
	 * @depends testCreatePopulation
	 */
	public function testGetPopulationServersForFrontend()
	{
		$this->testCreatePopulation();

		$populationData = $this->db->GetPopulationServersForFrontend();

		$this->assertSameSize($this->testServers, $populationData);

		for($i = 0; $i < sizeof($this->testServers); ++$i)
		{
			$testPopulationData = array('publicToken' => bin2hex($this->testServers[$i]['publicToken']),
				'serverName' => $this->testServers[$i]['serverName'],
				'shardName' => $this->testServers[$i]['shardName'],
				'userName' => $this->testServers[$i]['ownerName'],
				'regionName' => $this->testServers[$i]['regionName'],
				'enabled' => $this->testServers[$i]['enabled'],
				'currentPopulation' => $i & 1 ? $i : null);

			$this->assertContains($testPopulationData, $populationData);
		}
	}

	/*
	 * @depends testCreatePopulation
	 */
	public function testGetPopulation()
	{
		$this->testCreatePopulation();

		for($i = 0; $i < sizeof($this->testServers); ++$i)
		{
			$populations = $this->db->GetPopulation($this->testServers[$i]['publicToken'], null, null);

			// TODO: Test time range...

			if($i & 1)
			{
				$this->assertEquals($i, sizeof($populations));
				for($populationIter = 0; $populationIter < sizeof($populations); ++$populationIter)
				{
					$this->assertEquals($i, $populations[$populationIter]['agentCount']);
					$this->assertEquals(1000+$i, $populations[$populationIter]['time']);
				}
			}
			else
			{
				$this->assertEquals(0, sizeof($populations));
			}
		}
	}

	public function testCreatePopulation()
	{
		for($i = 0; $i < sizeof($this->testServers); ++$i)
		{
			if($i & 1)
			{
				for($populationIter = 0; $populationIter < $i; ++$populationIter)
				{
					$this->db->CreatePopulation($this->testServers[$i]['publicToken'], 1000 + $i, $i);
				}
			}
		}

	}
}
