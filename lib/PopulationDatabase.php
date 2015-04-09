<?php
include_once(dirname(__FILE__) . "/BaseServerDatabase.php");

class PopulationServerDatabase extends BaseServerDatabase
{
	protected $serverTypeName = 'Population Server';

	public function GetPopulationServersForFrontend()
	{
		$statement = $this->db->prepare("SELECT
											server.publicToken,
											server.name AS 'serverName',
											shard.name AS 'shardName',
											agent.name AS 'userName',
											region.name as 'regionName',
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
											enabled = TRUE AND server_type.name = :serverTypeName");
		$statement->execute(array(
			'serverTypeName' => $this->serverTypeName,
		));

		$results = $statement->fetchAll(PDO::FETCH_ASSOC);
		for($i = 0; $i < sizeof($results); ++$i)
		{
			$results[$i]['enabled'] = (bool)$results[$i]['enabled'];
			$results[$i]['publicToken'] = bin2hex($results[$i]['publicToken']);
			$results[$i]['currentPopulation'] = (int)$results[$i]['currentPopulation'];
		}

		return $results;
	}

	public function GetPopulation($publicToken, $min, $max)
	{
		if($min === null)
			$min = 0;
		if($max === null)
			$max = PHP_INT_MAX;

		$statement = $this->db->prepare("SELECT agentCount, time
										FROM   population
										LEFT JOIN server on server.id = population.serverId
										WHERE time >= :min and time <= :max and publicToken = :publicToken
										ORDER BY time ASC");

		$statement->bindParam('publicToken', $publicToken, PDO::PARAM_LOB);
		$statement->bindParam('min', $min, PDO::PARAM_INT);
		$statement->bindParam('max', $max, PDO::PARAM_INT);
		$statement->execute();

		$populations = $statement->fetchAll(PDO::FETCH_ASSOC);

		for($i = 0; $i < sizeof($populations); ++$i)
		{
			$populations[$i]['time'] = (int)$populations[$i]['time'];
			$populations[$i]['agentCount'] = (int)$populations[$i]['agentCount'];
		}

		return $populations;
	}

	public function CreatePopulation($publicToken, $time, $agentCount)
	{
		$statement = $this->db->prepare("INSERT INTO population (
											serverId, agentCount, time
										) VALUES (
											(select id from server where server.publicToken = :publicToken limit 1), :agentCount, :time
										)");

		$statement->execute(array(
			'publicToken' => $publicToken,
			'agentCount' => $agentCount,
			'time' => $time
		));

		if(!$statement->rowCount())
		{
			throw new Exception("Failed to add sim stats for server.");
		}

		return $this->db->lastInsertId();
	}
} 