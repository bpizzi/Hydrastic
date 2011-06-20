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

abstract class TextProcessorBase implements TextProcessorInterface
{

	protected $dic = array();
	public $extensions = array();

	public function __construct($c) {
		$this->dic = $c;
	}

	public function register() {
		$this->dic['txt_extensions_registered'] = array_merge($this->dic['txt_extensions_registered'], $this->extensions);
	}
}


