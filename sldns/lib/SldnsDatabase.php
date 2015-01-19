<?php

include_once(dirname(__FILE__) . "/../.private/config.php");

class SldnsDatabase
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
		$newDb = new PDO("mysql:host=" . Config::$SldnsDatabaseHost . ";dbname=" . Config::$SldnsDatabaseName . ";charset=utf8", Config::$SldnsDatabaseUser, Config::$SldnsDatabasePassword);
		$newDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$this->db = $newDb;
	}

	function CreateRandomName()
	{
		return bin2hex(openssl_random_pseudo_bytes(16));
	}

	function CreateSalt()
	{
		return openssl_random_pseudo_bytes(32);
	}

	function HashPassword($password, $salt)
	{
		return hex2bin(hash('sha512', $salt.$password));
	}

	function GetSaltedHash($name, $password)
	{
		$salt = $this->GetSalt($name);
		$hash = $this->HashPassword($password, $salt);

		return $hash;
	}

	public function CreateDnsEntry($password)
	{
		$salt = $this->CreateSalt();
		$hash = $this->HashPassword($password, $salt);
		$name = $this->CreateRandomName();

		$statement = $this->db->prepare("INSERT INTO dns (
											name, hash, salt
										) VALUES (
											:name, :hash, :salt
										)");

		$statement->execute(array(
			'name' => $name,
			'hash' => $hash,
			'salt' => $salt,
		));

		if(!$statement->rowCount())
		{
			throw new Exception("Failed to add new DNS record.");
		}

		return $name;
	}

	function UpdateDns($name, $password, $address)
	{
		$hash = $this->GetSaltedHash($name, $password);

		$statement = $this->db->prepare("UPDATE dns SET
											address = :address
										WHERE name = :name AND hash = :hash
										LIMIT 1");

		$statement->execute(array(
			'name' => $name,
			'address' => $address,
			'hash' => $hash,
		));

		if(!$statement->rowCount())
		{
			throw new Exception("Failed to update DNS record.");
		}
	}

	function GetAddress($name, $password)
	{
		$hash = $this->GetSaltedHash($name, $password);

		$statement = $this->db->prepare("SELECT address
										FROM dns
										WHERE name = :name AND hash = :hash
										LIMIT 1");

		$statement->execute(array(
			'name' => $name,
			'hash' => $hash
		));

		$result = $statement->fetch(PDO::FETCH_ASSOC);

		if(!isset($result['address']))
		{
			return null;
		}

		return $result['address'];
	}

	function GetSalt($name)
	{
		$statement = $this->db->prepare("SELECT salt
										FROM dns
										WHERE name = :name
										LIMIT 1");

		$statement->execute(array(
			'name' => $name
		));

		$result = $statement->fetch(PDO::FETCH_ASSOC);

		if(!isset($result['salt']))
		{
			return null;
		}

		return $result['salt'];
	}

	function DeleteDns($name, $password)
	{
		$hash = $this->GetSaltedHash($name, $password);

		$statement = $this->db->prepare("DELETE
										FROM dns
										WHERE name = :name AND hash = :hash
										LIMIT 1");

		$statement->execute(array(
			'name' => $name,
			'hash' => $hash
		));

		if(!$statement->rowCount())
		{
			throw new Exception("Failed to delete DNS record.");
		}
	}
}