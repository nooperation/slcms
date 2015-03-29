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
	protected $testServers;


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
				'shardName' => "Test Shard-" . $i,
				'ownerKey' => "TestOwnerKey-" . $i,
				'objectKey' => "TestObjectKey-" .$i,
				'ownerName' => "Test User-" . $i,
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

		$droppedServerCount = $this->db->DropTestServers();
		echo 'Dropped: ' . $droppedServerCount . ' test server(s)\n';

		$this->testServers = $this->CreateServers($this->db, 10);

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
			foreach($this->testServers as $server)
			{
				$this->db->RemoveServer($server['authToken']);
			}
		}
	}

	public function testGetServer()
	{
		foreach($this->testServers as $server)
		{
			// Check to see if test server exists in database
			$serverFromDatabase = $this->db->GetServer($server['authToken']);
			$this->assertNotEmpty($server);

			// Verify all data matches...
			$this->assertEquals($server['shardName'], $serverFromDatabase['shardName']);
			$this->assertEquals($server['ownerKey'], $serverFromDatabase['ownerKey']);
			$this->assertEquals($server['objectKey'], $serverFromDatabase['objectKey']);
			$this->assertEquals($server['ownerName'], $serverFromDatabase['ownerName']);
			$this->assertEquals($server['serverName'], $serverFromDatabase['serverName']);
			$this->assertEquals($server['regionName'], $serverFromDatabase['regionName']);
			$this->assertEquals($server['address'], $serverFromDatabase['address']);
			$this->assertEquals($server['positionX'], $serverFromDatabase['positionX']);
			$this->assertEquals($server['positionY'], $serverFromDatabase['positionY']);
			$this->assertEquals($server['positionZ'], $serverFromDatabase['positionZ']);
			$this->assertEquals($server['authToken'], $serverFromDatabase['authToken']);
			$this->assertEquals($server['publicToken'], $serverFromDatabase['publicToken']);
		}
	}

	public function testRegisterServer()
	{
		// NOTE: Server already created and registered by test...

		// Recreate server...
		$newTestServer = $this->CreateServers($this->db, 1)[0];
		$this->assertNotEmpty($newTestServer);

		// Make sure auth tokens were re-created
		$this->assertNotEquals($newTestServer['authToken'], $this->testServers[0]['authToken']);
		$this->assertNotEquals($newTestServer['publicToken'], $this->testServers[0]['publicToken']);

		// Make sure we can no longer fetch the test server via old auth token
		$server = $this->db->GetServer($this->testServers[0]['authToken']);
		$this->assertFalse($server);

		// Make sure we can fetch test server with new auth token
		$server = $this->db->GetServer($newTestServer['authToken']);
		$this->assertNotEmpty($server);

		// Update auth tokens of our test server because we just changed them...
		$this->testServers[0]['authToken'] = $newTestServer['authToken'];
		$this->testServers[0]['publicToken'] = $newTestServer['publicToken'];

		// Confirm all servers have expected values in database...
		$this->testGetServer();
	}

	public function testUpdateServer()
	{
		// Update test server data
		$newAddress = $this->testServers[0]['address'] . " [updated address]";
		$newRegionName = $this->testServers[0]['regionName'] . " [updated region name]";
		$newObjectName = $this->testServers[0]['serverName'] . " [updated server name]";
		$newX = $this->testServers[0]['positionX'] + 1;
		$newY = $this->testServers[0]['positionY'] + 1;
		$newZ = $this->testServers[0]['positionZ'] + 1;
		$newEnabled = !$this->testServers[0]['enabled'];
		$this->db->UpdateServer($this->testServers[0]['authToken'], $newAddress, $newObjectName, $this->testServers[0]['shardName'], $newRegionName, $newX, $newY, $newZ, $newEnabled);

		// Verify data exists
		$server = $this->db->GetServer($this->testServers[0]['authToken']);
		$this->assertNotFalse($server);
		$this->assertNotEmpty($server);
		$this->assertEquals($newAddress, $server['address']);
		$this->assertEquals($newRegionName, $server['regionName']);
		$this->assertEquals($newObjectName, $server['serverName']);
		$this->assertEquals($newX, $server['positionX']);
		$this->assertEquals($newY, $server['positionY']);
		$this->assertEquals($newZ, $server['positionZ']);
		$this->assertEquals($newEnabled, $server['enabled']);

		// Update updated testServer so it contains the expected values...
		$this->testServers[0]['address'] = $server['address'];
		$this->testServers[0]['regionName'] = $server['regionName'];
		$this->testServers[0]['serverName'] = $server['serverName'];
		$this->testServers[0]['x'] = $server['x'];
		$this->testServers[0]['y'] = $server['y'];
		$this->testServers[0]['z'] = $server['z'];
		$this->testServers[0]['enabled'] = $server['enabled'];

		// Confirm all servers have expected values in database...
		$this->testGetServer();
	}

	public function testSetServerStatus()
	{
		// Test enabling server
		$this->db->SetServerStatus($this->testServers[0]['authToken'], 1);
		$server = $this->db->GetServer($this->testServers[0]['authToken']);
		$this->assertEquals($server['enabled'], 1);

		// Confirm all servers have expected values in database...
		$this->testServers[0]['enabled'] = 1;
		$this->testGetServer();

		// Test disabling server
		$this->db->SetServerStatus($this->testServers[0]['authToken'], 0);
		$server = $this->db->GetServer($this->testServers[0]['authToken']);
		$this->assertEquals($server['enabled'], 0);

		// Confirm all servers have expected values in database...
		$this->testServers[0]['enabled'] = 0;
		$this->testGetServer();
	}

	function testGetUninitializedServerAuthToken()
	{
		$this->assertEquals(null, $this->db->GetUninitializedServerAuthToken(null));
		$this->assertEquals(null, $this->db->GetUninitializedServerAuthToken("1234"));

		foreach($this->testServers as $server)
		{
			$this->assertEquals($server['authToken'], $this->db->GetUninitializedServerAuthToken($server['objectKey']));
		}

		// TODO: Add check vs server that is already initialized...
	}

	function testRegenerateServerTokens()
	{
		$newTokens = $this->db->RegenerateServerTokens($this->testServers[0]['authToken']);

		$this->assertNotEquals($this->testServers[0]['publicToken'], $newTokens['publicToken']);
		$this->assertNotEquals($this->testServers[0]['authToken'], $newTokens['authToken']);

		$this->testServers[0]['publicToken'] = $newTokens['publicToken'];
		$this->testServers[0]['authToken'] = $newTokens['authToken'];

		// Confirm all servers have expected values in database...
		$this->testGetServer();
	}

	function testRegenerateServerAuthToken()
	{
		$newAuthToken = $this->db->RegenerateServerAuthToken($this->testServers[0]['authToken']);

		$this->assertNotEquals($this->testServers[0]['authToken'], $newAuthToken);

		$this->testServers[0]['authToken'] = $newAuthToken;

		// Confirm all servers have expected values in database...
		$this->testGetServer();
	}

	function testRegenerateServerPublicToken()
	{
		$newPublicToken = $this->db->RegenerateServerPublicToken($this->testServers[0]['authToken']);

		$this->assertNotEquals($this->testServers[0]['publicToken'], $newPublicToken);

		$this->testServers[0]['publicToken'] = $newPublicToken;

		// Confirm all servers have expected values in database...
		$this->testGetServer();
	}

	function testGetServerAddress()
	{
		$this->assertEmpty($this->db->GetServerAddress("123"));
		$this->assertEquals($this->testServers[0]['address'], $this->db->GetServerAddress($this->testServers[0]['publicToken']));
	}


	////////////////////
	// TABLE: shard
	////////////////////

	function testGetShards()
	{

	}

	public function testCreateShard()
	{
		echo "Test create shard\n";
		$shardName = "Test shard " . time();

		$shardId = $this->db->CreateShard($shardName);
		$this->assertNotNull($shardId);

		return array('name' => $shardName, 'id' => $shardId);
	}

	/**
	 * @depends testCreateShard
	 */
	function testGetShardName($shard)
	{

		//$this->assertEquals($shard['name'], $this->db->GetShardName($shard['name']));
		//$this->assertNull($this->db->GetShardName("!This shard shouldn't exist!"));
	}

	/**
	 * @depends testCreateShard
	 */
	function testGetShardId($shard)
	{

	}

	/**
	 * @depends testCreateShard
	 */
	public function testGetOrCreateShardId($shard)
	{

	}

}
