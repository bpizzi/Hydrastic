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
use Hydrastic\Theme;
use Hydrastic\Service\Yaml           as YamlService;
use Hydrastic\Service\Finder         as FinderService;
use Hydrastic\Service\Util           as UtilService;
use Hydrastic\Service\Twig           as TwigService;
use Hydrastic\Service\Logger         as LoggerService;
use Hydrastic\Service\TextProcessor  as TextProcessorService;
use Hydrastic\Service\Asset          as AssetService;

class TaxonomyTest extends PHPUnit_Framework_TestCase
{

	protected $dic = array();
	protected $fixDir;

	public function setUp() {

		$this->fixDir = __DIR__.'/../fixtures/set1/';

		$this->dic = new Pimple();
		$this->dic['hydrastic_dir'] = __DIR__.'/../../../';
		$this->dic['yaml']          = $this->dic->share(function ($c) { return new YamlService($c); });
		$this->dic['finder']        = $this->dic->share(function ($c) { return new FinderService($c); });
		$this->dic['taxonomy']      = $this->dic->share(function ($c) { return new Taxonomy($c); });
		$this->dic['util']          = $this->dic->share(function ($c) { return new UtilService($c); });
		$this->dic['twig']          = $this->dic->share(function ($c) { return new TwigService($c); });
		$this->dic['asset']         = $this->dic->share(function ($c) { return new AssetService($c); });
		$this->dic['logger']        = $this->dic->share(function ($c) { return new LoggerService($c); });
		$this->dic['textprocessor'] = $this->dic->share(function ($c) { return new TextProcessorService($c); });

		$this->dic['conf']          = $this->dic['yaml']['parser']->parse(file_get_contents($this->fixDir.'hydrastic-conf-1.yml'));

		//Mocking the filesystem
		vfsStreamWrapper::register();
		vfsStreamWrapper::setRoot(new vfsStreamDirectory('hydrasticRoot'));
		$this->dic['working_directory'] = vfsStream::url('hydrasticRoot');
		mkdir(vfsStream::url('hydrasticRoot/log'));
		mkdir(vfsStream::url('hydrasticRoot/www'));

		//Load templates in mocked filesystem 
		vfsStream::newDirectory('tpl/default/')->at(vfsStreamWrapper::getRoot());
		foreach ($this->dic['finder']['find']->files()->in($this->fixDir.'tpl/default/') as $f) {
			vfsStream::newFile($f->getFilename())->withContent(file_get_contents($f))->at(vfsStreamWrapper::getRoot()->getChild('tpl/default'));
		}

		//Loading the theme
		$this->dic['theme'] = new Theme($this->dic);
		$this->dic['theme']->validate();
	}

	/**
	 *
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

		$taxParent = $this->dic['taxonomy']->retrieveTaxonFromName("Cat");
		$taxChild = $this->dic['taxonomy']->retrieveTaxonFromName("Cat1");
		$this->assertTrue($taxParent->hasChildByName("Cat1"), '"Cat" taxon should have "Cat1" taxon as child');
		$this->assertFalse($taxParent->hasChildByName("Cat1Unknown"), '"Cat" taxon shouldn\'t have "CatUnknown" taxon as child');
		$this->assertTrue($taxParent->hasChild($taxChild), '"Cat" taxon should have "Cat1" taxon as child');
		$this->assertFalse($taxParent->hasChild(new Hydrastic\Taxon(null)), 'Blank taxon shouldn\'t have "Cat" taxon as parent');

		$taxParent = $this->dic['taxonomy']->retrieveTaxonFromName("Cat1");
		$taxChild = $this->dic['taxonomy']->retrieveTaxonFromName("Subcat1");
		$this->assertTrue($taxParent->hasChild($taxChild), '"Cat1" taxon should have "Subcat1" taxon as child');

		$taxParent = $this->dic['taxonomy']->retrieveTaxonFromName("Subcat1");
		$taxChild = $this->dic['taxonomy']->retrieveTaxonFromName("Elem1Subcat1");
		$this->assertTrue($taxParent->hasChild($taxChild), '"Subcat1" taxon should have "Elem1Subcat1" taxon as child');

		$taxParent = $this->dic['taxonomy']->retrieveTaxonFromName("Subcat1");
		$taxChild = $this->dic['taxonomy']->retrieveTaxonFromName("Elem2Subcat1");
		$this->assertTrue($taxParent->hasChild($taxChild), '"Subcat1" taxon should have "Elem2Subcat1" taxon as child');

		$taxParent = $this->dic['taxonomy']->retrieveTaxonFromName("Subcat1");
		$taxChild = $this->dic['taxonomy']->retrieveTaxonFromName("Subcat1");
		$this->assertFalse($taxParent->hasChild($taxChild), 'A taxon shouldn\'t have itself as a child');

	}

	/**
	 *
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
	 * @group WritingToDisc
	 */
	public function testCreateDirectoryStruct()
	{

		//Load templates in mocked filesystem for $post->hydrate and $taxon->hydrate to work
		vfsStream::newDirectory('tpl/default/')->at(vfsStreamWrapper::getRoot());
		foreach ($this->dic['finder']['find']->files()->in($this->fixDir.'tpl/')->name('*.twig') as $f) {
			vfsStream::newFile($f->getFilename())->withContent(file_get_contents($f))->at(vfsStreamWrapper::getRoot()->getChild('tpl/default'));
		}

		$this->dic['taxonomy']->initiateTaxonStorage();  //Read and initiate taxon storage
		$this->dic['taxonomy']->createDirectoryStruct(); //Create directory structure corresponding to the taxon storage

		$this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('www/cat'), "cat/ should have been created by createDirectoryStruct()");
		$this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('www/cat/cat1'), "cat/cat1 should have been created by createDirectoryStruct()");
		$this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('www/tag/subtag1/elem1subtag1'), "tag/Subtag1/Elem1Subtag1 should have been created by createDirectoryStruct()");
		$this->assertFalse(vfsStreamWrapper::getRoot()->hasChild('www/tag/subtag1/subtag2/elem1subtag2'), "Avoiding path bug in recursivity : tag/subtag1/subtag2/elem1subtag2 shouldn't exist");
	}

	public function testCreateIndexFiles()
	{
		//Load templates in mocked filesystem for $post->hydrate and $taxon->hydrate to work
		vfsStream::newDirectory('tpl/default/')->at(vfsStreamWrapper::getRoot());
		foreach ($this->dic['finder']['find']->files()->in($this->fixDir.'tpl/')->name('*.twig') as $f) {
			vfsStream::newFile($f->getFilename())->withContent(file_get_contents($f))->at(vfsStreamWrapper::getRoot()->getChild('tpl/default'));
		}

		$this->dic['taxonomy']->initiateTaxonStorage();  //Read and initiate taxon storage

		$post = new Post($this->dic);
		$post->read($this->fixDir.'txt/post-1.txt')
			->clean()
			->parseMetas()
			->parseContent()
			->hydrate()
			->attachToTaxonomy();

		$this->dic['taxonomy']->createDirectoryStruct(); //Create directory structure corresponding to the taxon storage

		$this->assertTrue(file_exists(vfsStream::url('www/tag/tag2/title.html')), "title.html should have been written in tag/tag2");
		$this->assertTrue(file_exists(vfsStream::url('www/cat/cat1/subcat1/elem1subcat1/title.html')), "title.html should have been written in cat/cat1/subcat1/elem1subcat1");
	}

	/**
	 * This test is just to kill a nasty bug where terminal taxon is duplicated
	 *
	 *
	 **/
	public function testBugDuplicateTaxonEntry()
	{

	}

}
