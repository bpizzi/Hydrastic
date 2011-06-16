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
use Hydrastic\Service\TextProcessor as TextProcessorService;

class MarkdownProcessorTest extends PHPUnit_Framework_TestCase
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
		$this->dic['textprocessor']   = $this->dic->share(function ($c) { return new TextProcessorService($c); });
		$this->dic['hydrastic_dir'] = __DIR__.'/../../../';

		$this->dic['conf'] = $this->dic['yaml']['parser']->parse(file_get_contents($this->fixDir.'hydrastic-conf-1.yml')); 

	}

	public function testRender()
	{
		$file = reset(iterator_to_array($this->dic['finder']['find']->files()->in($this->fixDir)->name('post-markdown-1.markdown')));

		$post = new Post($this->dic);
		$post->read($file)->clean()->parseMetas()->parseContent();

		$matcher1 = array('tag' => 'em', 'content' => 'Lorem');
		$matcher2 = array('tag' => 'strong', 'content' => 'Ipsum');

		$this->assertTag($matcher1, $post->getContent(), 'Mardown processor properly parse markdown formatted text (italic).');
		$this->assertTag($matcher2, $post->getContent(), 'Mardown processor properly parse markdown formatted text (strong).');

	}
}

