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

use Hydrastic\Theme;
use Hydrastic\Service\Yaml as YamlService;
use Hydrastic\Service\Finder as FinderService;
use Hydrastic\Service\Util as UtilService;
use Hydrastic\Service\Logger as LoggerService;

class ThemeTest extends PHPUnit_Framework_TestCase
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
		$this->dic['logger']   = $this->dic->share(function ($c) { return new LoggerService($c); });
		$this->dic['hydrastic_dir'] = __DIR__.'/../../../';

		$this->dic['conf'] = $this->dic['yaml']['parser']->parse(file_get_contents($this->fixDir.'hydrastic-conf-1.yml')); 

 		//Mocking the filesystem
		vfsStreamWrapper::register();
		vfsStreamWrapper::setRoot(new vfsStreamDirectory('hydrasticRoot'));
		mkdir(vfsStream::url('hydrasticRoot/log'));
		$this->dic['working_directory'] = vfsStream::url('hydrasticRoot');

		//Load templates in mocked filesystem 
		vfsStream::newDirectory('tpl/default/')->at(vfsStreamWrapper::getRoot());
		foreach ($this->dic['finder']['find']->files()->in($this->fixDir.'tpl/default/') as $f) {
			vfsStream::newFile($f->getFilename())->withContent(file_get_contents($f))->at(vfsStreamWrapper::getRoot()->getChild('tpl/default'));
		}

		//Loading the theme
		$this->dic['theme'] = new Theme($this->dic);
		$this->dic['theme']->validate();
	}

	public function testValidate() 
	{
		//Loading the theme
		$this->dic['theme'] = new Theme($this->dic);
		$this->assertTrue($this->dic['theme']->isValid(), 'Theme config passes the validatation');
	}

	public function testGetConfKey()
	{
		$this->dic['theme'] = new Theme($this->dic);
		$this->dic['theme']->validate();
		$this->assertEquals('index.twig', $this->dic['theme']->getConfKey('index_template'), 'index_template key is properly retrieved');
		$this->assertEquals('post.twig', $this->dic['theme']->getConfKey('post_template'), 'post_template key is properly retrieved');
		$this->assertEquals('taxon.twig', $this->dic['theme']->getConfKey('taxon_template'), 'taxon_template key is properly retrieved');
	}

	public function testGetThemeFolder() 
	{
		$this->assertEquals($this->dic['theme']->getThemeFolder(), 'vfs://hydrasticRoot/tpl/default');
	}


}

