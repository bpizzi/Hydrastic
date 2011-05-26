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


use Hydrastic\Post;
use Hydrastic\Service\Yaml as YamlService;

class PostTest extends PHPUnit_Framework_TestCase
{

	protected $dic = array();

	public function setUp() {

		$this->dic = new Pimple();
		$this->dic['taxonomy'] = $this->dic->share(function ($c) { return new Taxonomy($c); });
		$this->dic['yaml']   = $this->dic->share(function ($c) { return new YamlService($c); });

	}

	public function testConstruct()
	{

	}
}
