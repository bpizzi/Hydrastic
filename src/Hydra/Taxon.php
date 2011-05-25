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


namespace Hydra;

use \SplObjectStorage;


class Taxon
{
	protected $dic = array(); //The Dependency Injection Container

	protected $name = null;
	protected $slug = null;
	protected $parent = null;
	protected $level;
	protected $children;
	protected $postStorage;

	public function __construct($dic)
	{
		$this->dic = $dic;
		$this->setChildren(new SplObjectStorage());
		$this->setPostStorage(new SplObjectStorage());
	}

	public function __toString() 
	{
		return $this->getName();
	}

	public function setPostStorage($postStorage) 
	{
		$this->postStorage = $postStorage;
	}

	public function getPostStorage()
	{
		return $this->postStorage;
	}

	public function setName($name) { 
		$this->name = $name; 
		if (isset($this->dic['util'])) {
			$this->setSlug($this->dic['util']['slugify']->slugify($name));
		} else {
			throw new \Exception("You should register 'util' service before calling \$taxon->setName() (it uses \$dic['util']['slugify'] to initiate the taxon slug)");
		}
	}

	public function getName() 
	{ 
		return $this->name; 
	}

	public function setSlug($slug) { 
		$this->slug = $slug; 
	}

	public function getSlug() 
	{ 
		return $this->slug; 
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
		if (false === is_a($child, "Hydra\Taxon")) {
			throw new \Exception("\$taxon->addChild() except a Taxon object as first parameter.");
		} 
		$this->getChildren()->attach($child, $child->getName());
	}

	public function getChildrenNumber() 
	{
		return $this->getChildren()->count();
	}
	public function hasChildren()
	{
		return $this->getChildrenNumber() > 0 ? true : false;
	}

	public function addPost($post)
	{
		if (false === is_a($post, "Hydra\Post")) {
			throw new \Exception("addPost except a Hydra\Post object as a first argument");
		}
		$this->getPostStorage()->attach($post);
	}

	public function hasPost($post)
	{
		if (false === is_a($post, "Hydra\Post")) {
			throw new \Exception("hasPost except a Hydra\Post object as a first argument");
		}
		return $this->getPostStorage()->contains($post) ? true : false;
	}

}
