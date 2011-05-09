<?php

namespace Hydra\Container;

use Symfony\Component\Finder\Finder as SymfonyFinder;
use Pimple;

class Finder extends Pimple
{

	public function __construct($c) {

		$this['find'] = $this->share(function () use ($c) {
			return new SymfonyFinder();
		});

		$this['txtFiles'] = $this->share(function () use ($c) {
			$f = new SymfonyFinder();
			return $f->files()
				->ignoreVCS(true)
				->name('*.'.$c['conf']['txtFileExtension'])
				->in($c['conf']['txtDir']);
		});                         

	}

}




