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
use Hydrastic\TextProcessor\MarkdownExtraProcessor;
use Hydrastic\TextProcessor\TextileProcessor;
use Hydrastic\TextProcessor\RestructuredTextProcessor;

class TextProcessor extends Pimple
{

	public function __construct($c) {

		$this['markdown'] = $this->share(function () use ($c) {
			require_once $c['hydrastic_dir'].'/vendor/markdown/MarkdownParser.php';
			return new MarkdownProcessor();
		});

		$this['markdown_extra'] = $this->share(function () use ($c) {
			require_once $c['hydrastic_dir'].'/vendor/markdown/MarkdownExtraParser.php';
			return new MarkdownExtraProcessor();
		});

		$this['textile'] = $this->share(function () use ($c) {
			require_once $c['hydrastic_dir'].'/vendor/textile/classTextile.php';
			return new TextileProcessor();
		});

		$this['restructuredtext'] = $this->share(function () use ($c) {
			require_once $c['hydrastic_dir'].'/vendor/restructuredtext/restructuredtext.php';
			return new RestructuredTextProcessor();
		});

	}

}

