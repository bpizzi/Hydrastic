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

use Hydra\Taxonomy;
use Hydra\ArrayMerger;

use \SplObjectStorage;

class Post 
{

	protected $dic = array();

	protected $filepath = array();

	protected $rawData;

	protected $fileArray = array();

	protected $wwwFile;

	protected $finalWwwFile;

	protected $slug;

	protected $metadatas = array();

	protected $taxonomy = array();

	protected $output = null;

	protected $taxonStorage;

	/**
	 * Constructs the Post object
	 *
	 * @param array $dic The Application's Dependency Injection Container
	 * @param OutputInterface $output Where to log actions
	 */
	public function __construct($dic)
	{
		$this->dic = $dic;
		if(array_key_exists('output', $dic)) {
			$this->output = $dic['output'];
		}
		$this->setTaxonStorage(new SplObjectStorage());
	}

	public function __toString()
	{
		$ret = $this->getMetaDatas['General']['title'];
		return is_string($ret) ? $ret : "";
	}

	public function setTaxonStorage($taxonStorage)
	{
		$this->taxonStorage = $taxonStorage;
	}

	public function getTaxonStorage()
	{
		return $this->taxonStorage;
	}

	public function getFilepath()
	{
		return $this->filepath;
	}

	public function getMetadatas()
	{
		return $this->metadatas;
	}

	public function setMetadatas($metadatas)
	{
		$this->metadatas = $metadatas;
	}

	public function writeOutput($msg) {
		if (false === is_null($this->output)) {
			$this->output->writeln($msg);
		}
	}

	public function getSlug()
	{
		return $this->slug;
	}

	public function setSlug($slug)
	{
		$this->slug = $slug;
	}

	public function getFinalWwwFile() 
	{
		return $this->finalWwwFilename;
	}

	public function getTaxonomy() 
	{
		return $this->taxonomy;
	}

	/**
	 * Set the taxonomy of the post from taxonomy field found in metadatas
	 *
	 * @param array $taxonomy An array of taxonomy [two-levels array('taxon1' => array('class1', 'class2', 'class3'), 'taxon2' => array(...), ...)]
	 */
	public function setTaxonomy($taxonomy) {
		if (is_array($taxonomy)) {
			$this->taxonomy = $taxonomy;
		} else {
			throw \Exception();
		}
	}

	/**
	 * Set the filepath of the original content
	 *
	 * @param $filepath A full filepath to a text file
	 */
	public function setFilepath($filepath)
	{
		$this->filepath = $filepath;
	}

	public function hasTaxon($taxon)
	{
		if (true === is_a($taxon, "Hydra\Taxon")) {
			return $this->getTaxonStorage()->contains($taxon) ? true : false;
		} 
		return false;
	}

	public function addTaxon($taxon)
	{
		if (false === is_a($taxon, "Hydra\Taxon")) {
			throw new \Exception("addTaxon except a Hydra\Taxon object as a first argument");
		}
		$this->getTaxonStorage()->attach($taxon);
	}

	/**
	 * Fails if file is unreadable
	 * Or set filepath and wwwFile before proceeding to further action
	 *
	 * $param $file A full filepath to a text file
	 */
	public function read($file)
	{
		if (file_exists($file)) {
			$this->setFilepath($file);
			$this->wwwFile = reset(explode('.', end(explode('/',$file->getRealPath()))));
			$this->writeOutput($this->dic['conf']['command_prefix'].' Processing file <comment>'.$this->wwwFile.' ('.$file.')</comment>');
		} else {
			throw new \Exception();
		}

		return $this;
	}

	/**
	 * Parse a file into an array where each value is a line
	 * Get read of CR and spaces for each lines
	 * And proceed to further actions
	 */
	public function clean()
	{
		$this->fileArray = file($this->filepath);
		array_walk($this->fileArray, function(&$item, $key) {
			$item = str_replace("\n", '', $item);
		});                                     

		return $this;
	}

	/**
	 * Extract the metadatas of $fileArray
	 * Must be called after clean()
	 * And proceed to further actions
	 */
	public function parseMetas()
	{
		// Get the metadatas
		$metaDatasStr =  implode(chr(13), array_slice($this->fileArray, 0, array_search("---", $this->fileArray)));                 

		// Parse the metadatas in a array, using defaults when necessary
		$this->setMetadatas($this->dic['yaml']['parser']->parse($metaDatasStr));

		array_walk($this->metadatas['General'], function(&$item, $key, $hydraConf) {
			if ($item == "") {
				$item = $hydraConf['metadata_defaults']['General'][$key];
			}
		}, $this->dic['conf']);

		if (isset($this->metadatas['Taxonomy']) && sizeof($this->metadatas['Taxonomy'])>0) {
			$this->setTaxonomy($this->metadatas['Taxonomy']);
		}

		//Setting name+ extension of the file to write
		if (isset($this->metadatas['General']['slug']) && $this->metadatas['General']['slug'] != "") {
			$this->setSlug($this->metadatas['General']['slug']);
		} else {
			$this->setSlug($this->dic['util']['slugify']->slugify($this->metadatas['General']['title']));
		}

		if (isset($this->metadatas['General']['file_extension']) && $this->metadatas['General']['file_extension'] != "") {
			$this->finalWwwFilename = $this->getSlug() . '.' . $this->metadatas['General']['file_extension'];
		} else {
			$this->finalWwwFilename = $this->getSlug() . '.' . $this->dic['conf']['www_file_extension'];
		}

		return $this;
	}

	/**
	 * Extract the content of $fileArray
	 * Must be called after clean()
	 * And proceed to further actions
	 */
	public function parseContent()
	{
		// Get the content
		$this->content = implode("\n", array_slice($this->fileArray, array_search("---", $this->fileArray) + 1, sizeof($this->fileArray)));  

		return $this;
	}

	/**
	 * Hydrate a template with $content, respect $metaData and $dic['conf'] when needed
	 * Must be called after parseContent() and parseMetas()
	 * And proceed to further actions
	 */
	public function hydrate()
	{
		if ($this->output->getVerbosity()==2) {
			foreach ($this->metadatas['General'] as $k => $v) {
				if (is_array($v)) {
					$options = '{';
					foreach ($v as $v2) {
						$options .= $v2.', ';
					}
					$options = substr($options, 0, strlen($options)-2).'}';
					$this->writeOutput($this->dic['conf']['command_prefix'].'   ... with metadata <comment>'.$k.'</comment> => <comment>'.$options.'</comment>');
				} else {
					$this->writeOutput($this->dic['conf']['command_prefix'].'   ... with metadata <comment>'.$k.'</comment> => <comment>'.$v.'</comment>');
				}
			}
			$this->writeOutput($this->dic['conf']['command_prefix'].'   ... and a content of <comment>'.strlen($this->content).'</comment> char(s) (<comment>'.str_word_count($this->content).'</comment> word(s))');
		}
		$this->html = $this->dic['twig']['parser']->render(
			$this->metadatas['General']['template'].'.twig',
			array_merge($this->metadatas['General'], array("content" => $this->content))
		); 

		return $this;
	}

	public function attachToTaxonomy() 
	{
		if (false === isset($this->dic['taxonomy']) || false === $this->dic['taxonomy']->isInitiated()) {
			throw new \Exception("You tried to attach a post to the general Taxonomy before having initiated it");
		}

		if (false === is_string($this->metadatas['General']['title'])) {
			throw new \Exception("Please affect a metadata entry 'title' to text file".$this->getFilepath());
		}

		array_walk_recursive($this->getTaxonomy(), function($value, $key, $args) {
			$taxon = $args['taxonomy']->retrieveTaxonFromName($value);
			if(false === $taxon) {
				throw new \Exception("You affected the post named '".$args['postObject']->metadatas['General']['title']."' to a Taxon named '$value' which is not declared in your configuration");
			}
			$args['postObject']->addTaxon($taxon);
			$taxon->addPost($args['postObject']);
		}, array(
			'taxonomy'     => $this->dic['taxonomy'],
			'postObject'    => $this, )
		);

	}

	/**
	 * Write the html into the final www file
	 * Must be called after hydrate()
	 * Do check that the file written is what it should look like, then return true
	 * If something wrong written to disc, return false
	 */
	public function writeToFile() 
	{
		$fileToWrite = $this->dic['working_directory'].'/'.$this->dic['conf']['www_dir'].'/'.$this->finalWwwFilename;

		// Write the html
		file_put_contents($fileToWrite, $this->html);
		if (file_exists($fileToWrite) && file_get_contents($fileToWrite) == $this->html) {
			$this->writeOutput($this->dic['conf']['command_prefix'].' Successfully hydrated <comment>'.str_replace(__DIR__.'/', '', $this->finalWwwFilename).'</comment>');
			return true;
		}
		$this->writeOutput($this->dic['conf']['command_prefix'].' <error>ERROR</error> Failed hydrating <comment>'.str_replace(__DIR__.'/', '', $this->finalWwwFilename).'</comment>');
		return false;
	}

}
