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

use Symfony\Component\Console\Output\OutputInterface;
use \SplObjectStorage;

/**
 * Taxonomy is classification.
 *
 * A Taxon is a parent family with children ("A taxon", "Two taxa")
 * Each child can be a taxon with or without children.
 *
 * Taxonomy in Hydra accept unlimited levels of classification :
 *   Categories:
 *     First Categorie:
 *       Sub-Categorie for first categorie;
 *         Sub-sub-Categorie for first Sub-sategorie:
 *           - First element of Sub-Sub-Categorie
 *       - First element of Sub-Categorie
 *       - Second element for Sub-Categorie
 *     - Second Categorie
 *       -First element for Sub-Categorie
 *   Tag:
 *     - First tag
 *     - Second Tag
 *   Complexity:
 *     - Simple post
 *     - Complex post
 *
 * A nested tree of taxa is initiated by $this->initiateTaxonStorage(), 
 * but $this->dic['conf']['Taxonomy'] must have been read before.
 *
 * Posts can tell which taxa they are affected to in their metadatas.
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

	/**
	 * Go threw the taxonomy array (stored in the DIC), and initiate a tree of Taxon objects.
	 * This function is recursive, and should be called the first time without args.
	 */
	public function initiateTaxonStorage($children = null, &$parent = null, $level = -1) 
	{
		$level ++;
		if (null === $children) {
			$children = $this->dic['conf']['Taxonomy'];
		}
		foreach ($children as $parentName => $child) {
			//echo "New parent : $parentName, level $level\n";
			$subParent = new Taxon();
			if (is_int($parentName)) {
				$subParent->setName(null);
			} else {
				$subParent->setName($parentName);
			}
			$subParent->setLevel($level); 
			if (null !== $parent) {
				$parent->addChild($subParent);
			}

			if(is_array($child)) {
				//If the taxon as children
				//echo "Going into deep init for ".sizeof($child)." children of : $parentName\n";
				$this->initiateTaxonStorage($child, &$subParent, $level); 
			} else {
				//echo "New child : $child, level 1\n";
				$newChildLevel = $level + 1;
				$newChild = new Taxon();
				$newChild->setName($child);
				$newChild->setLevel($newChildLevel);
				$subParent->addChild($newChild);
			}

			if($level === 0) {
				//echo "Added ".$subParent->getName()." as a known taxon\n";
				$this->addTaxon($subParent);
			}
		}
	}

	/**
	 * Search recursively threw the Taxon tree stored in $this->taxonStorage for a taxon with name === $taxonName.
	 * If found, returns the Taxon object.
	 * If not, returns false
	 *
	 * @param string taxonName The taxon name to search
	 */
	public function retrieveTaxonFromName($taxonName, $taxonStorage = null, $level = 0) 
	{
		if(is_null($taxonStorage)) {
			//echo "\n\n----------------------\nlooking for $taxonName...\n";
			$taxonStorage = $this->getTaxonStorage();
		}
		$taxonStorage->rewind();

		while($taxonStorage->valid()) {
			$taxon = $taxonStorage->current();
			//echo $taxon->getName()." at level ".$taxon->getLevel()." - \n";
			if ($taxon->getName() === $taxonName) {
				//echo "FOUND\n";
				return $taxon;
			}
			if ($taxon->hasChildren()) {
				//echo $taxon->getName() ." has ".$taxon->getChildrenNumber()." children \n";
				$levelUp = $level + 1;
				$deepSearch = $this->retrieveTaxonFromName($taxonName, $taxon->getChildren(), $levelUp);
				if (is_a($deepSearch, "Hydra\Taxon")) {
					return $deepSearch;
				}
			}
			$taxonStorage->next();
		}

		if ($level == 0) {
			//echo "NOT FOUND\n";
			return false;
		}
	}

	public function hydrateTaxa() {

	}

	public function createDirectoryStruct() {

	}

	public function writeToDisc() {

	}

}
