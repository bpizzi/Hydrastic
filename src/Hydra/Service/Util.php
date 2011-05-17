<?php

namespace Hydra\Service;

use Hydra\Util\ArrayMerger;
use Pimple;

class Util extends Pimple
{

	public function __construct($c) {

		$this['array.merger'] = $this->share(function () use ($c) {
			return new ArrayMerger();
		});

	}

}





