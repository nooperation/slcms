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

		$this->testServer = $this->CreateServer($this->db);
		$this->assertNotEmpty($this->testServer);
		$this->assertNotEmpty($this->testServer['publicToken']);
		$this->assertNotEmpty($this->testServer['authToken']);
	}

	protected function tearDown()
	{
		if($this->db)
		{
			$this->db->RemoveServer($this->testServer['authToken']);
		}
	}

	public function testGetServer()
	{
		// Check to see if test server exists in database
		$server = $this->db->GetServer($this->testServer['authToken']);
		$this->assertNotEmpty($server);

		// Verify all data matches...
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

	public function testRegisterServer()
	{
		// NOTE: Server already created and registered by test...

		// Recreate server...
		$newTestServer = $this->CreateServer($this->db);
		$this->assertNotEmpty($newTestServer);

		// Make sure auth tokens were re-created
		$this->assertNotEquals($newTestServer['authToken'], $this->testServer['authToken']);
		$this->assertNotEquals($newTestServer['publicToken'], $this->testServer['publicToken']);

		// Make sure we can no longer fetch the test server via old auth token
		$server = $this->db->GetServer($this->testServer['authToken']);
		$this->assertFalse($server);

		// Make sure we can fetch test server with new auth token
		$server = $this->db->GetServer($newTestServer['authToken']);
		$this->assertNotEmpty($server);

		// Confirm the only things that have changed in the server itself are the tokens
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
		$this->assertNotEquals($this->testServer['authToken'], $server['authToken']);
		$this->assertNotEquals($this->testServer['publicToken'], $server['publicToken']);

		// Fix the test server so TearDown can delete it...
		$this->testServer = $newTestServer;
	}

	public function testUpdateServer()
	{
		// Update test server data
		$newAddress = $this->testServer['address'] . " [updated address]";
		$newRegionName = $this->testServer['regionName'] . " [updated region name]";
		$newObjectName = $this->testServer['serverName'] . " [updated server name]";
		$newX = $this->testServer['positionX'] + 1;
		$newY = $this->testServer['positionY'] + 1;
		$newZ = $this->testServer['positionZ'] + 1;
		$newEnabled = !$this->testServer['enabled'];
		$this->db->UpdateServer($this->testServer['authToken'], $newAddress, $newObjectName, $this->testServer['shardName'], $newRegionName, $newX, $newY, $newZ, $newEnabled);

		// Verify data exists
		$server = $this->db->GetServer($this->testServer['authToken']);
		$this->assertNotFalse($server);
		$this->assertNotEmpty($server);
		$this->assertEquals($newAddress, $server['address']);
		$this->assertEquals($newRegionName, $server['regionName']);
		$this->assertEquals($newObjectName, $server['serverName']);
	}

	public function testSetServerStatus()
	{
		// Test enabling server
		$this->db->SetServerStatus($this->testServer['authToken'], 1);
		$server = $this->db->GetServer($this->testServer['authToken']);
		$this->assertEquals($server['enabled'], 1);


		// Test disabling server
		$this->db->SetServerStatus($this->testServer['authToken'], 0);
		$server = $this->db->GetServer($this->testServer['authToken']);
		$this->assertEquals($server['enabled'], 0);
	}

	function testGetUninitializedServerAuthToken()
	{
		$this->assertEquals(null, $this->db->GetUninitializedServerAuthToken(null));
		$this->assertEquals(null, $this->db->GetUninitializedServerAuthToken("1234"));
		$this->assertEquals($this->testServer['authToken'], $this->db->GetUninitializedServerAuthToken($this->testServer['objectKey']));
	}

	function testRegenerateServerTokens()
	{
		$newTokens = $this->db->RegenerateServerTokens($this->testServer['authToken']);

		$this->assertNotEquals($this->testServer['publicToken'], $newTokens['publicToken']);
		$this->assertNotEquals($this->testServer['authToken'], $newTokens['authToken']);

		$this->testServer['publicToken'] = $newTokens['publicToken'];
		$this->testServer['authToken'] = $newTokens['authToken'];
	}

	function testRegenerateServerAuthToken()
	{
		$newAuthToken = $this->db->RegenerateServerAuthToken($this->testServer['authToken']);

		$this->assertNotEquals($this->testServer['authToken'], $newAuthToken);

		$this->testServer['authToken'] = $newAuthToken;
	}

	function testRegenerateServerPublicToken()
	{
		$newPublicToken = $this->db->RegenerateServerPublicToken($this->testServer['authToken']);

		$this->assertNotEquals($this->testServer['publicToken'], $newPublicToken);

		$this->testServer['publicToken'] = $newPublicToken;
	}

	function testGetServerAddress()
	{
		$this->assertEmpty($this->db->GetServerAddress("123"));
		$this->assertEquals($this->testServer['address'], $this->db->GetServerAddress($this->testServer['publicToken']));
	}

	function testGetServers()
	{

	}

	function testGetServerNameAndId()
	{

	}
}
