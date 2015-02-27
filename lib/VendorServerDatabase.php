<?php
include_once(dirname(__FILE__) . "/BaseServerDatabase.php");

class VendorServerDatabase extends BaseServerDatabase
{
	public function RegisterServer($shardId, $regionId, $ownerId, $userId, $address, $objectKey, $name, $enabled, $positionX, $positionY, $positionZ)
	{
		$this->RegisterServerEx($shardId, $regionId, $ownerId, $userId, $address, $objectKey, $name, $enabled, $positionX, $positionY, $positionZ, 'Vendor Server');
	}
} 