<?php

namespace Carrooi\Assets;

use Nette\SmartObject;

final class Assets
{

	use SmartObject;

	public const REBUILD_REASON_MISSING_TARGET = 1;

	public const REBUILD_REASON_DIFFERENT_FILES = 2;

	public const REBUILD_REASON_FILES_CHANGES = 3;

	/** @var array<string, AssetsNamespace> */
	private $namespaces = [];

	/**
	 * @return $this
	 */
	public function addNamespace(string $name, AssetsNamespace $namespace): self
	{
		$this->namespaces[$name] = $namespace;

		return $this;
	}

	public function getNamespace(string $name): AssetsNamespace
	{
		if (!isset($this->namespaces[$name])) {
			throw new AssetsNamespaceNotExists('Assets namespace ' . $name . ' does not exists.');
		}

		return $this->namespaces[$name];
	}

	public function getResource(string $namespace, string $resource): AssetsResource
	{
		return $this->getNamespace($namespace)->getResource($resource);
	}

}
