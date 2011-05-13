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
	static public function Merge($Arr1, $Arr2)
	{
		foreach($Arr2 as $key => $Value)
		{
			if(array_key_exists($key, $Arr1) && is_array($Value)) {
				$Arr1[$key] = self::Merge($Arr1[$key], $Arr2[$key]);
			} else {

				$Arr1[$key] = $Value;
			}
		}
		return $Arr1;
	}

}

