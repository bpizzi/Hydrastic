#!/usr/bin/env php
<?php

require_once __DIR__.'/autoload.php';

use Symfony\Component\Console\Application;
use Hydra\HydraCommand\Process;
use Hydra\HydraCommand\Compile;
use Hydra\HydraCommand\Shell;
use Hydra\Container\TwigContainer;

$dic = new Pimple();

$dic['conf'] = array(
	'version'           => '0.1',
	'hydraDir'          => __DIR__,
	'websiteDir'        => __DIR__.'/../',
	'txtDir'            => __DIR__."/../txt/",
	'tplDir'            => __DIR__."/../tpl/",
	'wwwDir'            => __DIR__."/../www/",
	'wwwFileExtension'  => 'html',
	'metaDatasDefaults' => array(
		'template'    => 'post',
		'title'       => 'Set your page title in metadatas !',
		'description' => 'Set your page description in metadatas !',
		'author'      => 'Set your page author in metadatas !',
	)
);

$dic['hydraApp'] = new Application('Hydra',$dic['conf']['version']);

$hydraCommands = array(
	new Process($dic), 
	new Compile($dic), //TODO: do not add tis command in PHAR mode
	new Shell($dic),
);

foreach ($hydraCommands as $c) {
	$dic['hydraApp']->add($c);
}

$dic['hydraApp']->run();

