<?php

namespace Carrooi\Assets\DI;

interface IAssetsProvider
{

	/**
	 * @return array<mixed>
	 */
	public function getAssetsConfiguration(): array;

}
