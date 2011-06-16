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

namespace Hydrastic\TextProcessor;

use Hydrastic\TextProcessor\TextProcessorInterface;

class MarkdownExtraProcessor implements TextProcessorInterface
{
	
	public function render($content) {
		$parser = new \MarkdownExtraParser();

		return $parser->transform($content);
	}

}


