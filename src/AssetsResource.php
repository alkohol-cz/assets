<?php

namespace Carrooi\Assets;

use Carrooi\Assets\Compilers\BaseCompiler;
use Carrooi\Helpers\FileSystemHelpers;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\SmartObject;

final class AssetsResource
{

	use SmartObject;

	public const CACHE_NAMESPACE = 'carrooi.assets';

	/** @var string */
	private $namespace;

	/** @var BaseCompiler */
	private $compiler;

	/** @var Cache */
	private $cache;

	/** @var array<int, string|array<string>> */
	private $paths = [];

	/** @var string|null */
	private $target;

	/** @var string */
	private $publicPath;

	/** @var bool */
	private $debugMode = false;

	/** @var array|null */
	private $files;

	/** @var array */
	private $times = [];

	/** @var callable[] */
	private $filters = [];

	/** @var string */
	private $output;

	/** @var int */
	private $rebuildReason;

	public function __construct(string $namespace, BaseCompiler $compiler, IStorage $storage)
	{
		$this->namespace = $namespace;
		$this->compiler = $compiler;
		$this->cache = new Cache($storage, self::CACHE_NAMESPACE);
	}

	public function getCompiler(): BaseCompiler
	{
		return $this->compiler;
	}

	/**
	 * @param string|array<string>
	 * @return $this
	 */
	public function addPath($path): self
	{
		$this->paths[] = $path;

		return $this;
	}

	/**
	 * @return array<int, string|array<string>>
	 */
	public function getPaths(): array
	{
		return $this->paths;
	}

	/**
	 * @return array<string>
	 */
	public function getFiles(): array
	{
		if ($this->files === null) {
			$this->files = FileSystemHelpers::expandFiles($this->getPaths());
		}

		return $this->files;
	}

	public function getTarget(): ?string
	{
		return $this->target;
	}

	/**
	 * @return $this
	 */
	public function setTarget(?string $target): self
	{
		$this->target = $target;

		return $this;
	}

	public function getPublicPath(bool $versioned = false): string
	{
		$path = $this->publicPath;
		if ($versioned) {
			$path .= '?&v=' . $this->getCurrentVersion();
		}

		return $path;
	}

	/**
	 * @return $this
	 */
	public function setPublicPath(string $path): self
	{
		$this->publicPath = $path;

		return $this;
	}

	public function isDebugMode(): bool
	{
		return $this->debugMode === true;
	}

	/**
	 * @return $this
	 */
	public function setDebugMode(bool $debugMode = true): self
	{
		$this->debugMode = $debugMode;

		return $this;
	}

	/**
	 * @return mixed
	 */
	private function loadCacheData(string $key, ?callable $fallback = null)
	{
		return $this->cache->load($this->namespace . '.' . $key, $fallback);
	}

	/**
	 * @param mixed $data
	 */
	private function saveCacheData(string $key, $data): void
	{
		$this->cache->save($this->namespace . '.' . $key, $data);
	}

	public function getCurrentVersion(): int
	{
		return (int) $this->loadCacheData('version');
	}

	private function increaseVersion(): int
	{
		$version = $this->getCurrentVersion();

		if ($version === 0) {
			$version = 1;
		} else {
			$version++;
		}

		$this->saveCacheData('version', $version);

		return $version;
	}

	/**
	 * @return array<string>
	 */
	private function getOldFiles(): array
	{
		return $this->loadCacheData('files', function () {
			return [];
		});
	}

	private function getFileModified(string $path): int
	{
		if (!isset($this->times[$path])) {
			$this->times[$path] = filemtime($path);
		}

		return $this->times[$path];
	}

	/**
	 * @return $this
	 */
	public function addFilter(callable $filter): self
	{
		$this->filters[] = $filter;

		return $this;
	}

	public function getOutput(): string
	{
		if (!$this->output) {
			if ($this->needsRebuild()) {
				$this->build();
			} else {
				$this->output = file_get_contents($this->getTarget());
			}
		}

		return $this->output;
	}

	public function getRebuildReason(): int
	{
		return $this->rebuildReason;
	}

	public function createHtml(): string
	{
		return $this->compiler->createHtml($this->getPublicPath(true));
	}

	public function build(): void
	{
		$files = $this->getFiles();
		if (count($files) === 0) {
			throw new InvalidStateException('Missing files to build in ' . $this->namespace . ' assets resource.');
		}

		$timedFiles = [];
		foreach ($files as $file) {
			$timedFiles[$file] = $this->getFileModified($file);
		}

		$this->increaseVersion();

		$this->saveCacheData('files', $timedFiles);

		$output = $this->compiler->compile($files);

		foreach ($this->filters as $filter) {
			$output = $filter($output);
		}

		$this->output = $output;

		file_put_contents($this->getTarget(), $output);
	}

	public function needsRebuild(): bool
	{
		$target = $this->getTarget();
		if (!$target) {
			throw new InvalidStateException('You have to set target for ' . $this->namespace . ' assets resource.');
		}

		if (is_file($target)) {
			if ($this->isDebugMode()) {
				$files = $sortedFiles = $this->getFiles();
				if (count($files) === 0) {
					throw new InvalidStateException('Missing files to build in ' . $this->namespace . ' assets resource.');
				}

				$oldFiles = $this->getOldFiles();
				$oldFilesPaths = array_keys($oldFiles);

				sort($sortedFiles);
				sort($oldFilesPaths);

				if ($sortedFiles === $oldFilesPaths) {
					foreach ($files as $file) {
						if ($this->getFileModified($file) !== $oldFiles[$file]) {
							$this->rebuildReason = Assets::REBUILD_REASON_FILES_CHANGES;

							return true;
						}
					}

					$this->rebuildReason = null;

					return false;
				}

				$this->rebuildReason = Assets::REBUILD_REASON_DIFFERENT_FILES;

				return true;
			}

			$this->rebuildReason = null;

			return false;
		}

		$this->rebuildReason = Assets::REBUILD_REASON_MISSING_TARGET;

		return true;
	}

}
