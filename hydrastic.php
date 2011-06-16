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


require_once __DIR__.'/autoload.php';

use Symfony\Component\Console\Application;
use Hydrastic\Taxonomy;
use Hydrastic\Command\Process;
use Hydrastic\Command\Compile;
use Hydrastic\Command\Shell;
use Hydrastic\Command\Version;
use Hydrastic\Command\Init;
use Hydrastic\Service\Twig as TwigService;
use Hydrastic\Service\Yaml as YamlService;
use Hydrastic\Service\Finder as FinderService;
use Hydrastic\Service\Util as UtilService;
use Hydrastic\Service\TextProcessor as TextProcessorService;

// "Dic" stands for Dependency Injection Container
// It holds configuration variables and services
// Services are utility object lazily loaded throughout the application
$dic = new Pimple();

// Register services that do not depends on config
$dic['yaml'] = $dic->share(function ($c) { return new YamlService($c); });
$dic['util'] = $dic->share(function ($c) { return new UtilService($c); });

// Set the working directory correctly and read/set the conf 
// depending on being in a phar archive or not.
$workingDir = dirname(Phar::running(false));
if ($workingDir == '') {
	//Currently outside a phar archive
	$dic['inside_phar'] = false;
	$dic['working_directory'] = dirname(__DIR__);
	$dic['hydrastic_dir'] = __DIR__;
	$defaultConf = $dic['yaml']['parser']->parse(file_get_contents(__DIR__.'/hydrastic-default-conf.yml'));
	$userConfFile = $dic['working_directory'].'/hydrastic-conf.yml';
	if (file_exists($userConfFile)) {
		$userConf = $dic['yaml']['parser']->parse(file_get_contents($userConfFile)); 
	} else {
		$userConf = array();
	}
} else {
	//Currently inside a phar archive
	$dic['inside_phar'] = true;
	$dic['working_directory'] = str_replace(array('phar:/','hydrastic.phar'), '', $workingDir);
	$dic['hydrastic_dir'] = Phar::running();
	$userConfFile = $workingDir.'/hydrastic-conf.yml';
	if (file_exists($userConfFile)) {
		Phar::mount('hydrastic-conf.yml', $userConfFile);
		$userConf = $dic['yaml']['parser']->parse(file_get_contents('hydrastic-conf.yml'));
		$dic['user_conf_defined'] = true;
	} else {
		$dic['user_conf_defined'] = false;
		$userConf = array();
	}
	$defaultConf = $dic['yaml']['parser']->parse(file_get_contents(__DIR__.'/hydrastic-default-conf.yml')); 
}
$dic['conf'] = $dic['util']['array.merger']->mergeUniqueKeysRecursive($defaultConf, $userConf);

// Register Taxonomy
$dic['taxonomy'] = $dic->share(function ($c) { return new Taxonomy($c); });

// Register services
$dic['twig']          = $dic->share(function ($c) { return new TwigService($c); });
$dic['finder']        = $dic->share(function ($c) { return new FinderService($c); });
$dic['textprocessor'] = $dic->share(function ($c) { return new TextProcessorService($c); });

// Declare (Symfony Component) Application 
$dic['hydrastic_app'] = new Application('Hydrastic',$dic['conf']['version']);

// Add commands to the Application object
$commands = array(
	new Process($dic), 
	new Shell($dic),
	new Version($dic),
	new Init($dic),
);
if (false === $dic['inside_phar']) {
	$commands[] = new Compile($dic);
}

foreach ($commands as $c) {
	$dic['hydrastic_app']->add($c);
}

//Run the Application
$dic['hydrastic_app']->run();
