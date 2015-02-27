<?php
include_once(dirname(__FILE__) . "/BaseServerDatabase.php");

class VendorServerDatabase extends BaseServerDatabase
{
	public function RegisterServer($shardId, $regionId, $ownerId, $userId, $address, $objectKey, $name, $enabled, $positionX, $positionY, $positionZ)
	{
		$this->RegisterServerEx($shardId, $regionId, $ownerId, $userId, $address, $objectKey, $name, $enabled, $positionX, $positionY, $positionZ, 'Vendor Server');
	}

	public function CreateItem($vendorId, $objectKey, $name, $price, $salePrice, $enabled, $copy, $modify, $transfer)
	{
		$statement = $this->db->prepare("INSERT INTO item (
											vendorId, objectKey, name, price, salePrice, enabled, copy, modify, transfer
										) VALUES (
											:vendorId, :objectKey, :name, :price, :salePrice, :enabled, :copy, :modify, :transfer
										)");

		$statement->execute(array(
			'vendorId' => $vendorId,
			'objectKey' => $objectKey,
			'name' => $name,
			'price' => $price,
			'salePrice' => $salePrice,
			'enabled' => $enabled,
			'copy' => $copy,
			'modify' => $modify,
			'transfer' => $transfer
		));

		if(!$statement->rowCount())
		{
			throw new Exception("Failed to add item '" . $name . "'.");
		}

		return $this->db->lastInsertId();
	}

	public function CreateTransaction($vendorId, $itemId, $agentId, $price)
	{
		$statement = $this->db->prepare("INSERT INTO transaction (
											vendorId, itemId, agentId, price
										) VALUES (
											:vendorId, :itemId, :agentId, :price
										)");

		$statement->execute(array(
			'vendorId' => $vendorId,
			'itemId' => $itemId,
			'agentId' => $agentId,
			'price' => $price
		));

		if(!$statement->rowCount())
		{
			throw new Exception("Failed to add transaction.");
		}

		return $this->db->lastInsertId();
	}
} 