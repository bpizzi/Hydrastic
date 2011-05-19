<?php

use Hydra\Post;
use Hydra\Service\Yaml as YamlService;

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

