<?php

namespace Carrooi\Assets\Compilers;

use Nette\Utils\Strings;

final class JsCompiler extends BaseCompiler
{

	public function __construct()
	{
		$this->addFileFilter(function (string $file): string {
			return "(function() {\n" . Strings::indent($file) . "\n}).call();";
		});
	}

	public function createHtml(string $publicPath): string
	{
		return '<script type="text/javascript" src="' . $publicPath . '"></script>';
	}

}
