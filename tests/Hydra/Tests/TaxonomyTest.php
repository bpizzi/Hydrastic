<?php
/**
 * This file is part of the Hydra package.
 *
 * (c) Baptiste Pizzighini <baptiste@bpizzi.fr> 
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */


use Hydra\Taxonomy;
use Hydra\Post;
use Hydra\Service\Yaml as YamlService;
use Hydra\Service\Finder as FinderService;
use Hydra\Service\Util as UtilService;

class TaxonomyTest extends PHPUnit_Framework_TestCase
{

	protected $dic = array();
	protected $fixDir;

	public function setUp() {

		$this->fixDir = __DIR__.'/../fixtures/set1/';

		$this->dic = new Pimple();
		$this->dic['yaml'] = $this->dic->share(function ($c) { return new YamlService($c); });
		$this->dic['finder'] = $this->dic->share(function ($c) { return new FinderService($c); });
		$this->dic['taxonomy'] = $this->dic->share(function ($c) { return new Taxonomy($c); });
		$this->dic['util'] = $this->dic->share(function ($c) { return new UtilService($c); });

		$this->dic['conf'] = $this->dic['yaml']['parser']->parse(file_get_contents($this->fixDir.'hydra-conf-1.yml')); 

	}

	public function testParsingCorrectlyYamlTaxonomyFile()
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

	public function testTaxonomyInitiateCorrectlyItsTaxonStorage()
	{
		$this->assertFalse($this->dic['taxonomy']->isInitiated(), "isInitiated returns false before initiateTaxonStorage()");
		$this->dic['taxonomy']->initiateTaxonStorage();
		$this->assertTrue($this->dic['taxonomy']->isInitiated(), "isInitiated returns true after initiateTaxonStorage()");

		$this->assertFalse($this->dic['taxonomy']->retrieveTaxonFromName("The Unknown Taxon"), "An unknown taxon shouldn't be found by retrieveTaxonFromName()");

		$this->assertTrue(is_a($this->dic['taxonomy']->retrieveTaxonFromName("Cat"), "Hydra\Taxon"), "A Hydra\Taxon object should be found by retrieveTaxonFromName('Cat')");
		$this->assertTrue(is_a($this->dic['taxonomy']->retrieveTaxonFromName("Elem1Subtag2"), "Hydra\Taxon"), "A Hydra\Taxon object should be found by retrieveTaxonFromName('Elem1Subtag2')");
		$this->assertTrue(is_a($this->dic['taxonomy']->retrieveTaxonFromName("Elem1Subcat1"), "Hydra\Taxon"), "A Hydra\Taxon object should be found by retrieveTaxonFromName('Elem1Subcat1')");
		$this->assertTrue(is_a($this->dic['taxonomy']->retrieveTaxonFromName("Subtag1"), "Hydra\Taxon"), "A Hydra\Taxon object should be found by retrieveTaxonFromName('Subtag1')");

	}

	public function testMutualAttachBetweenTaxonAndPost()
	{
		$this->dic['taxonomy']->initiateTaxonStorage();
		$file = reset(iterator_to_array($this->dic['finder']['find']->files()->in($this->fixDir)->name('post-1.txt')));

		$post = new Post($this->dic);
		$post->read($file)->clean()->parseMetas()->attachToTaxonomy();

		$taxonThatShouldBeAttached = $this->dic['taxonomy']->retrieveTaxonFromName("Elem1Subcat1");
		$notExistingTaxonThatShouldNotBeAttached = $this->dic['taxonomy']->retrieveTaxonFromName("Unknown");
		$existingTaxonThatShouldNotBeAttached = $this->dic['taxonomy']->retrieveTaxonFromName("Elem2Subcat1");

		//Testing $post->hasTaxon()
		$this->assertTrue($post->hasTaxon($taxonThatShouldBeAttached), "Taxon Elem1Subcat1 is attached to its post");
		$this->assertFalse($post->hasTaxon($notExistingTaxonThatShouldNotBeAttached), "An unknown taxon isn't attached to the post");
		$this->assertFalse($post->hasTaxon($existingTaxonThatShouldNotBeAttached), "Taxon Elem2Subcat1 taxon isn't attached to the post");

		//Testing $taxon->hasPost()
		$this->assertTrue($taxonThatShouldBeAttached->hasPost($post), "Taxon 'Elem1Subcat1' knows the post");
		$this->assertFalse($existingTaxonThatShouldNotBeAttached->hasPost($post), "Taxon 'Elem2Subcat1' don't know the post");

		$freshTaxon = $this->dic['taxonomy']->retrieveTaxonFromName("Elem1Subcat1");
		$this->assertTrue($freshTaxon->hasPost($post), "Freshly retrieved Taxon 'Elem1Subcat1' is attached to its post");

	}

}
