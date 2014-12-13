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

	function SetServerStatus($serverId, $isEnabled)
	{
		$statement = $this->db->prepare("UPDATE servers SET
											enabled = :isEnabled
										WHERE id = :serverId
										LIMIT 1");

		$statement->bindParam('serverId', $serverId, PDO::PARAM_INT);
		$statement->bindParam('isEnabled', $isEnabled, PDO::PARAM_INT);
		$statement->execute();
	}

	function UpdateServer($serverId, $ownerId, $address, $enabled)
	{
		$statement = $this->db->prepare("UPDATE servers SET
											ownerId = :ownerId,
											address = :address,
											enabled = :enabled
										WHERE id = :serverId
										LIMIT 1");
		$statement->execute(array(
			'serverId' => $serverId,
			'ownerId' => $ownerId,
			'address' => $address,
			'enabled' => $enabled
		));
	}

	function GetServerAddress($serverId)
	{
		$statement = $this->db->prepare("SELECT servers.address FROM servers WHERE servers.id = :serverId LIMIT 1");

		$statement->execute(array(
			'serverId' => $serverId
		));

		$result = $statement->fetch(PDO::FETCH_ASSOC);

		if(!isset($result['address']))
			return null;

		return $result['address'];
	}

	function GetServersForFrontend()
	{
		$statement = $this->db->prepare("SELECT
											servers.id,
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
		$statement = $this->db->prepare("SELECT * FROM servers");
		$statement->execute();

		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	function GetServerName($serverId)
	{
		$statement = $this->db->prepare("SELECT name
										FROM servers
										WHERE id = :serverId");

		$statement->execute(array(
			'serverId' => $serverId
		));

		$result = $statement->fetch(PDO::FETCH_ASSOC);

		if(!isset($result['name']))
			return null;

		return $result['name'];
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

	function GetShardName($shardId)
	{
		$statement = $this->db->prepare("SELECT name
										FROM shards
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


	public function CreateStats($serverId, $agentCount)
	{
		$statement = $this->db->prepare("INSERT INTO stats (
											serverId, agentCount, time
										) VALUES (
											:serverId, :agentCount, UNIX_TIMESTAMP()
										)");

		$statement->execute(array(
			'serverId' => $serverId,
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
			$this->UpdateServer($serverId, $ownerId, $address, $enabled);
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