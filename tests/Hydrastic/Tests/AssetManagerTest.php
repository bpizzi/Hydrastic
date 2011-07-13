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
		//Declaring some nested file by hand... #lazydev
		vfsStream::newDirectory('js/')->at(vfsStreamWrapper::getRoot()->getChild('tpl/default'));
		vfsStream::newFile('f.js')->withContent(file_get_contents($this->fixDir.'tpl/default/js'))->at(vfsStreamWrapper::getRoot()->getChild('tpl/default/js'));
		vfsStream::newDirectory('subjs1/')->at(vfsStreamWrapper::getRoot()->getChild('tpl/default/js'));
		vfsStream::newFile('f1.js')->withContent(file_get_contents($this->fixDir.'tpl/default/js/subjs1/f1.js'))->at(vfsStreamWrapper::getRoot()->getChild('tpl/default/js/subjs1'));
		vfsStream::newDirectory('subjs2')->at(vfsStreamWrapper::getRoot()->getChild('tpl/default/js/subjs1'));
		vfsStream::newFile('f2.js')->withContent(file_get_contents($this->fixDir.'tpl/default/js/subjs1/subjs2/f2.js'))->at(vfsStreamWrapper::getRoot()->getChild('tpl/default/js/subjs1/subjs2'));

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

	public function testOffsetExists()
	{
		$this->assertEquals(isset($this->dic['asset']['manager']['blank.png']), false, 'An offset isn\'t yet setted');
		$asset = $this->dic['asset']['manager']['blank.png'];
		$this->assertEquals(isset($this->dic['asset']['manager']['blank.png']), true, 'An offset is setted after having been accessed.');
	}

	public function testOffsetGet()
	{
		$contentFromDisk = file_get_contents(vfsStream::url('tpl/default/blank.png'));
		$contentFromAssetManager = file_get_contents($this->dic['asset']['manager']['blank.png']);
		$this->assertEquals($contentFromDisk, $contentFromAssetManager);
	}

	/**
 	 * @expectedException Exception
	 */
	public function testOffsetGetWhenAssetIsInexistant()
	{
		$js2 = $this->dic['asset']['manager']['nonexistant.txt'];
	}

	public function testPublish()
	{

		$asset = $this->dic['asset']['manager']['blank.png'];
		$asset1 = $this->dic['asset']['manager']['blank1.png'];
		$asset2 = $this->dic['asset']['manager']['blank2.png'];
		$asset3 = $this->dic['asset']['manager']['dummy.jpg'];
		$js = $this->dic['asset']['manager']['js/f.js'];
		$js1 = $this->dic['asset']['manager']['js/subjs1/f1.js'];
		$js2 = $this->dic['asset']['manager']['js/subjs1/subjs2/f2.js'];
		$this->dic['asset']['manager']->publish();

		$this->assertTrue(isset($this->dic['asset']['manager']['blank.png']));
		$this->assertTrue(isset($this->dic['asset']['manager']['blank1.png']));
		$this->assertTrue(isset($this->dic['asset']['manager']['blank2.png']));
		$this->assertTrue(isset($this->dic['asset']['manager']['dummy.jpg']));
		$this->assertTrue(isset($this->dic['asset']['manager']['js/f.js']));
		$this->assertTrue(isset($this->dic['asset']['manager']['js/subjs1/f1.js']));

		$contentFromTpl = file_get_contents(vfsStream::url('tpl/default/blank.png'));
		$contentFromWww = file_get_contents(vfsStream::url('www/assets/blank.png'));
		$this->assertEquals($contentFromTpl, $contentFromWww, 'File blank.png published correctly');

		$contentFromTpl = file_get_contents(vfsStream::url('tpl/default/blank1.png'));
		$contentFromWww = file_get_contents(vfsStream::url('www/assets/blank1.png'));
		$this->assertEquals($contentFromTpl, $contentFromWww, 'File blank1.png published correctly');

		$contentFromTpl = file_get_contents(vfsStream::url('tpl/default/blank2.png'));
		$contentFromWww = file_get_contents(vfsStream::url('www/assets/blank2.png'));
		$this->assertEquals($contentFromTpl, $contentFromWww, 'File blank2.png published correctly');

		$contentFromTpl = file_get_contents(vfsStream::url('tpl/default/dummy.jpg'));
		$contentFromWww = file_get_contents(vfsStream::url('www/assets/dummy.jpg'));
		$this->assertEquals($contentFromTpl, $contentFromWww, 'File dummy.jpg');

		$contentFromTpl = file_get_contents(vfsStream::url('tpl/default/js/f.js'));
		$contentFromWww = file_get_contents(vfsStream::url('www/assets/js/f.js'));
		$this->assertEquals($contentFromTpl, $contentFromWww, 'File js/f.js');

		$contentFromTpl = file_get_contents(vfsStream::url('tpl/default/js/subjs1/f1.js'));
		$contentFromWww = file_get_contents(vfsStream::url('www/assets/js/subjs1/f1.js'));
		$this->assertEquals($contentFromTpl, $contentFromWww, 'File js/subjs1/f1.js');
	}

}

