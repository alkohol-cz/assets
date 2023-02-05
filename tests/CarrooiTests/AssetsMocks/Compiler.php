<?php

namespace CarrooiTests\AssetsMocks;

use Carrooi\Assets\Compilers\BaseCompiler;

final class Compiler extends BaseCompiler
{

	/**
	 * @param array $files
	 */
	public function compile(array $files): string
	{
		$result = [];
		foreach ($files as $file) {
			$result[] = $this->loadFile($file);
		}

		return implode(',', $result);
	}

	public function createHtml(string $publicPath): string
	{
		return $publicPath;
	}

}
