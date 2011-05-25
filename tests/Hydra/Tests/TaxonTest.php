<?php
/**
 * This file is part of the Hydra package.
 *
 * (c) Baptiste Pizzighini <baptiste@bpizzi.fr> 
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */


use Hydra\Taxon;
use Hydra\Service\Yaml as YamlService;
use Hydra\Service\Util as UtilService;

class TaxonTest extends PHPUnit_Framework_TestCase
{

	protected $dic = array();

	public function setUp() {

		$this->dic = new Pimple();
		$this->dic['taxonomy'] = $this->dic->share(function ($c) { return new Taxonomy($c); });
		$this->dic['yaml']   = $this->dic->share(function ($c) { return new YamlService($c); });
		$this->dic['util'] = $this->dic->share(function ($c) { return new UtilService($c); });

	}

	public function testConstruct()
	{
		$taxon = new Taxon($this->dic);
		$taxon->setName('This is a nice Cat');

		$this->assertEquals($taxon->getSlug(), 'this-is-a-nice-cat', 'Taxon automatic slugging is correctly handled');
	}


}
