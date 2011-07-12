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

/**
 * LazyLoading of assets (images/css/js)
 * The template calls the Asset Service which deliver a AssetManager instance.
 * This object implements ArrayAccess, and copy the assets to the www dir only when 
 * they are accessed: that should be lazyloading done right (unless someone tells me I'm wrong :p)
 **/
class AssetManager implements \ArrayAccess
{

	protected $dic = array();
	protected $array = array();

	public function __construct($dic)
	{
		$this->dic = $dic;
	}

	/**
	 * Extracting the filename from $filePath
	 */
	public function extractFilenameIn($filePath)
	{
		//If '/' is the first char of $filePath, get rid of it
		if (substr($filePath, 0, 1) === '/') {
			$filePath = substr($filePath, 1, strlen($filePath) - 1);
		}

		return end(explode('/', $filePath));
	}

	/**
	 * Extracting the relative path to the folder holding the file from $filePath
	 */
	public function extractFoldernameIn($filePath)
	{
		//If '/' is the first char of $filePath, get rid of it
		if (substr($filePath, 0, 1) === '/') {
			$filePath = substr($filePath, 1, strlen($filePath) - 1);
		}

		return is_int(strpos($filePath, '/')) ?	str_replace('/'.$this->extractFilenameIn($filePath), "", $filePath) : '';
	}

	/**
	 * Set the offset value only if the corresponding file exists
	 */
	public function offsetSet($offset, $value) {
		$this->array[$offset] = $value;
	}

	/**
	 * Don't try to set the offset before returning it existance status
	 * Even if the offset do exist on disk, we have to get/set if before, 
	 * in order to initialize it
	 */
	public function offsetExists($offset) {
		return isset($this->array[$offset]);
	}

	/**
	 * Unset the offset, don't touch the filesystem
	 */
	public function offsetUnset($offset) {
		unset($this->array[$offset]);
	}

	/**
	 * Try to set the offset before returning its value
	 */
	public function offsetGet($offset) {
		//echo "\nTrying to get: $offset\n";
		if(false === $this->offsetExists($offset)) {
			$filePath = $this->dic['theme']->getThemeFolder()."/".$offset;
			//echo "\nTesting file existance : $filePath\n";
			if (file_exists($filePath)) {
				$this->array[$offset] = $filePath;
				//echo "\nSetted array[\"$offset\"] = ".$this->array[$offset]."\n";
			} else {
				$this->dic['logger']['hydration']->addWarning("Your theme calls an unexisting asset ($filePath)");
				//echo "\nDidn't Setted array[\"$offset\"] because ".$filePath." isn't a valid file\n";
			}
		}

		return isset($this->array[$offset]) ? $this->array[$offset] : false;
	}

	/**
	 * Copies every setted offset to www_dir
	 */
	public function publish()
	{
		$assetDir = $this->dic['working_directory'].'/'.$this->dic['conf']['www_dir'].'/assets';
		//echo "\nMkdir $assetDir\n";
		mkdir($assetDir);
		foreach ($this->array as $relativeFilepath => $fullPath) {
			$destinationPath = $assetDir .'/'. $this->extractFilenameIn($relativeFilepath);
			//echo "copying $fullPath to $destinationPath\n";

			//TODO: gérer les sous-dir
			//TODO: gérer les post-process selon le type d'asset
			if (false === copy($fullPath, $destinationPath)) {
				//echo "Error when copying ".$fullPath."\n";
				$this->dic['logger']['hydration']->addError("<error>ERROR</error> when copying $fullPath to $destinationPath");
			} else {
				//echo "Copied $fullPath to $destinationPath\n";
				$this->dic['logger']['hydration']->addInfo("Asset hydration: copying $fullPath to $destinationPath");
			}
		}
	}

}

