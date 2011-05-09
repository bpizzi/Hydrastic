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

// Initiate the conf array
// TODO : read the conf from a yaml file
$dic['conf'] = array(
	'version'           => '0.1',
	'hydraDir'          => __DIR__,
	'websiteDir'        => __DIR__.'/../',
	'txtDir'            => __DIR__."/../txt/",
	'tplDir'            => __DIR__."/../tpl/",
	'wwwDir'            => __DIR__."/../www/",
	'wwwFileExtension'  => 'html',
	'txtFileExtension'  => 'txt',
	'metaDatasDefaults' => array(
		'template'    => 'post',
		'title'       => 'Set your page title in metadatas !',
		'description' => 'Set your page description in metadatas !',
		'author'      => 'Set your page author in metadatas !',
	)
);

// Register services
$dic['twig']   = $dic->share(function ($c) { return new TwigContainer($c); });
$dic['yaml']   = $dic->share(function ($c) { return new YamlContainer($c); });
$dic['finder'] = $dic->share(function ($c) { return new FinderContainer($c); });

// Declare (Symfony Component) Application 
$dic['hydraApp'] = new Application('Hydra',$dic['conf']['version']);

// Add commands to the Application object
$hydraCommands = array(
	new Process($dic), 
	new Compile($dic), //TODO: do not add tis command in PHAR mode
	new Shell($dic),
);
foreach ($hydraCommands as $c) {
	$dic['hydraApp']->add($c);
}

//Run the Application
$dic['hydraApp']->run();

