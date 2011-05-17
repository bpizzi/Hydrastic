<?php

namespace Hydra;

use Symfony\Component\Console\Output\OutputInterface;

use Hydra\Taxonomy;
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
	 * Set the taxonomy of the post from taxonomy field found in metadatas
	 *
	 * @param array $taxonomy An array of taxonomy [two-levels array('taxon1' => array('class1', 'class2', 'class3'), 'taxon2' => array(...), ...)]
	 */
	public function setTaxonomy($taxonomy) {
		if(is_array($taxonomy)) {
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
		$this->metaDatas = $this->dic['yaml']['parser']->parse($metaDatasStr);
		array_walk($this->metaDatas['General'], function(&$item, $key, $hydraConf) {
			if ($item == "") {
				$item = $hydraConf['metadata_defaults']['General'][$key];
			}
		}, $this->dic['conf']);

		if (isset($this->metaDatas['Taxonomy']) && sizeof($this->metaDatas['Taxonomy'])>0) {
        	$this->setTaxonomy($this->metaDatas['Taxonomy']);
		}

		//Setting name+ extension of the file to write
		if (isset($this->metaDatas['General']['filename']) && $this->metaDatas['General']['filename'] != "") {
			$this->finalWwwFilename = $this->metaDatas['General']['filename'];
		}
		if (isset($this->metaDatas['General']['file_extension']) && $this->metaDatas['General']['file_extension'] != "") {
			$this->finalWwwFilename .= '.'.$this->metaDatas['General']['file_extension'];
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
			foreach ($this->metaDatas['General'] as $k => $v) {
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
			$this->metaDatas['General']['template'].'.twig',
			array_merge($this->metaDatas['General'], array("content" => $this->content))
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
