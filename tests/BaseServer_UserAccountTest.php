<?php

include_once(dirname(__FILE__) . "/../lib/BaseServerDatabase.php");
include_once(dirname(__FILE__) . "/../lib/PopulationDatabase.php");
include_once(dirname(__FILE__) . "/../lib/SecondlifeHeader.php");
include_once(dirname(__FILE__) . "/../lib/Utils.php");

class BaseServer_UserAccountTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var BaseServerDatabase
	 */
	protected $db;
	protected $testUsers;

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
		$this->testUsers = $this->CreateUsers($this->db, 5);


	}

	protected function tearDown()
	{
		if(	$this->db)
		{
			$this->db->DropTestServers();
		}
	}

	/**
	 * @param BaseServerDatabase $db
	 * @return array
	 */
	protected function CreateUsers($db, $count)
	{
		$users = array();

		for($i = 0; $i < $count; ++$i)
		{
			$user = [
				'name' => "TestUser-" . $i,
				'email' => "TestEmail-" . $i . "@example.com",
				'password' => "TestUserPassword-" . $i,
				'id' => null,
			];

			$result = $db->RegisterUser($user['name'], $user['email'], $user['password']);
			$this->assertNotNull($result);

			$user['id'] = $result;
			$users []= $user;
		}

		return $users;
	}

	public function testGetUser()
	{
		$this->assertNull($this->db->GetUser("Hello", "What"));

		foreach($this->testUsers as $user)
		{
			$returnedUser = $this->db->GetUser($user['name'], $user['password']);

			$this->assertNotNull($returnedUser);
			$this->assertNotNull($returnedUser['id']);
			$this->assertEquals($user['name'], $returnedUser['name']);
		}

		foreach($this->testUsers as $user)
		{
			$returnedUser = $this->db->GetUser($user['name'] . 'x', $user['password']);
			$this->assertNull($returnedUser);

			$returnedUser = $this->db->GetUser($user['name'], $user['password'] . 'x');
			$this->assertNull($returnedUser);

			$returnedUser = $this->db->GetUser($user['name'] . 'x', $user['password'] . 'x');
			$this->assertNull($returnedUser);
		}
	}

	public function testIsUsernameAvailable()
	{
		$this->assertFalse($this->db->IsUsernameAvailable(null));

		foreach($this->testUsers as $user)
		{
			$this->assertFalse($this->db->IsUsernameAvailable($user['name']));
			$this->assertTrue($this->db->IsUsernameAvailable($user['name'] . 'x'));
		}
	}
}
