<?php 

namespace Hydra;


/**
 * @author Baptiste Pizzighini <baptiste@bpizzi.fr>
 */
class taxon
{
	protected $dic = array(); //The Dependency Injection Container
	protected $output;

	/**
	 * Constructs the Taxon object
	 *
	 * @param array $dic The Application's Dependency Injection Container
	 * @param OutputInterface $output Where to log actions
	 */
	public function __construct($dic, OutputInterface $output = null)
	{
		$this->dic = $dic;
		$this->output = $output;
	}



}
