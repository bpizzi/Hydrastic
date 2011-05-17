<?php

require_once __DIR__.'/autoload.php';

use Symfony\Component\Console\Application;
use Hydra\HydraCommand\Process;
use Hydra\HydraCommand\Compile;
use Hydra\HydraCommand\Shell;
use Hydra\HydraCommand\Version;
use Hydra\HydraCommand\Init;
use Hydra\Service\Twig as TwigService;
use Hydra\Service\Yaml as YamlService;
use Hydra\Service\Finder as FinderService;
use Hydra\Service\Util as UtilService;

// "Dic" stands for Dependency Injection Container
// It holds configuration variables and services
// Services are utility object lazily loaded throughout the command
$dic = new Pimple();

// Register services that do not depends on config
$dic['yaml']   = $dic->share(function ($c) { return new YamlService($c); });
$dic['util'] = $dic->share(function ($c) { return new UtilService($c); });

// Set the working directory correctly and read/set the conf 
// depending on being in a phar archive or not.
$workingDir = dirname(Phar::running(false));
if( $workingDir == '' ) {
	//Currently outside a phar archive
	$dic['inside_phar'] = false;
	$dic['working_directory'] = dirname(__DIR__);
	$dic['hydra_dir'] = __DIR__;
	$defaultConf = $dic['yaml']['parser']->parse(file_get_contents(__DIR__.'/hydra-default-conf.yml'));
	$userConfFile = $dic['working_directory'].'/hydra-conf.yml';
	if(file_exists($userConfFile)) {
		$userConf = $dic['yaml']['parser']->parse(file_get_contents($userConfFile)); 
	} else {
		$userConf = array();
	}
} else {
	//Currently inside a phar archive
	$dic['inside_phar'] = true;
	$dic['working_directory'] = str_replace(array('phar:/','hydra.phar'), '', $workingDir);
	$dic['hydra_dir'] = Phar::running();
	$userConfFile = $workingDir.'/hydra-conf.yml';
	if(file_exists($userConfFile)) {
		Phar::mount('hydra-conf.yml', $userConfFile);
		$userConf = $dic['yaml']['parser']->parse(file_get_contents('hydra-conf.yml'));
		$dic['user_conf_defined'] = true;
	} else {
		$dic['user_conf_defined'] = false;
		$userConf = array();
	}
	$defaultConf = $dic['yaml']['parser']->parse(file_get_contents(__DIR__.'/hydra-default-conf.yml')); 
}
$dic['conf'] = $dic['util']['array.merger']->mergeUniqueKeysRecursive($defaultConf, $userConf);

// Register services
$dic['twig']   = $dic->share(function ($c) { return new TwigService($c); });
$dic['finder'] = $dic->share(function ($c) { return new FinderService($c); });

// Declare (Symfony Component) Application 
$dic['hydra_app'] = new Application('Hydra',$dic['conf']['version']);

// Add commands to the Application object
$hydraCommands = array(
	new Process($dic), 
	new Shell($dic),
	new Version($dic),
	new Init($dic),
);
if(!$dic['inside_phar']) {
	$hydraCommands[] = new Compile($dic);
}

foreach ($hydraCommands as $c) {
	$dic['hydra_app']->add($c);
}

//Run the Application
$dic['hydra_app']->run();

