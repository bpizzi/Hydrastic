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
use Hydrastic\AssetManager;

class Asset extends Pimple
{

	public function __construct($c) {

		//This logger will record to disk the hydration process
		$this['manager'] = $this->share(function () use ($c) {
			return new AssetManager($c);
		});
		
	}

}
