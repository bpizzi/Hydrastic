<?php
/**
 * This file is part of the Hydra package.
 *
 * (c) Baptiste Pizzighini <baptiste@bpizzi.fr> 
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */


use Hydra\Util\ArrayMerger;

class ArrayMergerTest extends PHPUnit_Framework_TestCase
{

	public function testMergeUniqueKeysRecursive()
	{
        $am = new ArrayMerger();

		//Simples cases
		$a1 = array( 1 => "one", 2 => "two",);
		$a2 = array( 3 => "three", 4 => "four",);
		$r = array( 1 => "one", 2 => "two", 3 => "three", 4 => "four",);
		$this->assertEquals($am->mergeUniqueKeysRecursive($a1,$a2), $r);

		$a1 = array( 1 => "one", 2 => "two",);
		$a2 = array( 2 => "two", 3 => "three", 4 => "four",);
		$r = array( 1 => "one", 2 => "two", 3 => "three", 4 => "four",);
		$this->assertEquals($am->mergeUniqueKeysRecursive($a1,$a2), $r);

		$a1 = array( 1 => "one");
		$a2 = array( 2 => "two", 3 => "three", 4 => "four",);
		$r = array( 1 => "one", 2 => "two", 3 => "three", 4 => "four",);
		$this->assertEquals($am->mergeUniqueKeysRecursive($a1,$a2), $r);

		$a1 = array( 1 => "one", 2 => "two", 3 => "three", 4 => "four",);
		$a2 = array( 2 => "two", 3 => "three", 4 => "four",);
		$r = array( 1 => "one", 2 => "two", 3 => "three", 4 => "four",);
		$this->assertEquals($am->mergeUniqueKeysRecursive($a1,$a2), $r);

		//two level array
		$a1 = array( 1 => "one", 2 => "two", 3 => "three", 4 => array( 41 => "four-one", 42 => "four-two"),);
		$a2 = array( 2 => "two", 3 => "three", 4 => "four",);
		$r = array( 1 => "one", 2 => "two", 3 => "three", 4 => "four",);
		$this->assertEquals($am->mergeUniqueKeysRecursive($a1,$a2), $r);

		$a1 = array( "2" => "two", "3" => "three", "4" => array(),);
		$a2 = array( "1" => "one", "2" => "two", "3" => "three", "4" => array( "41" => "four-one", "42" => "four-two"),);
		$r = array( "1" => "one", "2" => "two", "3" => "three", "4" => array( "41" => "four-one", "42" => "four-two"),);
		$this->assertEquals($am->mergeUniqueKeysRecursive($a1,$a2), $r);

		$a1 = array( "2" => "two", "3" => "three", "4" => array("43" => "four-three"),);
		$a2 = array( "1" => "one", "2" => "two", "3" => "three", "4" => array( "41" => "four-one", "42" => "four-two"),);
		$r = array( "1" => "one", "2" => "two", "3" => "three", "4" => array( "43" =>  "four-three", "41" => "four-one", "42" => "four-two"),);
		$this->assertEquals($am->mergeUniqueKeysRecursive($a1,$a2), $r);

		//Deep arrays
		$a1 = array( 
			"2" => "two",
			"3" => "three",
			"4" => array(
				"43" => "four-three",
				"44" => array(
					"441" => "four-four-one",
					"443" => "four-four-three",
				),
			),
		);
		$a2 = array( 
			"1" => "one",
			"2" => "two",
			"3" => "three",
			"4" => array( 
				"41" => "four-one",
				"42" => "four-two",
				"43" => "four-three",
				"44" => array(
					"441" => "four-four-one",
					"442" => "four-four-two",
					"444" => array(
						"4441" => "four-four-four-one",
					),
				),
			),
		);
		$r = array( 
			"1" => "one",
			"2" => "two",
			"3" => "three",
			"4" => array(
				"43" =>  "four-three",
				"41" => "four-one",
				"42" => "four-two",
				"44" => array(
					"441" => "four-four-one",
					"442" => "four-four-two",
					"443" => "four-four-three",
					"444" => array(
						"4441" => "four-four-four-one",
					),
				),
			),
		);
		$this->assertEquals($am->mergeUniqueKeysRecursive($a1,$a2), $r);
	}

	public function testMergeUniqueValues()
	{

        $am = new ArrayMerger();

		$a1 = array(1, 2,);
		$a2 = array(3, 4,);
		$r = array(1, 2, 3, 4,);
		$this->assertEquals($am->mergeUniqueValues($a1,$a2), $r);

		$a1 = array();
		$a2 = array(3, 4,);
		$r = array( 3, 4,);
		$this->assertEquals($am->mergeUniqueValues($a1,$a2), $r);

		$a1 = array(1, 2);
		$a2 = array();
		$r = array(1, 2,);
		$this->assertEquals($am->mergeUniqueValues($a1,$a2), $r);

		$a1 = array(1, 2);
		$a2 = array("Banana");
		$r = array(1, 2, "Banana");
		$this->assertEquals($am->mergeUniqueValues($a1,$a2), $r);

		$a1 = array(1, 2);
		$a2 = array("Banana");
		$r = array(1, 2, "Banana");
		$this->assertEquals($am->mergeUniqueValues($a1,$a2), $r);

	}

}
