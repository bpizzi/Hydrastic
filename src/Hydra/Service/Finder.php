<?php
/**
 * This file is part of the Hydra package.
 *
 * (c) Baptiste Pizzighini <baptiste@bpizzi.fr> 
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */


namespace Hydra\Service;

use Symfony\Component\Finder\Finder as SymfonyFinder;
use Pimple;

class Finder extends Pimple
{

	public function __construct($c) {

		$this['find'] = $this->share(function () use ($c) {
			return new SymfonyFinder();
		});

		$this['txt_files'] = $this->share(function () use ($c) {
			$txtDir = $c['working_directory'].'/'.$c['conf']['General']['txt_dir'].'/';
			$extention = '*.'.$c['conf']['General']['txt_file_extension'];
			$f = new SymfonyFinder();
			$f->files()
				->ignoreVCS(true)
				->name($extention)
				->in($txtDir);
			return iterator_to_array($f);
		});                         

	}

}
