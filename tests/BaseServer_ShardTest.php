<?php

include_once(dirname(__FILE__) . "/../lib/BaseServerDatabase.php");
include_once(dirname(__FILE__) . "/../lib/SecondlifeHeader.php");
include_once(dirname(__FILE__) . "/../lib/Utils.php");

class BaseServer_ShardTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var BaseServerDatabase
	 */
	protected $db;
	protected $testShards;

	/**
	 * @param BaseServerDatabase $db
	 * @return array
	 */
	protected function CreateShards($db, $count)
	{
		$shards = array();

		for($i = 0; $i < $count; ++$i)
		{
			$shardName = "TestShard-" . $i;
			$shardId = $this->db->CreateShard($shardName);

			$this->assertNotEmpty($shardId);

			$shards []= array('name' => $shardName, 'id' => $shardId);
		}

		return $shards;
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
		$this->testShards = $this->CreateShards($this->db, 5);
	}

	protected function tearDown()
	{
		if(	$this->db)
		{
			$this->db->DropTestServers();
		}
	}

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

}
