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

namespace Hydrastic\Util;

/**
 * @author Baptiste Pizzighini <baptiste@bpizzi.fr>
 */
class ArrayMerger 
{

	/**
	 * Merge recursively two arrays
	 * If a key exists in the two arrays,
	 * it will not be duplicated in the final array,
	 * and the value of the second arrays is used.
	 */
	public function mergeUniqueKeysRecursive($arr1, $arr2)
	{
		foreach ($arr2 as $key => $value) {
			if(array_key_exists($key, $arr1) && is_array($value)) {
				$arr1[$key] = $this->mergeUniqueKeysRecursive($arr1[$key], $arr2[$key]);
			} else {

				$arr1[$key] = $value;
			}
		}

		return $arr1;
	}

	/**
 	 * Make $arr1 hold unique values from both $arr1 and $arr2
	 *
	 */
	public function mergeUniqueValues($arr1, $arr2)
	{
		foreach ($arr2 as $key => $value) {
			if (false === in_array($value, $arr1)) {
				$arr1[] = $value;
			}
		}

		return $arr1;
	}

}
