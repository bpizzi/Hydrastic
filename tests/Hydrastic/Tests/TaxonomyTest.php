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
require_once 'vfsStream/vfsStream.php';

use Hydrastic\Taxonomy;
use Hydrastic\Post;
use Hydrastic\Service\Yaml as YamlService;
use Hydrastic\Service\Finder as FinderService;
use Hydrastic\Service\Util as UtilService;
use Hydrastic\Service\Twig as TwigService;

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
		$this->dic['twig']   = $this->dic->share(function ($c) { return new TwigService($c); });
		$this->dic['hydrastic_dir'] = __DIR__.'/../../../';

		$this->dic['conf'] = $this->dic['yaml']['parser']->parse(file_get_contents($this->fixDir.'hydrastic-conf-1.yml')); 

	}

	/**
	 *
	 * @test
	 * @group TaxonomyGeneration
	 */
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
		$this->assertEquals($expected, $result, "Correctly parsing taxonomy from hydrastic-conf");
	}

	/**
	 *
	 * @test
	 * @group TaxonomyGeneration
	 */
	public function testTaxonomyInitiateCorrectlyItsTaxonStorage()
	{
		$this->assertFalse($this->dic['taxonomy']->isInitiated(), "isInitiated returns false before initiateTaxonStorage()");
		$this->dic['taxonomy']->initiateTaxonStorage();
		$this->assertTrue($this->dic['taxonomy']->isInitiated(), "isInitiated returns true after initiateTaxonStorage()");

		$this->assertFalse($this->dic['taxonomy']->retrieveTaxonFromName("The Unknown Taxon"), "An unknown taxon shouldn't be found by retrieveTaxonFromName()");

		$this->assertTrue(is_a($this->dic['taxonomy']->retrieveTaxonFromName("Cat"), "Hydrastic\Taxon"), "A Hydra\Taxon object should be found by retrieveTaxonFromName('Cat')");
		$this->assertTrue(is_a($this->dic['taxonomy']->retrieveTaxonFromName("Elem1Subtag2"), "Hydrastic\Taxon"), "A Hydra\Taxon object should be found by retrieveTaxonFromName('Elem1Subtag2')");
		$this->assertTrue(is_a($this->dic['taxonomy']->retrieveTaxonFromName("Elem1Subcat1"), "Hydrastic\Taxon"), "A Hydra\Taxon object should be found by retrieveTaxonFromName('Elem1Subcat1')");
		$this->assertTrue(is_a($this->dic['taxonomy']->retrieveTaxonFromName("Subtag1"), "Hydrastic\Taxon"), "A Hydra\Taxon object should be found by retrieveTaxonFromName('Subtag1')");

	}

	/**
	 *
	 * @test
	 * @group TaxonomyGeneration
	 */
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

	/**
	 * This test use vfsStream : http://code.google.com/p/bovigo/wiki/vfsStream
	 * "vfsStream is a stream wrapper for a virtual file system that may be helpful in unit tests to mock the real file system."
	 * Install it first : 
	 *   $ pear channel-discover pear.php-tools.net
	 *   $ pear install pat/vfsStream-beta
	 *
	 * @test
	 * @group WritingToDisc
	 */
	public function testCreateDirectoryStruct()
	{
		//Mocking the filesystem
		vfsStreamWrapper::register();
		vfsStreamWrapper::setRoot(new vfsStreamDirectory('hydrasticRoot'));
		$this->dic['working_directory'] = vfsStream::url('hydrasticRoot');

		//Quickly testing if vfsStream works well... just to be sure...
		mkdir(vfsStream::url('hydrasticRoot/www'));
		$this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('www'), "www/ should have been created");

		//Load templates in mocked filesystem 
		vfsStream::newDirectory('tpl')->at(vfsStreamWrapper::getRoot());
		foreach ($this->dic['finder']['find']->files()->in($this->fixDir.'tpl/')->name('*.twig') as $f) {
			vfsStream::newFile($f->getFilename())->withContent(file_get_contents($f))->at(vfsStreamWrapper::getRoot()->getChild('tpl'));
		}

		$this->dic['taxonomy']->initiateTaxonStorage();  //Read and initiate taxon storage

		//Read a post, hydrate it, and attach it to the general taxonomy (ie. to each known taxon it's referring to in its metadatas)
		$post = new Post($this->dic);
		$post->read($this->fixDir.'txt/post-1.txt')
			->clean()
			->parseMetas()
			->parseContent()
			->hydrate()
			->attachToTaxonomy();

		$this->dic['taxonomy']->createDirectoryStruct(); //Create directory structure corresponding to the taxon storage

		//Test that folders matching the taxonomy has been created
		$this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('www/cat'), "cat/ should have been created by createDirectoryStruct()");
		$this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('www/cat/cat1'), "cat/cat1 should have been created by createDirectoryStruct()");
		$this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('www/tag/subtag1/elem1subtag1'), "tag/Subtag1/Elem1Subtag1 should have been created by createDirectoryStruct()");
		$this->assertFalse(vfsStreamWrapper::getRoot()->hasChild('www/tag/subtag1/subtag2/elem1subtag2'), "Avoiding path bug in recursivity : tag/subtag1/subtag2/elem1subtag2 shouldn't exist");

		//test that text files were converted to html file in their own folders
		$this->assertTrue(file_exists(vfsStream::url('hydrasticRoot/www/tag/tag2/title.html')), "title.html should have been written in tag/tag2");
		$this->assertTrue(file_exists(vfsStream::url('hydrasticRoot/www/cat/cat1/subcat1/elem1subcat1/title.html')), "title.html should have been written in cat/cat1/subcat1/elem1subcat1");

		//test that an index.html file with a list of posts has been created in each taxonomy folder
		$taxon = $this->dic['taxonomy']->retrieveTaxonFromName("Tag2");
		$this->assertRegExp('/- Title/', $taxon->getHtml());
		$this->assertRegExp("/Posts classified under 'Tag2'/", $taxon->getHtml());
 

	}
}
