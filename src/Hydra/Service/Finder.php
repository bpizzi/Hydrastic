<?php

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
			$txtDir = $c['working_directory'].'/'.$c['conf']['txt_dir'].'/';
			$extention = '*.'.$c['conf']['txt_file_extension'];
			$f = new SymfonyFinder();
			$f->files()
				->ignoreVCS(true)
				->name($extention)
				->in($txtDir);
			return iterator_to_array($f);
		});                         

	}

}




