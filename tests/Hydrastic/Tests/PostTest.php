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
use Hydrastic\Service\Finder as FinderService;
use Hydrastic\Service\Util as UtilService;
use Hydrastic\Service\Twig as TwigService;

class PostTest extends PHPUnit_Framework_TestCase
{

	protected $dic = array();

	public function setUp() {

		$this->fixDir = __DIR__.'/../fixtures/set1/';

		$this->dic = new Pimple();
		$this->dic['yaml']   = $this->dic->share(function ($c) { return new YamlService($c); });
		$this->dic['finder'] = $this->dic->share(function ($c) { return new FinderService($c); });
		$this->dic['taxonomy'] = $this->dic->share(function ($c) { return new Taxonomy($c); });
		$this->dic['util'] = $this->dic->share(function ($c) { return new UtilService($c); });
		$this->dic['twig']   = $this->dic->share(function ($c) { return new TwigService($c); });
		$this->dic['hydrastic_dir'] = __DIR__.'/../../../';

		$this->dic['conf'] = $this->dic['yaml']['parser']->parse(file_get_contents($this->fixDir.'hydrastic-conf-1.yml')); 

	}

	public function testGetMetadata()
	{
		$file = reset(iterator_to_array($this->dic['finder']['find']->files()->in($this->fixDir)->name('post-1.txt')));

		$post = new Post($this->dic);
		$post->read($file)->clean()->parseMetas();

		$this->assertEquals("Title", $post->getMetadata('title'), "getMetadata('title') retrieve General:title value from post's metadatas");

	}

	/**
 	 * @expectedException Exception
	 */
	public function testReadInvalidFilename() {
		$post = new Post($this->dic);
		$post->read($this->fixDir.'/this-file-does-not-exists-and-reading-it-should-raise-an-exception.txt');
	}

	public function testReadBlankTextFile() {
		$post = new Post($this->dic);
		$post->read($this->fixDir.'/txt/post-blank.markdown');

		$this->assertTrue($post->isIgnored(), "A blank text file should return true to Post->isIgnored()");
	}

	public function testGetFilename() {
		$post = new Post($this->dic);
		$post->read($this->fixDir.'/txt/post-markdown-1.markdown');

		$this->assertEquals('post-markdown-1.markdown', $post->getFilename(), "Post->getFilename() retrieve the filename with its extension");
	}
}
