<?php

namespace Carrooi\Assets\Compilers;

use Nette\SmartObject;

abstract class BaseCompiler
{

	use SmartObject;

	/** @var array<callable> */
	public $fileFilters = [];

	abstract public function createHtml(string $publicPath): string;

	/**
	 * @param callable $filter
	 * @return $this
	 */
	public function addFileFilter(callable $filter): self
	{
		$this->fileFilters[] = $filter;

		return $this;
	}

	/**
	 * @param array<string> $files
	 */
	public function compile(array $files): string
	{
		$result = [];
		foreach ($files as $file) {
			$result[] = $this->loadFile($file);
		}

		return implode("\n", $result) . "\n";
	}

	protected function loadFile(string $path): string
	{
		$file = file_get_contents($path);

		foreach ($this->fileFilters as $filter) {
			$file = $filter($file);
		}

		return $file;
	}

}
