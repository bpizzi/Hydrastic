<?php

require_once __DIR__.'/vendor/Symfony/Component/ClassLoader/UniversalClassLoader.php';

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->registerNamespaces(array(
	'Symfony' => __DIR__.'/vendor',
	'Monolog' => __DIR__.'/vendor/monolog/src',
	'Hydrastic'   => __DIR__.'/src',
));
$loader->registerPrefixes(array(
	'Pimple' => __DIR__.'/vendor/pimple/lib',
));
$loader->register();
