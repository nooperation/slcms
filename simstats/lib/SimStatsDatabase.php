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
		$newDb = new PDO("mysql:host=" . Config::$SimStatsDatabaseHost . ";dbname=" . Config::$SimStatsDatabaseName . ";charset=utf8", Config::$SimStatsDatabaseUser, Config::$SimStatsDatabasePassword );
		$newDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$this->db = $newDb;
	}

	function SetServerStatus($id, $isEnabled)
	{
		$statement = $this->db->prepare("UPDATE stats_server SET
											enabled = :isEnabled
										WHERE id = :id
										LIMIT 1");

		$statement->bindParam('id', $id, PDO::PARAM_INT);
		$statement->bindParam('isEnabled', $isEnabled, PDO::PARAM_INT);
		$statement->execute();
	}

	function UpdateServer($uuid, $name, $ownerId, $address, $enabled)
	{
		$statement = $this->db->prepare("UPDATE stats_server SET
											name = :name,
											ownerId = :ownerId,
											address = :address,
											enabled = :enabled
										WHERE uuid = :uuid
										LIMIT 1");
		$statement->execute(array(
			'uuid' => $uuid,
			'name' => $name,
			'ownerId' => $ownerId,
			'address' => $address,
			'enabled' => $enabled
		));
	}

	function GetServerAddress($uuid)
	{
		$statement = $this->db->prepare("SELECT address from stats_server WHERE uuid = :uuid LIMIT 1");

		$statement->execute(array(
			'uuid' => $uuid
		));

		$result = $statement->fetch(PDO::FETCH_ASSOC);

		if(!isset($result['address']))
			return null;

		return $result['address'];
	}

	function GetServersForFrontend()
	{
		$statement = $this->db->prepare("SELECT
											servers.uuid,
											servers.name AS 'serverName',
											shards.name AS 'shardName',
											users.name AS 'userName',
											servers.enabled,
											(SELECT
													agentCount
												FROM
													stats
												WHERE
													servers.id = stats.serverid
												ORDER BY time DESC
												LIMIT 1) AS currentPopulation
										FROM
											servers
											LEFT JOIN shards ON shards.id = servers.shardId
											LEFT JOIN users ON users.id = servers.ownerId
										WHERE
											enabled = TRUE;");
		$statement->execute();

		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	function GetServers()
	{
		$statement = $this->db->prepare("SELECT * from stats_server");
		$statement->execute();

		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	function GetServerNameAndId($uuid)
	{
		$statement = $this->db->prepare("SELECT id,name
										from stats_server
										WHERE uuid = :uuid");

		$statement->execute(array(
			'uuid' => $uuid
		));

		$result = $statement->fetch(PDO::FETCH_ASSOC);

		if(!isset($result['id']))
			return null;

		return $result;
	}

	function GetServerUuidFromObjectId($objectId)
	{
		$statement = $this->db->prepare("SELECT uuid
										from stats_server
										WHERE objectId = :objectId");

		$statement->execute(array(
			'objectId' => $objectId
		));

		$result = $statement->fetch(PDO::FETCH_ASSOC);

		if(!isset($result['uuid']))
			return null;

		return $result['uuid'];
	}

	function GetShards()
	{
		$statement = $this->db->prepare("SELECT * from shard");
		$statement->execute();

		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	function GetShardName($shardId)
	{
		$statement = $this->db->prepare("SELECT name
										from shard
										WHERE id = :shardId");

		$statement->execute(array(
			'shardId' => $shardId
		));

		$result = $statement->fetch(PDO::FETCH_ASSOC);

		if(!isset($result['name']))
			return null;

		return $result['name'];
	}

	function GetShardId($name)
	{
		$statement = $this->db->prepare("SELECT *
										from shard
										WHERE name = :name");

		$statement->execute(array(
			'name' => $name
		));

		$result = $statement->fetch(PDO::FETCH_ASSOC);

		if(!isset($result['id']))
			return null;

		return $result['id'];
	}

	function GetStats($serverId, $min, $max)
	{
		if($min === null)
			$min = 0;
		if($max === null)
			$max = PHP_INT_MAX;

		$statement = $this->db->prepare("SELECT agentCount, time
										FROM   stats
										WHERE  serverId = :serverId and time >= :min and time <= :max
										ORDER BY time ASC");

		$statement->bindParam('serverId', $serverId, PDO::PARAM_INT);
		$statement->bindParam('min', $min, PDO::PARAM_INT);
		$statement->bindParam('max', $max, PDO::PARAM_INT);
		$statement->execute();

		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	public function GetUserId($userKey, $shardId)
	{
		$statement = $this->db->prepare("SELECT id
										from agent
										WHERE uuid = :uuid
										AND shardId = :shardId");

		$statement->execute(array(
			'uuid' => $userKey,
			'shardId' => $shardId
		));

		$result = $statement->fetch(PDO::FETCH_ASSOC);

		if(!isset($result['id']))
			return null;

		return $result['id'];
	}

	public function CreateUser($userKey, $name, $shardId)
	{
		$statement = $this->db->prepare("INSERT INTO agent (
											uuid, name, shardId
										) VALUES (
											:uuid, :name, :shardId
										)");

		$statement->execute(array(
			'uuid' => $userKey,
			'name' => $name,
			'shardId' => $shardId
		));

		if(!$statement->rowCount())
		{
			throw new Exception("Failed to add user '" . $name . "' [" . $userKey . "].");
		}

		return $this->db->lastInsertId();
	}

	public function GetOrCreateUserId($userKey, $name, $shardId)
	{
		$userId = $this->GetUserId($userKey, $shardId);
		if($userId === null)
		{
			$userId = $this->CreateUser($userKey, $name, $shardId);
		}

		return $userId;
	}

	public function CreateStats($serverId, $time, $agentCount)
	{
		$statement = $this->db->prepare("INSERT INTO stats (
											serverId, agentCount, time
										) VALUES (
											:serverId, :agentCount, :time
										)");

		$statement->execute(array(
			'serverId' => $serverId,
			'agentCount' => $agentCount,
			'time' => $time
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

	public function CreateOrUpdateServer($name, $shardId, $ownerId, $address, $objectId, $password, $enabled = 1)
	{
		$uuid = $this->GetServerUuidFromObjectId($objectId);
		if($uuid == null)
		{
			$this->CreateServer($name, $shardId, $ownerId, $address, $objectId, $enabled);
		}
		else
		{
			$this->UpdateServer($uuid, $name, $ownerId, $address, $enabled);
		}
	}

	public function CreateServer($name, $shardId, $ownerId, $address, $objectId, $enabled = 1)
	{
		$statement = $this->db->prepare("INSERT INTO stats_server (
											name, shardId, address, ownerId, enabled, uuid, objectId
										) VALUES (
											:name, :shardId, :address, :ownerId, :enabled, UUID(), :objectId
										)");

		$statement->execute(array(
			'name' => $name,
			'shardId' => $shardId,
			'address' => $address,
			'ownerId' => $ownerId,
			'enabled' => $enabled,
			'objectId' => $objectId
		));

		if(!$statement->rowCount())
		{
			throw new Exception("Failed to add server named '" . $name . "'.");
		}

		return $this->db->lastInsertId();
	}
}