<?php 
/**
 * This file is part of the Hydrastic package.
 *
 * (c) Baptiste Pizzighini <baptiste@bpizzi.fr> 
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */


namespace Hydrastic;

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
	protected $html;
	protected $output = null;

	public function __construct($dic)
	{
		$this->dic = $dic;
		$this->setChildren(new SplObjectStorage());
		$this->setPostStorage(new SplObjectStorage());
		if (isset($dic["output"])) {
			$this->output = $dic['output'];
		}
	}

	public function __toString() 
	{
		return $this->getName();
	}

	public function writeOutput($msg) {
		if (false === is_null($this->output)) {
			$this->output->writeln($msg);
		}
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

	public function setHtml($html)
	{
		$this->html = $html;
	}
	public function getHtml()
	{
		return $this->html;
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
		if (false === is_a($child, "Hydrastic\Taxon")) {
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
		if (false === is_a($post, "Hydrastic\Post")) {
			throw new \Exception("addPost except a Hydrastic\Post object as a first argument");
		}
		$this->getPostStorage()->attach($post);
	}

	public function hasPost($post)
	{
		if (false === is_a($post, "Hydrastic\Post")) {
			throw new \Exception("hasPost except a Hydrastic\Post object as a first argument");
		}
		return $this->getPostStorage()->contains($post) ? true : false;
	}

	/**
	 * Returns true if $this->postStorage has at least one post
	 */
	public function hasPosts()
	{
		return $this->getPostsNumber() > 0 ? true : false;
	}

	public function getPostsNumber()
	{
		return $this->getPostStorage()->count();
	}

	/**
	 * Creates the HTML for the index file of the taxon.
	 * Uses a twig template from tpl_dir
	 **/
	public function hydrateIndexFile() 
	{
		//Choosing which template to use
		$template = "taxon.twig";

		$posts = array();
		$this->getPostStorage()->rewind();
		while ($this->getPostStorage()->valid()) {
			$p = $this->getPostStorage()->current();
			$posts[] = array(
				"title" => $p->getMetadata("title"),
				"slug" => $p->getSlug(),
			);
			$this->getPostStorage()->next();
		}

		$children = array();
		$this->getChildren()->rewind();
		while ($this->getChildren()->valid()) {
			$c = $this->getChildren()->current();
			$children[] = array(
				"name" => $c->getName(),
				"slug" => $c->getSlug(),
			);
			$this->getChildren()->next();
		}

		$this->setHtml($this->dic['twig']['parser']->render(
			$template,
			array(
				"posts" => $posts,
				"children" => $children,
				"taxonName" => $this->getName(),
				"taxonSlug" => $this->getSlug(),
			)
		)); 

		return $this;
	}

	public function writeIndexFile($path)
	{
		if (null === $this->getHtml()) {
			throw new \Exception("You called \$taxon->writeIndexFile() without having called \$taxon->hydrateIndexFile() before");
		}

		$fileToWrite = $path.'/index.html';

		// Write the html
		file_put_contents($fileToWrite, $this->getHtml());
		if (file_exists($fileToWrite) && file_get_contents($fileToWrite) == $this->getHtml()) {
			$this->writeOutput($this->dic['conf']['command_prefix'].' Successfully hydrated <comment>index.html</comment> for taxon <comment>'.$this->getName().'</comment>');
			return true;
		}

		$this->writeOutput($this->dic['conf']['command_prefix'].' <error>ERROR</error> Failed hydrated <comment>index.html</comment> for taxon <comment>'.$this->getName().'</comment>');

		return false;
	}
}
