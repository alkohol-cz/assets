<?php

/**
 * Test: Carrooi\Assets\DI\IAssetsProvider
 *
 * @testCase CarrooiTests\Assets\DI\AssetsProviderTest
 * @author   David Kudera
 */

namespace CarrooiTests\Assets\DI;

use Carrooi\Assets\Assets;
use Carrooi\Assets\DI\IAssetsProvider;
use Nette\Configurator;
use Nette\DI\CompilerExtension;
use Tester\Assert;
use Tester\FileMock;
use Tester\TestCase;

require_once __DIR__ . '/../../bootstrap.php';

final class AssetsProviderTest extends TestCase
{

	/** @var Assets */
	private $assets;

	private function createContainer(?string $configFile = null)
	{
		$config = new Configurator;
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(['appDir' => __DIR__ . '/../']);
		$config->addConfig(__DIR__ . '/../config/config.neon');

		if ($configFile) {
			$config->addConfig($configFile);
		}

		$context = $config->createContainer();

		$this->assets = $context->getByType(Assets::class);
	}

	public function testFunctionality(): void
	{
		$this->createContainer(FileMock::create('{extensions: [CarrooiTests\Assets\DI\OtherAssetsProvider]}', 'neon'));

		$css = $this->assets->getResource('front', 'css');

		$files = [
			__DIR__ . '/../files/css/style.css',
			__DIR__ . '/../files/css/components/widgets/favorite.css',
			__DIR__ . '/../files/css/components/footer.css',
			__DIR__ . '/../files/css/components/menu.css',
			__DIR__ . '/../files/css/core/mixins.css',
			__DIR__ . '/../files/css/core/variables.css',
			__DIR__ . '/../files/css/other.css',
		];
		$files = array_map(function ($path) {
			return realpath($path);
		}, $files);
		sort($files);

		$actual = $css->getFiles();
		sort($actual);

		Assert::same($files, $actual);
	}

}

final class OtherAssetsProvider extends CompilerExtension implements IAssetsProvider
{

	/**
	 * @return array<mixed>
	 */
	public function getAssetsConfiguration(): array
	{
		return [
			'front' => [
				'css' => [
					'paths' => [
						__DIR__ . '/../files/css/other.css',
					],
				],
			],
		];
	}

}

run(new AssetsProviderTest);
