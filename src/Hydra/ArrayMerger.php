<?php

namespace Hydra;

/**
 * @author Baptiste Pizzighini <baptiste@bpizzi.fr>
 */
abstract class ArrayMerger 
{

	/**
	 * Merge recursively two arrays
	 * If a key exists in the two arrays,
	 * it will not be duplicated in the final array,
	 * and the value of the second arrays is used.
	 */
	static public function mergeUniqueValuesRecursive($arr1, $arr2)
	{
		foreach ($arr2 as $key => $value) {
			if(array_key_exists($key, $arr1) && is_array($value)) {
				$arr1[$key] = self::mergeUniqueValuesRecursive($arr1[$key], $arr2[$key]);
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
	static public function mergeUniqueValues($arr1, $arr2)
	{
		foreach ($arr2 as $key => $value) {
			if (false === in_array($value, $arr1)) {
				$arr1[] = $value;
			}
		}

		return $arr1;
	}
}

