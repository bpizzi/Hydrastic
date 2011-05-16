<?php

namespace Hydra;

use Symfony\Component\Console\Output\OutputInterface;

use Hydra\ArrayMerger;

/**
 * @author Baptiste Pizzighini <baptiste@bpizzi.fr>
 */
class Post 
{

	protected $dic = array();

	protected $filepath = array();

	protected $rawData;

	protected $fileArray = array();

	protected $wwwFile;

	protected $finalWwwFile;

	protected $metaDatas = array();

	protected $taxonomy = array();

	/**
	 * Constructs the Post object
	 *
 	 * @param array $dic The Application's Dependency Injection Container
	 * @param OutputInterface $output Where to log actions
	 */
	public function __construct($dic, OutputInterface $output)
	{
		$this->dic = $dic;
		$this->output = $output;
	}

	/**
	 * Returns the taxonomy of the post
	 */
	public function getTaxonomy() 
	{
		return $this->taxonomy;
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
			$this->output->writeln($this->dic['conf']['command_prefix'].' Processing file <comment>'.$this->wwwFile.' ('.$file.')</comment>');
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
			$item = trim($item);
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
		$metaDatasStr =  implode("\n", array_slice($this->fileArray, 0, array_search("---", $this->fileArray)));                 

		// Parse the metadatas in a array, using defaults when necessary
		$this->metaDatas = $this->dic['yaml']['parser']->parse($metaDatasStr);
		array_walk($this->metaDatas, function(&$item, $key, $hydraConf) {
			if ($item == "") {
				$item = $hydraConf['metadata_defaults'][$key];
			}
		}, $this->dic['conf']);

		//check if there is some taxonomy in metadata and add it to the taxonomy of the post if necessary
		//TODO
		//if (isset($metaDatas["categories"]) && sizeof($metaDatas["categories"]>0)) {
		//$categories = ArrayMerger::mergeUniqueValues($categories, $metaDatas["categories"]);
		//}
		//if (isset($metaDatas["tags"]) && sizeof($metaDatas["tags"]>0)) {
		//$tags = ArrayMerger::mergeUniqueValues($tags, $metaDatas["tags"]);
		//}

		//Setting name+ extension of the file to write
		if (isset($this->metaDatas['filename']) && $this->metaDatas['filename'] != "") {
			$this->finalWwwFilename = $this->metaDatas['filename'];
		}
		if (isset($this->metaDatas['file_extension']) && $this->metaDatas['file_extension'] != "") {
			$this->finalWwwFilename .= '.'.$this->metaDatas['file_extension'];
		} else {
			$this->finalWwwFilename .= '.'.$this->dic['conf']['www_file_extension'];
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
			foreach ($this->metaDatas as $k => $v) {
				if (is_array($v)) {
					$options = '{';
					foreach ($v as $v2) {
						$options .= $v2.', ';
					}
					$options = substr($options, 0, strlen($options)-2).'}';
					$this->output->writeln($this->dic['conf']['command_prefix'].'   ... with metadata <comment>'.$k.'</comment> => <comment>'.$options.'</comment>');
				} else {
					$this->output->writeln($this->dic['conf']['command_prefix'].'   ... with metadata <comment>'.$k.'</comment> => <comment>'.$v.'</comment>');
				}
			}
			$this->output->writeln($this->dic['conf']['command_prefix'].'   ... and a content of <comment>'.strlen($this->content).'</comment> char(s) (<comment>'.str_word_count($this->content).'</comment> word(s))');
		}
		$this->html = $this->dic['twig']['parser']->render(
			$this->metaDatas['template'].'.twig',
			array_merge($this->metaDatas, array("content" => $this->content))
		); 

		return $this;
	}

	/**
	 * Finally write the html into the final www file
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
			$this->output->writeln($this->dic['conf']['command_prefix'].' Successfully hydrated <comment>'.str_replace(__DIR__.'/', '', $this->finalWwwFilename).'</comment>');
			return true;
		}
		$this->output->writeln($this->dic['conf']['command_prefix'].' <error>ERROR</error> Failed hydrating <comment>'.str_replace(__DIR__.'/', '', $this->finalWwwFilename).'</comment>');
		return false;
	}

}
