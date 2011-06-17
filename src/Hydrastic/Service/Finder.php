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

use Symfony\Component\Finder\Finder as SymfonyFinder;
use Pimple;

class Finder extends Pimple
{

	public function __construct($c) {

		$this['find'] = $this->share(function () use ($c) {
			return new SymfonyFinder();
		});

		$this['txt_files'] = $this->share(function () use ($c) {
			$txtDir = $c['working_directory'].'/'.$c['conf']['txt_dir'].'/';
			$f = new SymfonyFinder();
			$f->files()
				->ignoreVCS(true)
				->in($txtDir);

			foreach ($c['txt_extensions_registered'] as $extension) {
				//$f->name('*.'.$extension);
				echo "Autorise $extension\n";
			}

			return iterator_to_array($f);
		});                         

	}

}
