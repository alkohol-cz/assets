<?php

namespace Carrooi\Assets\DI;

use Carrooi\Assets\InvalidArgumentException;
use Nette\DI\CompilerExtension;
use Nette\DI\Config\Helpers;
use Nette\PhpGenerator\PhpLiteral;
use Carrooi\Assets\Compilers\CssCompiler;
use Carrooi\Assets\Compilers\JsCompiler;
use Carrooi\Assets\AssetsResource;
use Carrooi\Assets\AssetsNamespace;
use Carrooi\Assets\Assets;
use Carrooi\Assets\UI\AssetsControl;
use Carrooi\Assets\UI\IAssetsControlFactory;

final class AssetsExtension extends CompilerExtension
{

	/** @var array<mixed> */
	private $defaults = [
		'debug' => '%debugMode%',
	];

	/** @var array<mixed> */
	private $namespaceDefaults = [];

	/** @var array<mixed> */
	private $resourceDefaults = [
		'compiler' => null,
		'paths' => [],
		'target' => null,
		'publicPath' => null,
	];

	/** @var array<string, string> */
	private $compilersAliases = [
		'css' => CssCompiler::class,
		'js' => JsCompiler::class,
	];

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		$debugMode = $config['debug'];
		unset($config['debug']);

		$assets = $builder->addDefinition($this->prefix('assets'))
			->setFactory(Assets::class);

		$builder->addDefinition($this->prefix('control'))
			->setFactory(AssetsControl::class)
			->setImplement(IAssetsControlFactory::class);

		foreach ($this->compiler->getExtensions(IAssetsProvider::class) as $extension) {
			/** @var IAssetsProvider $extension */

			$config = Helpers::merge($config, $extension->getAssetsConfiguration());
		}

		foreach ($config as $name => $namespace) {
			$namespace = Helpers::merge($namespace, $this->namespaceDefaults);

			$namespaceName = $this->prefix('namespace.' . $name);
			$namespaceDef = $builder->addDefinition($namespaceName)
				->setFactory(AssetsNamespace::class)
				->setAutowired(false);

			foreach ($namespace as $rName => $resource) {
				$resource = Helpers::merge($resource, $this->resourceDefaults);

				$resourceName = $this->prefix('resource.' . $name . '.' . $rName);
				$resourceDef = $builder->addDefinition($resourceName)
					->setFactory(AssetsResource::class)
					->setArguments([$name . '.' . $rName, $this->parseCompiler($resource['compiler'])])
					->addSetup('setTarget', [$resource['target']])
					->addSetup('setPublicPath', [$resource['publicPath']])
					->addSetup('setDebugMode', [$debugMode])
					->setAutowired(false);

				foreach ($resource['paths'] as $path) {
					$resourceDef->addSetup('addPath', [$path]);
				}

				$namespaceDef->addSetup('$service->addResource(?, $this->getService(?))', [$rName, $resourceName]);
			}

			$assets->addSetup('$service->addNamespace(?, $this->getService(?))', [$name, $namespaceName]);
		}
	}

	private function parseCompiler(string $compiler): PhpLiteral
	{
		if (!isset($this->compilersAliases[$compiler]) && !class_exists($compiler)) {
			throw new InvalidArgumentException('Unknown compiler ' . $compiler);
		}

		if (!class_exists($compiler)) {
			$compiler = $this->compilersAliases[$compiler];
		}

		return new PhpLiteral("new $compiler");
	}

}
