<?php

namespace Hydra;

use Symfony\Component\Console\Output\OutputInterface;
use \SplObjectStorage;

/**
 * Taxonomy is classification.
 * A Taxon is a parent family with children ("A taxon", "Two taxa")
 * Posts are classified with couples of Taxon/Child, ex: "My post" => Categories/CategorieOne, Tags/Tag1, etc...
 * Taxonomy in Hydra only accept two levels of classification :
 *   Categories:
 *     - First Categorie
 *     - Second Categorie
 *   Tag:
 *     - First tag
 *     - Second Tag
 *   Complexity:
 *     - Simple post
 *     - Complex post
 *
 * Taxa and children are affected to a post in its metadata.
 *
 * As the Process Command parse the metadatas of text files, it calls addTaxa.
 * This class then holds three main data structures:
 *  - A list of all taxa and their children
 *  - A list of all posts and their associated taxonomy
 *  - A list of all taxon/child couple and posts classified under it
 *
 * @author Baptiste Pizzighini <baptiste@bpizzi.fr>
 */
class Taxonomy 
{

	protected $dic = array(); //The Dependency Injection Container
	protected $output;

	protected $taxonStorage = array(); //Hold taxon objects


	/**
	 * Constructs the Taxonomy object
	 *
	 * @param array $dic The Application's Dependency Injection Container
	 * @param OutputInterface $output Where to log actions
	 */
	public function __construct($dic)
	{
		$this->dic = $dic;
		$this->setTaxonStorage(new SplObjectStorage());
	}

	public function getTaxonStorage()
	{
		return $this->taxonStorage;
	}

	public function setTaxonStorage($taxonStorage)
	{
		$this->taxonStorage = $taxonStorage;
	}

	public function addTaxon($taxon)
 	{
		$this->taxonStorage->attach($taxon, $taxon->getName());
	}

	public function initiateTaxonStorage() 
	{
		foreach ($this->dic['conf']['Taxonomy'] as $parentName => $child) {
			//echo "New parent : $parentName, level 0\n";
			$parent = new Taxon();
			$parent->setName($parentName);
			$parent->setLevel(0); 

			if(is_array($child)) {
				//If the taxon as children
				//echo "Going into deep init for ".sizeof($child)." children of : $parentName\n";
				$this->deepTaxonInit($child, $parent, 0); 
			} else {
				//echo "New child : $child, level 1\n";
				$child = new Taxon();
				$child->setName($child);
				$child->setLevel(1);
				$parent->addChild($child);
			}
		}
		$this->addTaxon($parent, $parent->getName());
	}

	public function deepTaxonInit($children, &$parent, $level)
	{
		$level++;
		foreach ($children as $childName => $grandChildName) {
			//echo "New parent : $childName, level $level\n";
			$subParent = new Taxon();
			$subParent->setName($childName);
			$subParent->setLevel($level);
			$parent->addChild($subParent);

			if (is_array($grandChildName)) {
				//echo "Going into deep init for ".sizeof($grandChildName)." children of : $childName\n";
				$this->deepTaxonInit($grandChildName, $subParent, $level); //recursivity's magic
			} else {
				$grandChildLevel = $level + 1;
				//echo "New child : $grandChildName, level $grandChildLevel\n";
				$grandChild = new Taxon();
				$grandChild->setName($grandChildName);
				$grandChild->setLevel($grandChildLevel);
				$subParent->addChild($grandChild);
			}
		}
	}

	/**
	 * Retrieve taxa's templates, generate the HTML and store it
	 *
	 */
	public function hydrateTaxa() {

	}

	public function createDirectoryStruct() {

	}

	public function writeToDisc() {

	}

}
