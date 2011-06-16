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

class Twig extends Pimple
{

	public function __construct($c) {

		require $c['hydrastic_dir'].'/vendor/twig/lib/Twig/Autoloader.php';

		\Twig_Autoloader::register();

		$this['parser'] = $this->share(function () use ($c) {
			$tplDir = $c['working_directory'].'/'.$c['conf']['tpl_dir'].'/'.$c['conf']['theme'].'/';

			$loader = new \Twig_Loader_Filesystem($tplDir);
			$twig = new \Twig_Environment($loader, array());

			return $twig;
		});

	}
}
