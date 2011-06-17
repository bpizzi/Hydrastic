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

class TextProcessorBase implements TextProcessorInterface
{

	protected $dic = array();

	public function __construct($c) {
		$this->dic = $dic;
	}

	public function render($content) {

	}

	public function register() {
		foreach ($this->extensions as $e) {
			$this->dic['txt_extensions_registered'][] = $e;
		}
	}
}


