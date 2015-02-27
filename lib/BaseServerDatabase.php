<?php

include_once(dirname(__FILE__) . "/../.private/config.php");
include_once(dirname(__FILE__) . "/Utils.php");
class BaseServerDatabase
{
	/**
	 * @var PDO
	 *   Database connection. Null when not connected.
	 */
	protected $db = null;

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

	////////////////////
	// TABLE: server
	////////////////////

	public function RegisterServer($shardId, $regionId, $ownerId, $userId, $address, $objectKey, $name, $enabled, $positionX, $positionY, $positionZ)
	{
		$this->RegisterServerEx($shardId, $regionId, $ownerId, $userId, $address, $objectKey, $name, $enabled, $positionX, $positionY, $positionZ, 'Base Server');
	}

	public function RegisterServerEx($shardId, $regionId, $ownerId, $userId, $address, $objectKey, $name, $enabled, $positionX, $positionY, $positionZ, $serverTypeId)
	{
		$publicToken = GenerateRandomToken();
		$authToken = GenerateRandomToken();

		$statement = $this->db->prepare("INSERT INTO server (
											serverTypeId,
											shardId,
											regionId,
											ownerId,
											userId,
											address,
											authToken,
											publicToken,
											objectKey,
											name,
											enabled,
											positionX,
											positionY,
											positionZ
										) VALUES (
											:serverTypeId,
											:shardId,
											:regionId,
											:ownerId,
											:userId,
											:address,
											:authToken,
											:publicToken,
											:objectKey,
											:name,
											:enabled,
											:positionX,
											:positionY,
											:positionZ
										)");

		$statement->execute(array(
			'serverTypeId' => $serverTypeId,
			'shardId' => $shardId,
			'regionId' => $regionId,
			'ownerId' => $ownerId,
			'userId' => $userId,
			'address' => $address,
			'authToken' => $authToken,
			'publicToken' => $publicToken,
			'objectKey' => $objectKey,
			'name' => $name,
			'enabled' => $enabled,
			'positionX' => $positionX,
			'positionY' => $positionY,
			'positionZ' => $positionZ
		));

		if(!$statement->rowCount())
		{
			throw new Exception("Failed to add stats server named '" . $name . "'.");
		}

		return $this->db->lastInsertId();
	}

	function SetServerStatus($authToken, $isEnabled)
	{
		$statement = $this->db->prepare("UPDATE server SET
											enabled = :isEnabled
										WHERE authToken = :authToken
										LIMIT 1");

		$statement->bindParam('authToken', $authToken, PDO::PARAM_LOB);
		$statement->bindParam('isEnabled', $isEnabled, PDO::PARAM_INT);
		$statement->execute();
	}

	function UpdateServer($authToken, $name, $address, $enabled)
	{
		$statement = $this->db->prepare("UPDATE server SET
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

	function RegenerateServerAuthToken($authToken)
	{
		$newAuthToken = GenerateRandomToken();

		$statement = $this->db->prepare("UPDATE server SET
											authToken = :newAuthToken
										WHERE authToken = :authToken
										LIMIT 1");
		$statement->execute(array(
			'authToken' => $authToken,
			'newAuthToken' => $newAuthToken
		));

		return $newAuthToken;
	}

	function RegenerateServerPublicToken($authToken)
	{
		$newPublicToken = GenerateRandomToken();

		$statement = $this->db->prepare("UPDATE server SET
											publicToken = :newPublicToken
										WHERE authToken = :authToken
										LIMIT 1");
		$statement->execute(array(
			'authToken' => $authToken,
			'newAuthToken' => $newPublicToken
		));

		return $newPublicToken;
	}

	function GetServerAddress($publicToken)
	{
		$statement = $this->db->prepare("SELECT address
										FROM server
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

	function GetServers()
	{
		$statement = $this->db->prepare("SELECT *
										FROM server");
		$statement->execute();

		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	function GetServerNameAndId($publicToken)
	{
		$statement = $this->db->prepare("SELECT id,name
										from server
										WHERE publicToken = :publicToken");

		$statement->execute(array(
			'publicToken' => $publicToken
		));

		$result = $statement->fetch(PDO::FETCH_ASSOC);

		if(!isset($result['id']))
			return null;

		return $result;
	}

	////////////////////
	// TABLE: shard
	////////////////////

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
		$statement = $this->db->prepare("SELECT id
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

	////////////////////
	// TABLE: user
	////////////////////

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

	////////////////////
	// TABLE: region
	////////////////////

	function GetRegions()
	{
		$statement = $this->db->prepare("SELECT *
 										FROM region");
		$statement->execute();

		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	function GetRegionId($name, $shardId)
	{
		$statement = $this->db->prepare("SELECT id
										from region
										WHERE name = :name AND shardId = :shardId");

		$statement->execute(array(
			'name' => $name,
			'shardId' => $shardId
		));

		$result = $statement->fetch(PDO::FETCH_ASSOC);

		if(!isset($result['id']))
			return null;

		return $result['id'];
	}

	public function CreateRegion($name, $shardId)
	{
		$statement = $this->db->prepare("INSERT INTO region (
											name, shardId
										) VALUES (
											:name, :shardId
										)");

		$statement->execute(array(
			'name' => $name,
			'shardId' => $shardId
		));

		if(!$statement->rowCount())
		{
			throw new Exception("Failed to add region named '" . $name . "'.");
		}

		return $this->db->lastInsertId();
	}

	public function GetOrCreateRegionId($name, $shardId)
	{
		$regionId = $this->GetRegionId($name, $shardId);
		if($regionId === null)
		{
			$regionId = $this->CreateRegion($name, $shardId);
		}

		return $regionId;
	}

	////////////////////
	// TABLE: server_type
	////////////////////
	function GetServerTypes()
	{
		$statement = $this->db->prepare("SELECT *
 										FROM server_type");
		$statement->execute();

		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	////////////////////
	// TABLE: agent
	////////////////////

	function GetAgentId($uuid, $shardId)
	{
		$statement = $this->db->prepare("SELECT id
										from agent
										WHERE name = :name AND uuid = :uuid AND shardId = :shardId");

		$statement->execute(array(
			'uuid' => $uuid,
			'shardId' => $shardId,
		));

		$result = $statement->fetch(PDO::FETCH_ASSOC);

		if(!isset($result['id']))
			return null;

		return $result['id'];
	}

	public function CreateAgent($name, $uuid, $shardId)
	{
		$statement = $this->db->prepare("INSERT INTO agent (
											name, uuid, shardId
										) VALUES (
											:name, :uuid, :shardId
										)");

		$statement->execute(array(
			'name' => $name,
			'uuid' => $uuid,
			'shardId' => $shardId,
		));

		if(!$statement->rowCount())
		{
			throw new Exception("Failed to add agent named '" . $name . "'.");
		}

		return $this->db->lastInsertId();
	}

	public function GetOrCreateAgentId($name, $uuid, $shardId)
	{
		$agentId = $this->GetAgentId($uuid, $shardId);
		if($agentId === null)
		{
			$agentId = $this->CreateAgent($name, $uuid, $shardId);
		}

		return $agentId;
	}
}