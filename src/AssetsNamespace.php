<?php

namespace Carrooi\Assets;

use Nette\SmartObject;

final class AssetsNamespace
{

	use SmartObject;

	/** @var array<string, AssetsResource> */
	private $resources = [];

	/**
	 * @return $this
	 */
	public function addResource(string $name, AssetsResource $resource): self
	{
		$this->resources[$name] = $resource;

		return $this;
	}

	public function getResource(string $name): AssetsResource
	{
		if (!isset($this->resources[$name])) {
			throw new AssetsResourceNotExists('Assets resource ' . $name . ' does not exists.');
		}

		return $this->resources[$name];
	}

}
