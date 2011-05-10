<?php

namespace Hydra\Container;

use Pimple;

class Twig extends Pimple
{

	public function __construct($c) {

		require_once $c['hydraDir'].'/vendor/twig/lib/Twig/Autoloader.php';

		\Twig_Autoloader::register();

		$this['parser'] = $this->share(function () use ($c) {
			$tplDir = $c['workingDirectory'].'/'.$c['conf']['tplDir'];
			$loader = new \Twig_Loader_Filesystem($tplDir);
			$twig = new \Twig_Environment($loader, array());

			return $twig;
		});


	}
}


