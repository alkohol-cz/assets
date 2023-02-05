<?php

namespace Carrooi\Assets\UI;

use Carrooi\Assets\Assets;
use Nette\Application\UI\Control;

final class AssetsControl extends Control
{

	/** @var Assets */
	private $assets;

	public function __construct(Assets $assets)
	{
		parent::__construct();

		$this->assets = $assets;
	}

	public function render(string $namespace, string $resource): void
	{
		$resourceObj = $this->assets->getResource($namespace, $resource);

		if ($resourceObj->needsRebuild()) {
			$resourceObj->build();
		}

		echo $resourceObj->createHtml();
	}

}
