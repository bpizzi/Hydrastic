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

use Hydrastic\TextProcessor\TextProcessorBase;
use Hydrastic\TextProcessor\TextProcessorInterface;

class MarkdownProcessor extends TextProcessorBase implements TextProcessorInterface
{

	public $extensions = array(
		'md',
		'mdown',
		'markdown',
	);

	public function render($content) {
		$parser = new \MarkdownParser();

		return $parser->transform($content);
	}

}

