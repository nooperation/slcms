<?php

// TODO: DELETE CLASS

class SecondlifeHeader
{
	public function __construct($serverVars)
	{
		if(!isset($serverVars["HTTP_X_SECONDLIFE_REGION"]))
		{
			return;
		}

		$this->isSecondlifeRequest = true;
		//$this->userAgent = $serverVars["HTTP_USER_AGENT"];
		$this->shard = $serverVars["HTTP_X_SECONDLIFE_SHARD"];
		$this->objectName = $serverVars["HTTP_X_SECONDLIFE_OBJECT_NAME"];
		$this->objectKey = $serverVars["HTTP_X_SECONDLIFE_OBJECT_KEY"];
		$this->region = $this->ExtractRegion($serverVars["HTTP_X_SECONDLIFE_REGION"]);
		$this->localPosition = $this->ExtractPosition($serverVars["HTTP_X_SECONDLIFE_LOCAL_POSITION"]);
		$this->localRotation = $serverVars["HTTP_X_SECONDLIFE_LOCAL_ROTATION"];
		$this->localVelocity = $serverVars["HTTP_X_SECONDLIFE_LOCAL_VELOCITY"];
		$this->ownerName = $serverVars["HTTP_X_SECONDLIFE_OWNER_NAME"];
		$this->ownerKey = $serverVars["HTTP_X_SECONDLIFE_OWNER_KEY"];

		if($this->shard == null || $this->objectName == null || $this->objectKey == null || $this->ownerKey == null || $this->ownerName == null || $this->region === null || $this->localPosition === null)
		{
			$this->isSecondlifeRequest = false;
		}
	}

	private function ExtractPosition($input)
	{
		// (173.009827, 75.551231, 60.950001)
		$captures = sscanf($input, "(%f, %f, %f)", $x, $y, $z);

		if($captures != 3)
			return null;

		return array('x' => $x, 'y' => $y, 'z' => $z);
	}

	private function ExtractRegion($input)
	{
		// Jin Ho (264448, 233984)
		$numCaptures = preg_match_all("/^([^\(]+)\((\d+),\s+(\d+)\)$/", $input, $matches);

		if($numCaptures == 0)
			return null;

		return array('name' => $matches[1][0], 'x' => (int)$matches[2][0], 'y' => (int)$matches[3][0]);
	}

	/**
	 * Determines if the following data is valid and from a secondlife request.
	 * @var bool
	 */
	public $isSecondlifeRequest = false;

	/**
	 * The user-agent header sent by LSL Scripts. Contains Server version.
	 * @var string
	 * @example Second Life LSL/12.09.07.264510 (http://secondlife.com)
	 */
	public $userAgent;

	/**
	 * The environment the object is in. "Production" is the main grid and "Testing" is the preview grid Production
	 * @var string
	 * @example Production
	 */
	public $shard;

	/**
	 * The name of the object containing the script
	 * @var string
	 * @example Object
	 */
	public $objectName;

	/**
	 * The key of the object containing the script
	 * @var string
	 * @example 01234567-89ab-cdef-0123-456789abcdef
	 */
	public $objectKey;

	/**
	 * The name of the region the object is in, along with the global coordinates of the region's south-west corner
	 * @var array
	 * @example name => 'Jin Ho', x => 264448, y => 233984
	 */
	public $region;

	/**
	 * The position of the object within the region
	 * @var array
	 * @example x => 173.009827, y => 75.551231, z => 60.950001
	 */
	public $localPosition;

	/**
	 * The rotation of the object containing the script
	 * @var string
	 * @example 0.000000, 0.000000, 0.000000, 1.000000
	 */
	public $localRotation;

	/**
	 * The velocity of the object
	 * @var string
	 * @example 0.000000, 0.000000, 0.000000
	 */
	public $localVelocity;

	/**
	 * Legacy name of the owner of the object
	 * @var string
	 * @example Zeb Wyler
	 */
	public $ownerName;

	/**
	 * UUID of the owner of the object
	 * @var string
	 * @example 01234567-89ab-cdef-0123-456789abcdef
	 */
	public $ownerKey;
}

