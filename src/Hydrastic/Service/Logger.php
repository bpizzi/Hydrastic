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
use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;

class Logger extends Pimple
{

	public function __construct($c) {

		//This logger will record to disk the hydration process
		$this['hydration'] = $this->share(function () use ($c) {
			$l = new MonologLogger('hydration');
			$l->pushHandler(new StreamHandler($c['working_directory'].'/log/hydration.log'));
			return $l;
		});

		//This logger will record to disk the init command
		$this['init'] = $this->share(function () use ($c) {
			$l = new MonologLogger('init');
			$l->pushHandler(new StreamHandler($c['working_directory'].'/log/init.log'));
			return $l;
		});

		//TODO: define others loggers to handle pusblishing process, for ex.

	}

}

