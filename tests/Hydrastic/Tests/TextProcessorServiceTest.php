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

use Hydrastic\Service\Yaml as YamlService;
use Hydrastic\Service\Finder as FinderService;
use Hydrastic\Service\Util as UtilService;
use Hydrastic\Service\Twig as TwigService;
use Hydrastic\Service\TextProcessor as TextProcessorService;

class TextProcessorServiceTest extends PHPUnit_Framework_TestCase
{

	protected $dic = array();

	public function setUp() {

		$this->fixDir = __DIR__.'/../fixtures/set1/';

		$this->dic = new Pimple();
		$this->dic['yaml']   = $this->dic->share(function ($c) { return new YamlService($c); });
		$this->dic['conf'] = $this->dic['yaml']['parser']->parse(file_get_contents($this->fixDir.'hydrastic-conf-1.yml')); 
		$this->dic['hydrastic_dir'] = __DIR__.'/../../../';
		$this->dic['working_directory'] = $this->fixDir;

		$this->dic['taxonomy'] = $this->dic->share(function ($c) { return new Taxonomy($c); });
		$this->dic['util'] = $this->dic->share(function ($c) { return new UtilService($c); });
		$this->dic['twig']   = $this->dic->share(function ($c) { return new TwigService($c); });
		$this->dic['textprocessor']   = $this->dic->share(function ($c) { return new TextProcessorService($c); });
		$this->dic['finder'] = $this->dic->share(function ($c) { return new FinderService($c); });

	}

	public function testRegisteringExtensions()
	{
		$processor = $this->dic['textprocessor']['markdown'];
		$this->assertTrue(in_array('md', $this->dic['txt_extensions_registered']), 'Markdown service registers .md extension');

		$processor = $this->dic['textprocessor']['texy'];
		$this->assertTrue(in_array('texy', $this->dic['txt_extensions_registered']), 'Texy service registers .texy extension is registered.');

		$processor = $this->dic['textprocessor']['markdown_extra'];
		$this->assertTrue(in_array('mdextra', $this->dic['txt_extensions_registered']), 'MarkdownExtra service registers .md extension');

		$processor = $this->dic['textprocessor']['restructuredtext'];
		$this->assertTrue(in_array('rest', $this->dic['txt_extensions_registered']), 'ReST service registers .rest extension');

		$processor = $this->dic['textprocessor']['textile'];
		$this->assertTrue(in_array('textile', $this->dic['txt_extensions_registered']), 'Textile service registers .textile extension');

	}
}

