<?php

include_once(dirname(__FILE__) . "/../.private/config.php");

class SimstatsDatabase
{
	/**
	 * @var PDO
	 *   Database connection. Null when not connected.
	 */
	private $db = null;

	function __construct()
	{
		$this->db = null;
	}

	public function ConnectToDatabase()
	{
		$newDb = new PDO("mysql:host=" . Config::$DatabaseHost . ";dbname=" . Config::$DatabaseName . ";charset=utf8", Config::$DatabaseUser, Config::$DatabasePassword );
		$newDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$this->db = $newDb;
	}

	function SetServerAddress($serverId, $ownerId, $address)
	{
		$statement = $this->db->prepare("UPDATE servers SET
											ownerId = :ownerId,
											address = :address
										WHERE id = :serverId
										LIMIT 1");
		$statement->execute(array(
			'serverId' => $serverId,
			'ownerId' => $ownerId,
			'address' => $address
		));
	}

	function GetServers()
	{
		$statement = $this->db->prepare("SELECT * FROM servers");
		$statement->execute();

		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	function GetServerId($name)
	{
		$statement = $this->db->prepare("SELECT id
										FROM servers
										WHERE name = :name");

		$statement->execute(array(
			'name' => $name
		));

		$result = $statement->fetch(PDO::FETCH_ASSOC);

		if(!isset($result['id']))
			return null;

		return $result['id'];
	}

	function GetShards()
	{
		$statement = $this->db->prepare("SELECT * FROM shards");
		$statement->execute();

		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	function GetShardId($name)
	{
		$statement = $this->db->prepare("SELECT *
										FROM shards
										WHERE name = :name");

		$statement->execute(array(
			'name' => $name
		));

		$result = $statement->fetch(PDO::FETCH_ASSOC);

		if(!isset($result['id']))
			return null;

		return $result['id'];
	}

	function GetStats($serverId)
	{
		$statement = $this->db->prepare("SELECT agentCount, time
										FROM   stats
										WHERE  serverId = :serverId
										ORDER BY time");

		$statement->execute(array(
			'serverId' => $serverId
		));

		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	public function GetUserId($userKey)
	{
		$statement = $this->db->prepare("SELECT id
										FROM users
										WHERE uuid = :uuid");

		$statement->execute(array(
			'uuid' => $userKey
		));

		$result = $statement->fetch(PDO::FETCH_ASSOC);

		if(!isset($result['id']))
			return null;

		return $result['id'];
	}

	public function CreateUser($userKey, $name)
	{
		$statement = $this->db->prepare("INSERT INTO users (
											uuid, name
										) VALUES (
											:uuid, :name
										)");

		$statement->execute(array(
			'uuid' => $userKey,
			'name' => $name
		));

		if(!$statement->rowCount())
		{
			throw new Exception("Failed to add user '" . $name . "' [" . $userKey . "].");
		}

		return $this->db->lastInsertId();
	}

	public function GetOrCreateUserId($userKey, $name)
	{
		$userId = $this->GetUserId($userKey);
		if($userId === null)
		{
			$userId = $this->CreateUser($userKey, $name);
		}

		return $userId;
	}


	public function CreateStats($serverId, $time, $agentCount)
	{
		$statement = $this->db->prepare("INSERT INTO stats (
											serverId, time, agentCount
										) VALUES (
											:serverId, :time, :agentCount
										)");

		$statement->execute(array(
			'serverId' => $serverId,
			'time' => $time,
			'agentCount' => $agentCount,
		));

		if(!$statement->rowCount())
		{
			throw new Exception("Failed to add sim stats for server '" . $serverId . "'.");
		}

		return $this->db->lastInsertId();
	}

	public function GetOrCreateShardId($name)
	{
		$shardId = $this->GetShardId($name);
		if($shardId === null)
		{
			$shardId = $this->CreateShard($name);
		}

		return $shardId;
	}

	public function CreateShard($name)
	{
		$statement = $this->db->prepare("INSERT INTO shards (
											name
										) VALUES (
											:name
										)");

		$statement->execute(array(
			'name' => $name,
		));

		if(!$statement->rowCount())
		{
			throw new Exception("Failed to add shard named '" . $name . "'.");
		}

		return $this->db->lastInsertId();
	}

	public function CreateOrUpdateServer($name, $shardId, $ownerId, $address, $enabled = 1)
	{
		$serverId = $this->GetServerId($name);
		if($serverId == null)
		{
			$this->CreateServer($name, $shardId, $ownerId, $address, $enabled);
		}
		else
		{
			$this->SetServerAddress($serverId, $ownerId, $address);
		}
	}

	public function CreateServer($name, $shardId, $ownerId, $address, $enabled = 1)
	{
		$statement = $this->db->prepare("INSERT INTO servers (
											name, shardId, address, ownerId, enabled
										) VALUES (
											:name, :shardId, :address, :ownerId, :enabled
										)");

		$statement->execute(array(
			'name' => $name,
			'shardId' => $shardId,
			'address' => $address,
			'ownerId' => $ownerId,
			'enabled' => $enabled
		));

		if(!$statement->rowCount())
		{
			throw new Exception("Failed to add server named '" . $name . "'.");
		}

		return $this->db->lastInsertId();
	}
}