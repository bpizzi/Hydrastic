<?php 

namespace Hydra;

use \SplObjectStorage;


/**
 * @author Baptiste Pizzighini <baptiste@bpizzi.fr>
 */
class Taxon
{
	protected $dic = array(); //The Dependency Injection Container

	protected $name = null;
	protected $parent = null;
	protected $level;
	protected $children;

	public function __construct()
	{
		$this->setChildren(new SplObjectStorage());
	}

	public function setName($name) { 
		$this->name = $name; 
	}

	public function getName() 
	{ 
		return $this->name; 
	}

	public function setLevel($level) { 
		$this->level = $level; 
	}

	public function getLevel() 
	{ 
		return $this->level; 
	}

	public function setChildren($children) 
	{
		$this->children = $children; 
	}
	public function getChildren() 
	{ 
		return $this->children; 
	}

	public function addChild($child) 
	{
		if (is_a($child, "Hydra\Taxon")) {
			$this->getChildren()->attach($child, $child->getName());
		} else {
			throw new \Exception("\$taxon->addChild() except a Taxon object as first parameter.");
		}
	}

}

