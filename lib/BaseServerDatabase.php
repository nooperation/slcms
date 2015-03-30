<?php

// Temporary (so I can read the errors in secondlife...)
ini_set('html_errors', 0);

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

	function DropTestServers()
	{
		$this->db->query('set foreign_key_checks=0;');

		$this->db->query("DELETE from server where server.address like 'TestAddress-%'");
		$this->db->query("DELETE from user WHERE user.name LIKE 'TestUser-%'");
		$this->db->query("DELETE from agent WHERE agent.uuid LIKE 'TestUUID-%'");
		$this->db->query("DELETE from agent WHERE agent.uuid LIKE 'TestOwnerKey-%'");
		$this->db->query("DELETE from region WHERE region.name LIKE 'TestRegion-%'");
		$this->db->query("DELETE from shard WHERE shard.name LIKE 'TestShard-%'");

		$this->db->query('set foreign_key_checks=1;');
	}

	function RemoveServer($authToken)
	{
		$statement = $this->db->prepare("DELETE from server
										WHERE authToken = :authToken
										LIMIT 1");

		$statement->execute(array(
			'authToken' => $authToken,
		));

		if($statement->rowCount() != 1)
		{
			throw new Exception("Failed to delete server");
		}

		return $statement;
	}

	function RegisterServer($shardName, $ownerKey, $ownerName, $objectKey, $serverName, $regionName, $address, $positionX, $positionY, $positionZ, $enabled)
	{
		$shardId = $this->GetOrCreateShardId($shardName);
		$ownerId = $this->GetOrCreateUserId($ownerKey, $ownerName, $shardId);
		$regionId = $this->GetOrCreateRegionId($regionName, $shardId);

		$uninitializedServerAuthToken = $this->GetUninitializedServerAuthToken($objectKey);
		if(!$uninitializedServerAuthToken)
		{
			return $this->RegisterServerEx($shardId, $regionId, $ownerId, null, $address, $objectKey, $serverName, $enabled, $positionX, $positionY, $positionZ, 'Uninitialized');
		}
		else
		{
			$newTokens = $this->RegenerateServerTokens($uninitializedServerAuthToken);
			if(!$newTokens)
			{
				Throw new Exception("Failed to recreate tokens for uninitialized server");
			}

			return $newTokens;
		}
	}

	public function RegisterServerEx($shardId, $regionId, $ownerId, $userId, $address, $objectKey, $name, $enabled, $positionX, $positionY, $positionZ, $serverTypeName)
	{
		$publicToken = GenerateRandomToken();
		$authToken = GenerateRandomToken();
		$serverTypeId = $this->GetOrCreateServerTypeId($serverTypeName);

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

		return array('authToken' => $authToken, 'publicToken' => $publicToken);
	}

	function GetUninitializedServerAuthToken($objectKey)
	{
		$uninitializedServerId = $this->GetOrCreateServerTypeId('Uninitialized');

		$statement = $this->db->prepare("SELECT authToken
										FROM server
										WHERE
											serverTypeId = :uninitializedServerId AND
											objectKey = :objectKey AND
											userId is null
										LIMIT 1");

		$statement->execute(array(
			'uninitializedServerId' => $uninitializedServerId,
			'objectKey' => $objectKey,
		));

		$result = $statement->fetch(PDO::FETCH_ASSOC);

		if(!isset($result['authToken']))
			return null;

		return $result['authToken'];
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

	function UpdateServer($authToken, $address, $name, $shardName, $regionName, $positionX, $positionY, $positionZ, $enabled)
	{
		$shardId = $this->GetOrCreateShardId($shardName);
		$regionId = $this->GetOrCreateRegionId($regionName, $shardId);

		$statement = $this->db->prepare("UPDATE server SET
											address = :address,
											name = :name,
											regionId = :regionId,
											positionX = :positionX,
											positionY = :positionY,
											positionZ = :positionZ,
											enabled = :enabled
										WHERE authToken = :authToken
										LIMIT 1");

		$statement->execute(array(
			'authToken' => $authToken,
			'address' => $address,
			'name' => $name,
			'regionId' => $regionId,
			'positionX' => $positionX,
			'positionY' => $positionY,
			'positionZ' => $positionZ,
			'enabled' => $enabled ? 1 : 0
		));
	}

	function RegenerateServerTokens($authToken)
	{
		$newAuthToken = GenerateRandomToken();
		$newPublicToken = GenerateRandomToken();

		$statement = $this->db->prepare("UPDATE server SET
											authToken = :newAuthToken,
											publicToken = :newPublicToken
										WHERE authToken = :authToken
										LIMIT 1");
		$statement->execute(array(
			'authToken' => $authToken,
			'newAuthToken' => $newAuthToken,
			'newPublicToken' => $newPublicToken
		));

		if($statement->rowCount() == 0)
		{
			throw new Exception("Unable to regenerate server tokens for specified server");
		}

		return array('authToken' => $newAuthToken, 'publicToken' => $newPublicToken);
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

		if($statement->rowCount() == 0)
		{
			throw new Exception("Unable to regenerate auth token for specified server");
		}

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
			'newPublicToken' => $newPublicToken
		));

		if($statement->rowCount() == 0)
		{
			throw new Exception("Unable to regenerate public token for specified server");
		}

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

	function GetServer($authToken)
	{
		$statement = $this->db->prepare("SELECT
											server.id,
											server.serverTypeId as 'serverType',
											shard.name AS 'shardName',
											region.name as 'regionName',
											agent.name AS 'ownerName',
											user.name as 'userName',
											server.address,
											server.authToken,
											server.publicToken,
											server.objectKey,
											agent.uuid AS 'ownerKey',
											server.name AS 'serverName',
											server.enabled,
											server.created,
											server.updated,
											server.positionX,
											server.positionY,
											server.positionZ
										FROM
											server
											LEFT JOIN shard ON shard.id = server.shardId
											LEFT JOIN agent ON agent.id = server.ownerId
											LEFT JOIN region on region.id = server.regionId
											LEFT JOIN server_type on server_type.id = server.serverTypeId
											LEFT JOIN user on user.id = server.userId
										WHERE
											server.authToken = :authToken");
		$statement->execute(array(
			'authToken' => $authToken
		));

		$server = $statement->fetch(PDO::FETCH_ASSOC);
		if($server)
		{
			$server['enabled'] = (bool)$server['enabled'];
		}

		return $server;
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

		return (int)$result['id'];
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

		return (int)$this->db->lastInsertId();
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

		return (int)$result['id'];
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

		return (int)$this->db->lastInsertId();
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

		return (int)$result['id'];
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

		return (int)$this->db->lastInsertId();
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

	function GetServerTypeId($name)
	{
		$statement = $this->db->prepare("SELECT id
										from server_type
										WHERE name = :name
										LIMIT 1");

		$statement->execute(array(
			'name' => $name
		));

		$result = $statement->fetch(PDO::FETCH_ASSOC);

		if(!isset($result['id']))
			return null;

		return (int)$result['id'];
	}

	public function GetOrCreateServerTypeId($name)
	{
		$server_typeId = $this->GetServerTypeId($name);
		if($server_typeId === null)
		{
			$server_typeId = $this->CreateServerType($name);
		}

		return $server_typeId;
	}

	////////////////////
	// TABLE: agent
	////////////////////

	function GetAgentId($uuid, $shardId)
	{
		$statement = $this->db->prepare("SELECT id
										from agent
										WHERE uuid = :uuid AND shardId = :shardId");

		$statement->execute(array(
			'uuid' => $uuid,
			'shardId' => $shardId,
		));

		$result = $statement->fetch(PDO::FETCH_ASSOC);

		if(!isset($result['id']))
			return null;

		return (int)$result['id'];
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

		return (int)$this->db->lastInsertId();
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