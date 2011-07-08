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

class AssetManagerTest extends PHPUnit_Framework_TestCase
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

	public function testExtactFilenameIn() 
	{
		$this->assertEquals($this->dic['asset']['manager']->extractFilenameIn('path/to/filename.ext'), 'filename.ext');
		$this->assertEquals($this->dic['asset']['manager']->extractFilenameIn('/path/to/filename.ext'), 'filename.ext');
		$this->assertEquals($this->dic['asset']['manager']->extractFilenameIn('filename.ext'), 'filename.ext');
		$this->assertEquals($this->dic['asset']['manager']->extractFilenameIn('/filename.ext'), 'filename.ext');
	}

	public function testExtactFoldernameIn() 
	{
		$this->assertEquals($this->dic['asset']['manager']->extractFoldernameIn('path/to/filename.ext'), 'path/to');
		$this->assertEquals($this->dic['asset']['manager']->extractFoldernameIn('/path/to/filename.ext'), 'path/to');
		$this->assertEquals($this->dic['asset']['manager']->extractFoldernameIn('filename.ext'), '');
		$this->assertEquals($this->dic['asset']['manager']->extractFoldernameIn('/filename.ext'), '');
	}

	public function testOffsetGet()
	{
		$contentFromDisk = file_get_contents(vfsStream::url('tpl/default/blank.png'));
		$contentFromAssetManager = file_get_contents($this->dic['asset']['manager']['blank.png']);
		$this->assertEquals($contentFromDisk, $contentFromAssetManager);

		$nonExistantAsset = file_get_contents($this->dic['asset']['manager']['nonexistantfile']);
		echo $nonExistantAsset;
		$this->assertEquals(null, $nonExistantAsset);
	}

	public function testPublish()
	{

	}

}

