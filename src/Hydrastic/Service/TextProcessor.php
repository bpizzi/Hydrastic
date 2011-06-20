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
use Hydrastic\TextProcessor\TexyProcessor;

class TextProcessor extends Pimple
{

	public function __construct($c) {

		$c['txt_extensions_registered'] = array();

		$this['markdown'] = $this->share(function () use ($c) {
			require_once $c['hydrastic_dir'].'/vendor/markdown/MarkdownParser.php';
			$p = new MarkdownProcessor($c); 
			$p->register();
			return $p;
		});

		$this['markdown_extra'] = $this->share(function () use ($c) {
			require_once $c['hydrastic_dir'].'/vendor/markdown/MarkdownExtraParser.php';
			$p = new MarkdownExtraProcessor($c);
			$p->register();
			return $p;
		});

		$this['textile'] = $this->share(function () use ($c) {
			require_once $c['hydrastic_dir'].'/vendor/textile/classTextile.php';
			$p = new TextileProcessor($c);
			$p->register();
			return $p;
		});

		$this['restructuredtext'] = $this->share(function () use ($c) {
			require_once $c['hydrastic_dir'].'/vendor/restructuredtext/restructuredtext.php';
			$p = new RestructuredTextProcessor($c);
			$p->register();
			return $p;
		});

		$this['texy'] = $this->share(function () use ($c) {
			require_once $c['hydrastic_dir'].'/vendor/texy/Texy/Texy.php';
			$p = new TexyProcessor($c);
			$p->register();
			return $p;
		});

	}

}
