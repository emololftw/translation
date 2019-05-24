<?php declare(strict_types = 1);

/**
 * This file is part of the Contributte/Translation
 */

namespace Tests\DI;

use Contributte;
use Nette;
use Tester;
use Tests;
use UnexpectedValueException;

$container = require __DIR__ . '/../../bootstrap.php';

class TranslationExtension extends Tests\TestAbstract
{

	public function test01(): void
	{
		Tester\Assert::exception(function (): void {
			$this->createContainer(['localeResolvers' => ['\\stdClass']]);
		}, Contributte\Translation\Exceptions\InvalidArgument::class, 'Resolver must implement interface "Contributte\\Translation\\LocalesResolvers\\ResolverInterface".');
		Tester\Assert::exception(function (): void {
			$this->createContainer(['cache' => ['factory' => '\\stdClass']]);
		}, Contributte\Translation\Exceptions\InvalidArgument::class, 'Cache factory must implement interface "Symfony\Component\Config\ConfigCacheFactoryInterface".');
		Tester\Assert::exception(function (): void {
			$this->createContainer([]);
		}, Contributte\Translation\Exceptions\InvalidArgument::class, 'Default locale must be set.');
		Tester\Assert::exception(function (): void {
			$this->createContainer(['locales' => ['default' => 'en'], 'loaders' => ['\\stdClass']]);
		}, Contributte\Translation\Exceptions\InvalidArgument::class, 'Loader must implement interface "Symfony\Component\Translation\Loader\LoaderInterface".');
		Tester\Assert::exception(function (): void {
			$this->createContainer(['locales' => ['default' => 'en'], 'dirs' => [__DIR__ . '/__no_exists__']]);
		}, UnexpectedValueException::class);
	}

	public function test02(): void
	{
		try {
			$loader = new Nette\DI\ContainerLoader($this->container->getParameters()['tempDir'], true);

			$loader->load(function (Nette\DI\Compiler $compiler): void {
				$compiler->addExtension('translation', new Contributte\Translation\DI\TranslationExtension());
				$compiler->addExtension('translationProvider', new class extends Nette\DI\CompilerExtension implements Contributte\Translation\DI\TranslationProviderInterface {

					/**
					 * @return string[]
					 */
					public function getTranslationResources(): array
					{
						return [__DIR__ . '/__translation_provider_dir__'];
					}

				});
				$compiler->addConfig(['parameters' => $this->container->getParameters(), 'translation' => ['locales' => ['default' => 'en'], 'dirs' => [__DIR__ . '__config_dir__']]]);
			});

		} catch (UnexpectedValueException $e) {
			Tester\Assert::true(Nette\Utils\Strings::contains($e->getMessage(), __DIR__ . '/__translation_provider_dir__'));// translation provider dirs first !!
		}
	}

	/**
	 * @param string[] $config
	 * @internal
	 */
	private function createContainer(array $config): Nette\DI\Container
	{
		$loader = new Nette\DI\ContainerLoader($this->container->getParameters()['tempDir'], true);

		$class = $loader->load(function (Nette\DI\Compiler $compiler) use ($config): void {
			$compiler->addExtension('translation', new Contributte\Translation\DI\TranslationExtension());
			$compiler->addConfig(['parameters' => $this->container->getParameters(), 'translation' => $config]);
		});

		return new $class();
	}

}


(new TranslationExtension($container))->run();