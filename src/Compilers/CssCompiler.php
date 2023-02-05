<?php

namespace Carrooi\Assets\Compilers;

final class CssCompiler extends BaseCompiler
{

	public function createHtml(string $publicPath): string
	{
		return '<link href="' . $publicPath . '" rel="stylesheet" type="text/css">';
	}

}
