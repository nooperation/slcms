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

	/**
	 * @param BaseServerDatabase $db
	 * @return array
	 */
	protected function CreateRegionsAndShards($db, $count)
	{
		$shards = array();
		$regions = array();

		for($i = 0; $i < $count; ++$i)
		{
			$shardName = "TestShard-" . $i;
			$shardId = $db->CreateShard($shardName);
			$this->assertNotEmpty($shardId);

			$regionName = "TestRegion-" . $i;
			$regionId = $db->CreateRegion($regionName, $shardId);
			$this->assertNotEmpty($regionId);


			$shards []= array('name' => $shardName, 'id' => $shardId);
			$regions []= array('name' => $regionName, 'id' => $regionId, 'shardId' => $shardId);
		}

		return array('shards' => $shards, 'regions' => $regions);
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
}
