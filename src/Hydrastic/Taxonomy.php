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

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\DialogHelper;

use \SplObjectStorage;

/**
 * Taxonomy is classification.
 *
 * A Taxon is a parent family with children ("A taxon", "Two taxa")
 * Each child can be a taxon with or without children.
 *
 * Taxonomy in Hydrastic accept unlimited levels of classification :
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

	protected $initiated;

	protected $indexHtml;


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
		$this->setInitiated(false);
		if (isset($dic["output"])) {
			$this->output = $dic['output'];
		}
	}

	public function isInitiated() 
	{
		return $this->initiated;
	}

	public function setInitiated($bool)
	{
		$this->initiated = $bool;
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

	public function getIndexHtml()
	{
		return $this->indexHtml;
	}
	public function setIndexHtml($html)
	{
		$this->indexHtml = $html;
	}
	public function writeOutput($msg) {
		if (false === is_null($this->output)) {
			$this->output->writeln($msg);
		}
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
			$subParent = new Taxon($this->dic);
			if (is_int($parentName)) {
				$subParent->setName($child);
			} else {
				$subParent->setName($parentName);
			}
			$subParent->setLevel($level); 
			if (null !== $parent) {
				$parent->addChild($subParent);
			}

			if (is_array($child) && sizeof($child) > 0) {
				//If the taxon as children
				//echo "  Going into deep init for ".sizeof($child)." children of : $parentName\n";
				$this->initiateTaxonStorage($child, &$subParent, $level); 
			} elseif ($child != $subParent) {
				$newChildLevel = $level + 1;
				//echo "New child for $subParent : $child, level $newChildLevel\n";
				$newChild = new Taxon($this->dic);
				$newChild->setName($child);
				$newChild->setLevel($newChildLevel);
				$subParent->addChild($newChild);
			}

			if ($level === 0) {
				//echo "Added ".$subParent->getName()." as a known taxon\n";
				$this->addTaxon($subParent);
			}
		}
		if ($level === 0) {
			$this->setInitiated(true);
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
		if (is_null($taxonStorage)) {
			//echo "\n\n----------------------\nlooking for $taxonName...\n";
			$taxonStorage = $this->getTaxonStorage();
		}
		$taxonStorage->rewind();

		while ($taxonStorage->valid()) {
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
				if (is_a($deepSearch, "Hydrastic\Taxon")) {
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

	public function cleanWwwDir($force = false) 
	{
		if (file_exists($this->dic['working_directory'].'/'.$this->dic['conf']['www_dir'].'/hydrastic-conf.yml')) {
			//Checking if hydrastic config file exist in www_dir: if yes, it could be that something is wrong
			//and that we're not in www_dir.
			$this->dic['output']->writeln($this->dic['conf']['command_prefix'].' Something look wrong: I can\'t access the www_dir, maybe you should ckeck your config file.');
			die();
		}
		$dir = $this->dic['working_directory'].'/'.$this->dic['conf']['www_dir'].'/*';

		switch (PHP_OS) {
		case "Linux":
		case "FreeBSD":
		case "NetBSD":
		case "OpenBSD":
		case "Unix":
		case "Darwin":
			$cmd = "rm -rf ";
			break;
		case "WINNT":
		case "WIN32":
		case "Windows":
			$cmd = "rmdir /s /q ";
			break;
		default:
			break;
		}

		$cmd .= $dir;

		$runCmd = false;
		if ($force) {
			$runCmd = true;
		} else {
			if (isset($this->dic['output'])) { //What if we run cleanWwwDir without $output initiated ? ==> gonna take care of it later ;)
				$this->dic['output']->writeln('---');
				$dialog = new DialogHelper();
				$this->dic['output']->writeln($this->dic['conf']['command_prefix'].' I need to clean the www directory, but I request your permission before deleting anything on your filesystem :');
				$question = $this->dic['conf']['command_prefix']." Do you allow me to run '<comment>".$cmd."</comment>' (recursively delete everything in that folder)  ? (<info>y/n</info>)";
				if (true === $dialog->askConfirmation($this->dic['output'], $question, false)) {
					$runCmd = true;
				} 
			}
		}

		if($runCmd) {
			system($cmd);
			$this->dic['output']->writeln($this->dic['conf']['command_prefix']." Ran <comment>$cmd</comment>.");
		} else {
			$this->dic['output']->writeln($this->dic['conf']['command_prefix']." Didn't cleaned www directory, you should see some warnings... ");
		}

		return $this;
	}

	/**
	 * Recursively goes threw $taxonStorage, loop over taxa, and :
	 *  - create the corresponding folder in www_dir (according to its slug)
	 *  - retrieve posts affected to the current taxon and write them in that folder
	 *  - write down a taxon index.html file
	 *  - calls itself on $taxon->children if its necessary
	 * 
	 * @param SplObjectStorage $taxonStorage If null, use $this->getTaxonStorage()
	 * @param string $path The path to the current taxon (folders hierarchie between working_directory and the taxon folder)
	 * @param int $level The current recursivity level
	 **/
		public function createDirectoryStruct($taxonStorage = null, $path = null, $level = 0) {

			$baseDir = $this->dic['working_directory'].'/'.$this->dic['conf']['www_dir'];
			if (null === $taxonStorage) {
				$taxonStorage = $this->getTaxonStorage();
			}

			if (null === $path || $level == 0) {
				$levelDir = $baseDir;
				$path = '';
			} else {
				$levelDir = $baseDir.$path;
			}

			$taxonStorage->rewind();

			while ($taxonStorage->valid()) {
				$taxon = $taxonStorage->current();

				//$this->initiateTaxonStorage(), in its current implementation, generates in known circumstances
				//some taxon "artifacts" (ie. transient level between parent and children that should not exist, with blank names).
				//Until correcting the init algo, we avoid parsing those levels by checking if it has a name.
				if ($taxon->getName() != '') { 

					$dir = $levelDir.'/'.$taxon->getSlug();

					//$tab = "";
					//for ($i = 0; $i < $level; $i++) {
					//$tab .= "  ";
					//}
					//echo "\n$tab-'".$taxon->getName()."' ===> path = $path   /   dir = $dir\n";

					mkdir($dir);

					if (isset($this->dic['output']) && $this->dic['output']->getVerbosity() === 2) {
						$this->dic['output']->writeln($this->dic['conf']['command_prefix']." Created <info>$dir</info>.");
					}

					//Looping over the post of the taxon and write them to disc in the taxon folder
					if ($taxon->hasPosts()) {
						$postStorage = $taxon->getPostStorage();
						$postStorage->rewind();

						while ($postStorage->valid()) {
							$post = $postStorage->current();
							$post->writeToFile($dir);
							if (isset($this->dic['output']) && $this->dic['output']->getVerbosity() === 2) {
								$this->dic['output']->writeln($this->dic['conf']['command_prefix']." Filled <info>$taxon</info> with <info>$post</info>.");
							}
							$postStorage->next();
						}
					}

					//Hydrating and writing the taxon index.html file
					$taxon->hydrateIndexFile()->writeIndexFile($dir);
					if (isset($this->dic['output']) && $this->dic['output']->getVerbosity() === 2) {
						$this->dic['output']->writeln($this->dic['conf']['command_prefix']." Writed index.html for <info>$taxon</info>.");
					}

				}

				//Preparing the new folder path for the next recursivity call, if needed
				if ($taxon->hasChildren() && $taxon->getName() != "") {
					$childPath = $path.'/'.$taxon->getSlug();
				} else {
					$childPath = $path;
				}

				//Call createDirectoryStruct on $taxon's children, if needed, 
				//with the path to the current taxon and the next recursivity leve
				if ($taxon->hasChildren()) {
					$childLevel = $level + 1;
					$this->createDirectoryStruct($taxon->getChildren(), $childPath, $childLevel);
				}

				$taxonStorage->next();
			}

		}

		/**
		 * Hydrate the html for the root index file
		 **/
		public function hydrateIndexFile()
		{
			$firstLevelTaxa = array();
			$this->getTaxonStorage()->rewind();
			while($this->getTaxonStorage()->valid()) {
				$taxon = $this->getTaxonStorage()->current();
                $firstLevelTaxa[] = $taxon;
				$this->getTaxonStorage()->next();
			}

			$this->setIndexHtml($this->dic['twig']['parser']->render(
				$this->dic['theme']->getConfKey('index_template'),
				array(
					"title"  => "Hydra Accueil",
					"taxa"   => $firstLevelTaxa,
					"assets" => $this->dic['asset']['manager'],
				)
			)); 

			return $this;

		}

		/**
		 * Write the hydrated html to the specified path
		 * @param string The path to the www directory
		 **/
		public function writeIndexFile($path) 
		{

			if (null === $this->getIndexHtml()) {
				throw new \Exception("You called \$taxon->writeIndexFile() without having called \$taxon->hydrateIndexFile() before");
			}

			$fileToWrite = $path.'/index.html'; //TODO: setting the index.html in the conf

			// Write the html
			file_put_contents($fileToWrite, $this->getIndexHtml());
			if (file_exists($fileToWrite) && file_get_contents($fileToWrite) == $this->getIndexHtml()) {
				$this->writeOutput($this->dic['conf']['command_prefix'].' Successfully hydrated root <comment>index.html</comment>');
				return true;
			}

			$this->writeOutput($this->dic['conf']['command_prefix'].' <error>ERROR</error> Failed hydrated <comment>index.html</comment> for taxon <comment>'.$this->getName().'</comment>');

			return false;

		}
}
