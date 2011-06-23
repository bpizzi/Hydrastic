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

use Hydrastic\Taxonomy;
use Hydrastic\ArrayMerger;

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
	protected $content;
	protected $metadatas = array();
	protected $taxonomy = array();
	protected $output = null;
	protected $taxonStorage;
	protected $ignore = false;

	/**
	 * Constructs the Post object
	 *
	 * @param array $dic The Application's Dependency Injection Container
	 * @param OutputInterface $output Where to log actions
	 */
	public function __construct($dic)
	{
		$this->dic = $dic;
		if (isset($dic["output"])) {
			$this->output = $dic['output'];
		}
		$this->setTaxonStorage(new SplObjectStorage());
	}

	public function __toString()
	{
		$ret = '';
		if ($this->hasMetadata('title')) {
			$ret = $this->getMetadata('title');
		} elseif ($this->getFilename() != "") {
			$ret = $this->getFilename();
		}

		return $ret;
	}

	public function setTaxonStorage($taxonStorage)
	{
		$this->taxonStorage = $taxonStorage;
	}

	public function getTaxonStorage()
	{
		return $this->taxonStorage;
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
	public function getFilepath()
	{
		return $this->filepath;
	}
	public function getFilename() 
	{
		return end(explode("/", $this->getFilepath()));
	}

	public function hasMetadata($key)
	{
		return array_key_exists($key, $this->metadatas["General"]);
	}
	public function getMetadata($key)
	{
		if (false === array_key_exists($key, $this->metadatas["General"])) {
			throw new \LogicException("You tried to access a metadata named $key which is not defined");
		}

		return $this->metadatas["General"][$key];
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

	public function getWwwFile() 
	{
		return $this->wwwFile;
	}
	public function getFinalWwwFile() 
	{
		return $this->finalWwwFilename;
	}

	public function setHtml($html)
	{
		$this->html = $html;
	}
	public function getHtml()
	{
		return $this->html;
	}
	public function setContent($content)
	{
		$this->content = $content;
	}
	public function getContent()
	{
		return $this->content;
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
		if (false === is_array($taxonomy)) {
			throw \LogicException('Post->setTaxonomy() only accept arrays, "'.gettype($taxonomy).'" given.');
		}
		$this->taxonomy = $taxonomy;
	}


	public function setFileArray($array)
	{
		if (false === is_array($array)) {
			throw new \LogicException('Post->setFileArray() only accept arrays, "'.gettype($array).'" given.');
		}
		$this->fileArray = $array;
	}

	public function setIgnored($bool) 
	{
		$this->ignore = $bool;
	}
	public function isIgnored()
	{
		return $this->ignore;
	}

	public function hasTaxon($taxon)
	{
		if (true === is_a($taxon, "Hydrastic\Taxon")) {
			return $this->getTaxonStorage()->contains($taxon) ? true : false;
		} 
		return false;
	}

	public function addTaxon($taxon)
	{
		if (false === is_a($taxon, "Hydrastic\Taxon")) {
			throw new \LogicException("addTaxon except a Hydrastic\Taxon object as a first argument");
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
		if (false === file_exists($file)) {
			throw new \LogicException("\$post->read() except a valid readable file as first parameter: $file isn't a valid file.");
		} 

		$this->setFilepath($file);
		$this->wwwFile = reset(explode('.', end(explode('/',$file))));

		if ('' === file_get_contents($file)) {
			$msg = " <info>".$this->getFilename()."</info> is a blank file, I will skip it.";
			$this->writeOutput($this->dic['conf']['command_prefix'].$msg);
			$this->dic['logger']['hydration']->addError(strip_tags($msg));
			$this->setIgnored(true);
			return $this;
		} 

		$this->writeOutput($this->dic['conf']['command_prefix'].' Processing file <comment>'.$this->wwwFile.' ('.$file.')</comment>');

		return $this;
	}

	/**
	 * Parse a file into an array where each value is a line
	 * Get read of CR and spaces for each lines
	 * And proceed to further actions
	 */
	public function clean()
	{
		if (true === $this->isIgnored()) {
			return $this;
		}

		$this->setFileArray(file($this->getFilepath()));
		if (false === is_array($this->fileArray)) {
			throw new \LogicException("Post->clean() needs a proper fileArray variable.");
		}
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
		if (true === $this->isIgnored()) {
			return $this;
		}

		// Get the metadatas
		$metaDatasStr =  implode(chr(13), array_slice($this->fileArray, 0, array_search("---", $this->fileArray)));                 

		if ("" === $metaDatasStr) {
			throw new \Exception("The file $this has no metadata");
		}

		// Parse the metadatas in a array, using defaults when necessary
		$this->setMetadatas($this->dic['yaml']['parser']->parse($metaDatasStr));

		if (false === is_array($this->metadatas['General'])) {
			throw new \Exception("Post->parseMetas() needs a proper 'General' metadata entry.");
		}
		array_walk($this->metadatas['General'], function(&$item, $key, $conf) {
			if ($item == "") {
				$item = $conf['metadata_defaults']['General'][$key];
			}
		}, $this->dic['conf']);

		if (false === $this->hasMetadata('title')) {
			$msg = "Please affect a metadata entry 'title' to text file".$this->getFilepath().". Post is ignored.";
			$this->dic['output']->writeln($this->dic['conf']['command_prefix'].' '.$msg);
			$this->dic['logger']['hydration']->addError(strip_tags($msg));
			$this->setIgnored(true);
			return $this;
		}

		if (isset($this->metadatas['Taxonomy']) && sizeof($this->metadatas['Taxonomy'])>0) {
			$this->setTaxonomy($this->metadatas['Taxonomy']);
		}

		//Setting name+ extension of the file to write
		if ($this->hasMetadata('slug') && $this->getMetadata('slug') != "") {
			$this->setSlug($this->getMetadata('slug'));
		} elseif ($this->hasMetadata('title')) {
			$this->setSlug($this->dic['util']['slugify']->slugify($this->getMetadata('title')));
		}

		if (isset($this->dic['conf']['file_extension']) && $this->dic['conf']['file_extension'] != "") {
			$this->finalWwwFilename = $this->getSlug() . '.' . $this->dic['conf']['file_extension'];
		} else {
			$this->finalWwwFilename = $this->getSlug() . '.' . $this->dic['conf']['www_file_extension'];
		}

		return $this;
	}

	/**
	 * Extract the content of $fileArray
	 * Eventually process the content threw a valid text processor
	 * Must be called after clean()
	 * And proceed to further actions
	 */
	public function parseContent()
	{
		if (true === $this->isIgnored()) {
			return $this;
		}

		$this->setContent(implode("\n", array_slice($this->fileArray, array_search("---", $this->fileArray) + 1, sizeof($this->fileArray))));  
		if ($this->hasMetadata('processor') && isset($this->dic['textprocessor'][$this->getMetadata('processor')])) {
			$this->content = $this->dic['textprocessor'][$this->getMetadata('processor')]->render($this->content);
		}

		return $this;
	}

	/**
	 * Hydrate a template with $content, respect $metaData and $dic['conf'] when needed
	 * Must be called after parseContent() and parseMetas()
	 * And proceed to further actions
	 */
	public function hydrate()
	{
		if (true === $this->isIgnored()) {
			return $this;
		}

		if (null != $this->output && $this->output->getVerbosity()==2) {
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
			$this->writeOutput($this->dic['conf']['command_prefix'].'   ... and a content of <comment>'.strlen($this->getContent()).'</comment> char(s) (<comment>'.str_word_count($this->getContent()).'</comment> word(s))');
		}

		//Defining the template to use, same rules applies as for the theme
		//TODO: check that the template file exists
		if (isset($this->metadatas['General']['post_template'])) {
			$template = $this->metadatas['General']['post_template'];
		} elseif (isset($this->dic['conf']['metadata_defaults']['General']['post_template'])) {
			$template = $this->dic['conf']['metadata_defaults']['General']['post_template'];
		} else {
			$template = "post.twig";
		}

		$this->setHtml(
			$this->dic['twig']['parser']->render(
				$template,
				array_merge(
					$this->metadatas['General'],
					array("content" => $this->getContent())
				)
			)
		); 

		return $this;
	}

	public function attachToTaxonomy() 
	{
		if (true === $this->isIgnored()) {
			return $this;
		}

		if (false === isset($this->dic['taxonomy']) || false === $this->dic['taxonomy']->isInitiated()) {
			throw new \LogicException("You tried to attach a post to the general Taxonomy before having initiated it");
		}

		array_walk_recursive($this->getTaxonomy(), function($value, $key, $args) {
			$taxon = $args['taxonomy']->retrieveTaxonFromName($value);
			if (false === $taxon) {
				$msg = "You affected the post '".$args['postTitle']."' to a Taxon named '$value' which is not declared in your configuration. Post is ignored.";
				$this->dic['output']->writeln($this->dic['conf']['command_prefix'].' '.$msg);
				$this->dic['logger']['hydration']->addError(strip_tags($msg));
				$this->setIgnored(true);
				return $this;
			}
			$args['postObject']->addTaxon($taxon);
			$taxon->addPost($args['postObject']);
		}, array(
			'taxonomy'     => $this->dic['taxonomy'],
			'postTitle'     => $this->metadatas['General']['title'],
			'postObject'    => $this, )
		);

	}

	/**
	 * Write the html into the final www file
	 * Must be called after hydrate()
	 * Do check that the file written is what it should look like, then return true
	 * If something wrong written to disc, return false
	 *
	 * @params string $path The full path to the directory in which this post will be put
	 */
	public function writeToFile($path) 
	{
		if (true === $this->isIgnored()) {
			return $this;
		}

		$fileToWrite = $path.'/'.$this->finalWwwFilename;

		// Write the html
		file_put_contents($fileToWrite, $this->getHtml());
		if (file_exists($fileToWrite) && file_get_contents($fileToWrite) == $this->getHtml()) {
			$this->writeOutput($this->dic['conf']['command_prefix'].' Successfully hydrated <comment>'.str_replace(__DIR__.'/', '', $this->finalWwwFilename).'</comment>');
			//echo "\n written $fileToWrite\n";
			return true;
		}
		$this->writeOutput($this->dic['conf']['command_prefix'].' <error>ERROR</error> Failed hydrating <comment>'.str_replace(__DIR__.'/', '', $this->finalWwwFilename).'</comment>');
		return false;
	}

}
