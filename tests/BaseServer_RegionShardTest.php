<?php

include_once(dirname(__FILE__) . "/../lib/BaseServerDatabase.php");
include_once(dirname(__FILE__) . "/../lib/SecondlifeHeader.php");
include_once(dirname(__FILE__) . "/../lib/Utils.php");

class BaseServer_RegionShardTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var BaseServerDatabase
	 */
	protected $db;
	protected $testShards;
	protected $testRegions;
	protected $testAgents;

	/**
	 * @param BaseServerDatabase $db
	 * @return array
	 */
	protected function CreateRegionsAndShards($db, $count)
	{
		$shards = array();
		$regions = array();
		$agents = array();

		for($i = 0; $i < $count; ++$i)
		{
			$shardName = "TestShard-" . $i;
			$shardId = $db->CreateShard($shardName);
			$this->assertNotEmpty($shardId);

			$regionName = "TestRegion-" . $i;
			$regionId = $db->CreateRegion($regionName, $shardId);
			$this->assertNotEmpty($regionId);

			for($agentIndex = 0; $agentIndex < 3; ++ $agentIndex)
			{
				$agentName = "TestAgent-" . $agentIndex;
				$agentUuid = "TestUUID-" . $agentIndex;
				$agentId = $db->CreateAgent($agentName, $agentUuid, $shardId);
				$this->assertNotEmpty($agentId);

				$agents [] = array(
					'name' => $agentName,
					'id' => $agentId,
					'uuid' => $agentUuid,
					'shardId' => $shardId,
					'authToken' => null,
					'authTokenDate' => null
				);
			}

			$shards []= array('name' => $shardName, 'id' => $shardId);
			$regions []= array('name' => $regionName, 'id' => $regionId, 'shardId' => $shardId);
		}


		return array('shards' => $shards, 'regions' => $regions, 'agents' => $agents);
	}

	protected function setUp()
	{
		$this->db = new BaseServerDatabase();

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

		$regionsAndShards = $this->CreateRegionsAndShards($this->db, 5);
		$this->testShards = $regionsAndShards['shards'];
		$this->testRegions = $regionsAndShards['regions'];
		$this->testAgents = $regionsAndShards['agents'];
	}

	protected function tearDown()
	{
		if(	$this->db)
		{
			$this->db->DropTestServers();
		}
	}

	////////////////////
	// TABLE: shard
	////////////////////

	function testGetShards()
	{
		$unverifiedShards = $this->db->GetShards();

		foreach($this->testShards as $shard)
		{
			$this->assertContains($shard, $unverifiedShards);
		}
	}

	function testGetShardName()
	{
		foreach($this->testShards as $shard)
		{
			$this->assertEquals($shard['name'], $this->db->GetShardName($shard['id']));
		}
	}

	function testGetShardId()
	{
		foreach($this->testShards as $shard)
		{
			$this->assertEquals($shard['id'], $this->db->GetShardId($shard['name']));
		}
	}

	public function testGetOrCreateShardId()
	{
		foreach($this->testShards as $shard)
		{
			$shardId = $this->db->GetOrCreateShardId($shard['name']);
			$this->assertEquals($shardId, $shard['id']);
		}

	}

	////////////////////
	// TABLE: region
	////////////////////
	function testGetRegions()
	{
		$unverifiedRegions = $this->db->GetRegions();

		foreach($this->testRegions as $region)
		{
			$this->assertContains($region, $unverifiedRegions);
		}
	}

	function testGetRegionId()
	{
		foreach($this->testRegions as $region)
		{
			$this->assertEquals($region['id'], $this->db->GetRegionId($region['name'], $region['shardId']));
			$this->assertEquals(null, $this->db->GetRegionId($region['name'] . "Missing", $region['shardId']));
			$this->assertEquals(null, $this->db->GetRegionId($region['name'], 99999999));
		}
	}

	public function testGetOrCreateRegionId()
	{
		foreach($this->testRegions as $region)
		{
			$regionId = $this->db->GetOrCreateRegionId($region['name'], $region['shardId']);
			$this->assertEquals($regionId, $region['id']);
		}
	}

	////////////////////
	// TABLE: agent
	////////////////////
	function testGetAgentId()
	{
		foreach($this->testAgents as $agent)
		{
			$this->assertEquals($agent['id'], $this->db->GetAgentId($agent['uuid'], $agent['shardId']));
			$this->assertEquals(null, $this->db->GetAgentId($agent['uuid'] . "Missing", $agent['shardId']));
			//$this->assertEquals(null, $this->db->GetAgentId($agent['uuid'], 99999999));
			// TODO: Test invalid shardId
		}
	}

	public function testGetOrCreateAgentId()
	{
		foreach($this->testShards as $shard)
		{
			$testAgent = $this->testAgents[0];

			// Only UUID and shardId are used when looking up agents...
			$this->assertEquals($testAgent['id'], $this->db->GetOrCreateAgentId($testAgent['name'], $testAgent['uuid'], $testAgent['shardId']));
			$this->assertNotEquals($testAgent['id'], $this->db->GetOrCreateAgentId($testAgent['name'], $testAgent['uuid'] . "Missing", $testAgent['shardId']));
			//$this->assertNotEquals($testAgent['id'], $this->db->GetOrCreateAgentId($testAgent['name'], $testAgent['uuid'], 99999999));
			// TODO: Test invalid shardId

			$agentId = $this->db->GetOrCreateAgentId('TestAgent-90000', 'TestUUID-90000', $shard['id']);
			$this->assertNotEmpty($agentId);
		}

	}
}
