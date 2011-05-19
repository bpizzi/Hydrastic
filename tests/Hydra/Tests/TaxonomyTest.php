<?php

use Hydra\Taxonomy;
use Hydra\Post;
use Hydra\Service\Yaml as YamlService;
use Hydra\Service\Finder as FinderService;

class TaxonomyTest extends PHPUnit_Framework_TestCase
{

	protected $dic = array();
	protected $fixDir;

	public function setUp() {

		$this->fixDir = __DIR__.'/../fixtures/';

		$this->dic = new Pimple();
		$this->dic['taxonomy'] = $this->dic->share(function ($c) { return new Taxonomy($c); });
		$this->dic['yaml'] = $this->dic->share(function ($c) { return new YamlService($c); });
		$this->dic['finder'] = $this->dic->share(function ($c) { return new FinderService($c); });
	}

	public function testInitiateTaxonomy()
	{
		$expected = array(
			"Taxonomy" => array(
				"Cat" => array(
					"Cat1" => array(
						"Subcat1" => array("Elem1Subcat1", "Elem2Subcat1"),
					),
					"Cat2" => array("ElemCat2"),
				),
				"Tag" => array(
					"Tag1",
					"Tag2",
					"Subtag1" => array("Elem1Subtag1", ),
					"Subtag2" => array("Elem1Subtag2", ),
				)
			)
		);
		$result = $this->dic['yaml']['parser']->parse(file_get_contents($this->fixDir.'taxonomy-1.yml')); 
		$this->assertEquals($expected, $result, "Correctly parsing taxonomy from hydra-conf");
	}

	/**
	 *
	 * @depends testInitiateTaxonomy
	 */
	public function testAddPostToTaxonomy()
	{

		$this->dic['conf'] = $this->dic['yaml']['parser']->parse(file_get_contents($this->fixDir.'hydra-conf-1.yml')); 
		$file = reset(iterator_to_array($this->dic['finder']['find']->files()->in($this->fixDir)->name('post-1.txt')));

		$post = new Post($this->dic);
		$post->read($file);

	}

}
