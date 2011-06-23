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

class Theme 
{

	protected $dic = array();
	protected $errors = array();
	protected $conf = array();
	protected $themeFolder;
	protected $validationDone = false;

	/**
	 * Constructs the Theme object
	 *
	 * @param array $dic The Application's Dependency Injection Container
	 */
	public function __construct($dic)
	{
		$this->dic = $dic;
	}

	public function __toString() 
	{
		return $this->conf['theme_name'];
	}

	public function setThemeFolder($f)
	{
		$this->themeFolder = $f;
	}

	public function getThemeFolder()
	{
		return $this->themeFolder;
	}

	public function addAndLogError($msg)
	{
		$this->errors[] = $msg;
		$this->dic['logger']['hydration']->addCritical('During theme validation: '.$msg);
	}

	public function getValidationErrors() 
	{
		return $this->errors;
	}

	public function hasErrors()
	{
		return count($this->getValidationErrors()) > 0 ? true : false;
	}

	public function readConfigFile($file)
	{
		$this->conf = $this->dic['yaml']['parser']->parse(file_get_contents($file));
	}

	public function validate() 
	{
		//Validating access to the theme folder
		$this->setThemeFolder($this->dic['working_directory'].'/'.$this->dic['conf']['tpl_dir'].'/'.$this->dic['conf']['theme_folder']);
		if (false === is_dir($this->getThemeFolder())) {
			$this->addAndLogError($this->getThemeFolder()." isn't a valid folder");
		}

		//Validating conf file
		$confFile = $this->getThemeFolder().'/theme-conf.yml';
		if (false === is_file($confFile) || false === is_readable($confFile)) {
			$this->addAndLogError('theme configuration file unexistant or unreadable');
		} else {
			//Validating conf keys where valid file are expected
			$this->readConfigFile($confFile);
			$keys = array('post_template', 'taxon_template', 'index_template');
			foreach ($keys as $k) {
				if (false === isset($this->conf[$k])) {
					$this->addAndLogError("key $k undefined");
				} elseif (false === is_file($this->getThemeFolder().'/'.$this->getConfKey($k)) || false === is_readable($this->getThemeFolder().'/'.$this->getConfKey($k))) {
					$this->addAndLogError($k.' file unexistant or unreadable : '.$this->getConfKey($k));
				}
			}
		}
		$this->validationDone = true;
	}

	public function isValid() 
	{
		if (false === $this->validationDone) {
			$this->validate();
		}
		return $this->hasErrors() ? false : true;
	}

	public function getPathTo($k)
	{
		return $this->getThemeFolder().'/'.$this->getConfKey[$k];
	}

	public function getConfKey($k)
	{
		if (false === isset($this->conf[$k])) {
			throw new \LogicException("You tried to access an undefined key in the theme config file: $k");
		}

		return $this->conf[$k];
	}

}

