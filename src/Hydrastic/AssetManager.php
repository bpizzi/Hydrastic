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
	 * Returns a Finder instance for $filepath
	 */
	public function getFinderFor($filePath)
	{
		//Find the file
		$fileName = $this->extractFilenameIn($filePath);
		$folderName = $this->extractFoldernameIn($filePath);
		$finder = $this->dic['finder']['find']
			->files()
			->name($fileName);
		if (false === is_null($folderName)) {
			$finder->in($this->dic['theme']->getThemeFolder());
		}

		return $finder;
	}

	/**
 	 * Return the first SplFileInfo object found by the finder for $filepath
	 */
	public function getSplFileInfoFor($filePath)
	{
		return reset(iterator_to_array($this->getFinderFor($filePath)));
	}

	/**
	 * Test if the specified file exists somewhere in the theme folder.
	 * Log the event if not.
	 */
	public function fileExists($filePath) 
	{
		if (iterator_count($this->getFinderFor($filePath)) === 0) {
			$this->dic['logger']['hydration']->addWarning("Your theme calls an unexisting asset ($offset)");
			return false;
		}

		return true;
	}

	/**
	 * Set the offset value only if the corresponding file exists
	 */
	public function offsetSet($offset, $value) {
		if ($this->fileExists($offset)) {
			$file = $this->getSplFileInfoFor($offset);
			$this->array[$offset] = $file->getRealPath();
		}
	}

	/**
	 * Try to set the offset before returning it existance status
	 */
	public function offsetExists($offset) {
		$this->offsetSet($offset);
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
		echo "Get $offset\n";
		$this->offsetSet($offset, null);
		return isset($this->array[$offset]) ? $this->array[$offset] : null;
	}

	/**
	 * Return the full filepath to the file corresponding to $asset
	 */
	public function getPathTo($asset) {
		return $path;
	}

	/**
	 * Copies every setted offset to www_dir
	 */
	public function publish()
	{
		$assetDir = $this->dic['conf']['www_dir'].'/assets';
		mkdir($assetDir);
		foreach ($this as $relativeFilepath => $fullPath) {
			$fileName = $this->extractFilenameIn($relativeFilepath);

			//TODO: gérer les sous-dir
			//TODO: gérer les post-process selon le type d'asset
			if (!copy($fullPath, $assetDir.'/'.$fileName)) {
				$this->dic['logger']['hydration']->addError("<error>ERROR</error> when copying $fileName to $assetDir");
			} else {
				$this->dic['logger']['hydration']->addInfo("Asset hydration: copying $fileName to $assetDir");
			}
		}
	}

}

