<?php
/**
 * This file is part of the Hydrastic package.
 *
 * (c) Baptiste Pizzighini <baptiste@bpizzi.fr> 
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */


namespace Hydrastic\Service;

use Pimple;
use Hydrastic\TextProcessor\MarkdownProcessor;

class TextProcessor extends Pimple
{

	public function __construct($c) {

		include $c['hydrastic_dir'].'/vendor/markdown/MarkdownParser.php';

		$this['markdown'] = $this->share(function () use ($c) {
			return new MarkdownProcessor();
		});

	}

}

