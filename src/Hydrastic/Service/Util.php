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

use Hydrastic\Util\ArrayMerger;
use Hydrastic\Util\Slugify;
use Pimple;

class Util extends Pimple
{

	public function __construct($c) {

		$this['array.merger'] = $this->share(function () use ($c) {
			return new ArrayMerger();
		});

		$this['slugify'] = $this->share(function () use ($c) {
			return new Slugify();
		});

	}

}
