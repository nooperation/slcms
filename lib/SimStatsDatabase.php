<?php

include_once(dirname(__FILE__) . "/../.private/config.php");
include_once(dirname(__FILE__) . "/Utils.php");
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

	function SetStatsServerStatus($authToken, $isEnabled)
	{
		$statement = $this->db->prepare("UPDATE stats_server SET
											enabled = :isEnabled
										WHERE authToken = :authToken
										LIMIT 1");

		$statement->bindParam('authToken', $authToken, PDO::PARAM_LOB);
		$statement->bindParam('isEnabled', $isEnabled, PDO::PARAM_INT);
		$statement->execute();
	}

	function UpdateStatsServer($authToken, $name, $address, $enabled)
	{
		$statement = $this->db->prepare("UPDATE stats_server SET
											name = :name,
											address = :address,
											enabled = :enabled
										WHERE authToken = :authToken
										LIMIT 1");
		$statement->execute(array(
			'authToken' => $authToken,
			'name' => $name,
			'address' => $address,
			'enabled' => $enabled
		));
	}

	function RegenerateStatServerAuthToken($authToken)
	{
		$newAuthToken = GenerateRandomToken();

		$statement = $this->db->prepare("UPDATE stats_server SET
											authToken = :newAuthToken
										WHERE authToken = :authToken
										LIMIT 1");
		$statement->execute(array(
			'authToken' => $authToken,
			'newAuthToken' => $newAuthToken
		));

		return $newAuthToken;
	}

	function RegenerateStatServerPublicToken($authToken)
	{
		$newPublicToken = GenerateRandomToken();

		$statement = $this->db->prepare("UPDATE stats_server SET
											publicToken = :newPublicToken
										WHERE authToken = :authToken
										LIMIT 1");
		$statement->execute(array(
			'authToken' => $authToken,
			'newPublicToken' => $newPublicToken
		));

		return $newPublicToken;
	}

	function GetStatsServerAddress($publicToken)
	{
		$statement = $this->db->prepare("SELECT address
										FROM stats_server
										WHERE publicToken = :publicToken
										LIMIT 1");

		$statement->execute(array(
			'publicToken' => $publicToken
		));

		$result = $statement->fetch(PDO::FETCH_ASSOC);

		if(!isset($result['address']))
			return null;

		return $result['address'];
	}

	function GetStatsServersForFrontend()
	{
		$statement = $this->db->prepare("SELECT
											stats_server.publicToken,
											stats_server.name AS 'serverName',
											shard.name AS 'shardName',
											agent.name AS 'userName',
											stats_server.enabled,
											(SELECT
													agentCount
												FROM
													population
												WHERE
													stats_server.id = population.serverid
												ORDER BY time DESC
												LIMIT 1) AS currentPopulation
										FROM
											stats_server
											LEFT JOIN shard ON shard.id = stats_server.shardId
											LEFT JOIN agent ON agent.id = stats_server.ownerId
										WHERE
											enabled = TRUE;");
		$statement->execute();

		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	function GetStatsServers()
	{
		$statement = $this->db->prepare("SELECT *
										FROM stats_server");
		$statement->execute();

		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	function GetStatsServerNameAndId($publicToken)
	{
		$statement = $this->db->prepare("SELECT id,name
										from stats_server
										WHERE publicToken = :publicToken");

		$statement->execute(array(
			'publicToken' => $publicToken
		));

		$result = $statement->fetch(PDO::FETCH_ASSOC);

		if(!isset($result['id']))
			return null;

		return $result;
	}

	function GetPopulation($serverId, $min, $max)
	{
		if($min === null)
			$min = 0;
		if($max === null)
			$max = PHP_INT_MAX;

		$statement = $this->db->prepare("SELECT agentCount, time
										FROM   population
										WHERE  serverId = :serverId and time >= :min and time <= :max
										ORDER BY time ASC");

		$statement->bindParam('serverId', $serverId, PDO::PARAM_INT);
		$statement->bindParam('min', $min, PDO::PARAM_INT);
		$statement->bindParam('max', $max, PDO::PARAM_INT);
		$statement->execute();

		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	public function CreatePopulation($serverId, $time, $agentCount)
	{
		$statement = $this->db->prepare("INSERT INTO population (
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

	public function RegisterStatsServer($authToken, $name, $shardId, $ownerId, $address, $objectKey, $enabled = 1)
	{
		if(!$this->RemoveUnverifiedToken($authToken))
		{
			throw new Exception("Invalid token.");
		}

		$publicToken = GenerateRandomToken();

		$statement = $this->db->prepare("INSERT INTO stats_server (
											shardId,
											ownerId,
											address,
											publicToken,
											authToken,
											objectKey,
											name,
											enabled
										) VALUES (
											:shardId,
											:ownerId,
											:address,
											:publicToken,
											:authToken,
											:objectKey,
											:name,
											:enabled
										)");

		$statement->execute(array(
			'shardId' => $shardId,
			'ownerId' => $ownerId,
			'address' => $address,
			'publicToken' => $publicToken,
			'authToken' => $authToken,
			'objectKey' => $objectKey,
			'name' => $name,
			'enabled' => $enabled,
		));

		if(!$statement->rowCount())
		{
			throw new Exception("Failed to add stats server named '" . $name . "'.");
		}

		return $this->db->lastInsertId();
	}

	///////////////////////////////////////////
	// SHARED database functionality
	///////////////////////////////////////////

	public function CreateUnverifiedToken()
	{
		$authToken = GenerateRandomToken();

		$statement = $this->db->prepare("INSERT INTO unverified_token (
											authToken
										) VALUES (
											:authToken
										)");

		$statement->execute(array(
			'authToken' => $authToken
		));

		if(!$statement->rowCount())
		{
			throw new Exception("Failed to create new unverified token.");
		}

		return $authToken;
	}

	public function RemoveUnverifiedToken($authToken)
	{
		$statement = $this->db->prepare("DELETE FROM unverified_token
										WHERE authToken = :authToken");

		$statement->execute(array(
			'authToken' => $authToken
		));

		return $statement->rowCount() != 0;
	}

	function GetShards()
	{
		$statement = $this->db->prepare("SELECT *
 										FROM shard");
		$statement->execute();

		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	function GetShardName($shardId)
	{
		$statement = $this->db->prepare("SELECT name
										FROM shard
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
		$statement = $this->db->prepare("INSERT INTO shard (
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
}