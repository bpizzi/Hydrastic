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

/**
 * A text processor is responsible for transforming preformatted text in HTML.
 */
interface TextProcessorInterface 
{
	
	/**
	 * Renders the given $text in plain HTML
	 * @param string $text The text to render in HTML
	 */
	function render($text);

}
