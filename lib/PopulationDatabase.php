<?php
include_once(dirname(__FILE__) . "/BaseServerDatabase.php");

class PopulationServerDatabase extends BaseServerDatabase
{
	protected $serverTypeName = 'Population Server';

	public function GetPopulationServersForFrontend($serverTypeId)
	{
		$statement = $this->db->prepare("SELECT
											server.publicToken,
											server.name AS 'serverName',
											shard.name AS 'shardName',
											agent.name AS 'userName',
											region.name as 'regionName',
											server.serverTypeId as 'serverType',
											server.enabled,
											(SELECT
													agentCount
												FROM
													population
												WHERE
													server.id = population.serverid
												ORDER BY time DESC
												LIMIT 1) AS currentPopulation
										FROM
											server
											LEFT JOIN shard ON shard.id = server.shardId
											LEFT JOIN agent ON agent.id = server.ownerId
											LEFT JOIN region on region.id = server.regionId
											LEFT JOIN server_type on server_type.id = server.serverTypeId
										WHERE
											enabled = TRUE AND serverTypeId = :serverTypeId");
		$statement->execute(array(
			'serverTypeId' => $serverTypeId
		));

		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	public function GetPopulation($serverId, $min, $max)
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
} 