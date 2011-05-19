<?php

namespace Hydra;

use Symfony\Component\Console\Output\OutputInterface;

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

	protected $taxa = array(); //Hold all the taxa with their children

	protected $taxons = array(); //Hold taxon objects
	protected $posts = array(); //Hold post objects

	public function getTaxons()
	{

	}

	public function getTaxon($taxonSlug) 
	{

	}

	public function getPosts()
	{

	}

	public function getPost($postSlug)
	{

	}

	public function getTaxa()
	{
		return $this->taxa;
	}

	public function setTaxa($taxa) 
	{
		$this->taxa = $taxa;
	}


	/**
	 * Constructs the Taxonomy object
	 *
	 * @param array $dic The Application's Dependency Injection Container
	 * @param OutputInterface $output Where to log actions
	 */
	public function __construct($dic)
	{
		$this->dic = $dic;

		$this->initiateTaxonomy();
	}

	public function initiateTaxonomy() {
		
		$this->setTaxa($this->dic['conf']['Taxonomy']);
	}

	public function addPost($post) {

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
