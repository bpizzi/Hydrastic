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

class FinderServiceTest extends PHPUnit_Framework_TestCase
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

	public function testFindingMarkdownFiles()
	{
		//Finding .markdown files after MarkdownProcessort->register
		$processor = $this->dic['textprocessor']['markdown'];
		$files = array();
		foreach ($this->dic['finder']['txt_files'] as $file) {
			$files[] = $file->getFilename();
		}
		$this->assertTrue(in_array('post-markdown-1.markdown', $files), '.markdown text files are found.');
		$this->assertTrue(in_array('post-markdownextra-1.markdown', $files), '.markdown text files are found.');

	}

	public function testFindingRestFiles() {
		//Finding .rest files after RestructuredTextProcessor->register
		$processor = $this->dic['textprocessor']['restructuredtext'];
		$files = array();
		foreach ($this->dic['finder']['txt_files'] as $file) {
			$files[] = $file->getFilename();
		}
		$this->assertTrue(in_array('post-rst-1.rst', $files), '.rst text files are found.');
	}

	public function testFindingTextileFiles() {
		//Finding .textile files after TextileProcessor->register
		$processor = $this->dic['textprocessor']['textile'];
		$files = array();
		foreach ($this->dic['finder']['txt_files'] as $file) {
			$files[] = $file->getFilename();
		}
		$this->assertTrue(in_array('post-textile-1.textile', $files), '.textile text files are found.');
	}

	public function testFindingTexyFiles() {
		//Finding .texy files after TexyProcessor->register
		$processor = $this->dic['textprocessor']['texy'];
		$files = array();
		foreach ($this->dic['finder']['txt_files'] as $file) {
			$files[] = $file->getFilename();
		}
		$this->assertTrue(in_array('post-texy-1.texy', $files), '.texy text files are found.');

	}
}


