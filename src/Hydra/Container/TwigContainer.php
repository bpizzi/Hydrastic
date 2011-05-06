<?php

namespace Hydra\Container;

use Pimple;

class TwigContainer extends Pimple
{

	public function __construct($c) {

		require_once $c['conf']['hydraDir'].'/vendor/twig/lib/Twig/Autoloader.php';

		\Twig_Autoloader::register();

		$this['parser'] = $this->share(function () use ($c) {
			$loader = new \Twig_Loader_Filesystem($c['conf']['tplDir']);
			$twig = new \Twig_Environment($loader, array());

			return $twig;
		});


	}
}


