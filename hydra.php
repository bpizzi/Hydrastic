#!/usr/bin/env php
<?php

require_once __DIR__.'/autoload.php';

use Symfony\Component\Console\Application;
use Hydra\HydraCommand\Process;
use Hydra\HydraCommand\Compile;
use Hydra\HydraCommand\Shell;
use Hydra\Container\Twig as TwigContainer;
use Hydra\Container\Yaml as YamlContainer;
use Hydra\Container\Finder as FinderContainer;

// "Dic" stands for Dependency Injection Container
// It holds configuration variables and services
// Services are utility object lazily loaded throughout the command
$dic = new Pimple();

function MergeArrays($Arr1, $Arr2)
{
	foreach($Arr2 as $key => $Value)
	{
		if(array_key_exists($key, $Arr1) && is_array($Value)) {
			$Arr1[$key] = MergeArrays($Arr1[$key], $Arr2[$key]);
		} else {

			$Arr1[$key] = $Value;
		}
	}
	return $Arr1;
}

// Register services that do not depends on config
$dic['yaml']   = $dic->share(function ($c) { return new YamlContainer($c); });

// Set the working directory correctly and read/set the conf 
// depending on being in a phar archive or not.
$workingDir = dirname(Phar::running(false));
if( $workingDir == '' ) {
	//Currently outside a phar archive
	$dic['insidePhar'] = false;
	$dic['workingDirectory'] = dirname(__DIR__);
	$dic['hydraDir'] = __DIR__;
	$userConf = $dic['yaml']['parser']->parse(file_get_contents(__DIR__.'/hydra-default-conf.yml'));
	$defaultConf = $dic['yaml']['parser']->parse(file_get_contents(__DIR__.'/../hydra-conf.yml')); 
} else {
	//Currently inside a phar archive
	$dic['insidePhar'] = true;
	$dic['workingDirectory'] = str_replace(array('phar:/','hydra.phar'), '', $workingDir);
	$dic['hydraDir'] = Phar::running();
	Phar::mount('hydra-conf.yml', $workingDir.'/hydra-conf.yml');
	$userConf = $dic['yaml']['parser']->parse(file_get_contents('hydra-conf.yml'));
	$defaultConf = $dic['yaml']['parser']->parse(file_get_contents(__DIR__.'/hydra-default-conf.yml')); 
}
$dic['conf'] = MergeArrays($defaultConf, $userConf);

// Register services
$dic['twig']   = $dic->share(function ($c) { return new TwigContainer($c); });
$dic['finder'] = $dic->share(function ($c) { return new FinderContainer($c); });

// Declare (Symfony Component) Application 
$dic['hydraApp'] = new Application('Hydra',$dic['conf']['version']);

// Add commands to the Application object
$hydraCommands = array(
	new Process($dic), 
	new Compile($dic), //TODO: do not add this command in PHAR mode
	new Shell($dic),
);
foreach ($hydraCommands as $c) {
	$dic['hydraApp']->add($c);
}

//Run the Application
$dic['hydraApp']->run();

