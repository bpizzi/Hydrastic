#!/usr/bin/env php
<?php

require_once __DIR__.'/autoload.php';

use Symfony\Component\Console\Application;
use Hydra\HydraCommand\ProcessCommand;
use Hydra\HydraCommand\CompileCommand;
use Hydra\Container\TwigContainer;

function l($v) { printf("\n\n---\n"); var_dump($v); printf("\n---\n\n"); }

$hContainer = new Pimple();

$hContainer['conf'] = array(
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

$hydra = new Application('Hydra',$hContainer['conf']['version']);

$process = new ProcessCommand();
$process->setHcontainer($hContainer);
$hydra->add($process);

$compile = new CompileCommand();
$compile->setHcontainer($hContainer);
$hydra->add($compile); //TODO: do not add tis command in PHAR mode

$hydra->run();
