<?php

/**
 * Converts multi dimensional array to single dimensional arrays
 * @param multi array[string][string] $input
 * @return array[string]
 * @verion php > 5.4
 */
    function cc_cartesian($input)
    {
        if ($input) {
            if ($layer = array_pop($input)) //If there is data in the array
                foreach (cc_cartesian($input) as $cartesian) //Recursively loop through the array
                    foreach ($layer as $value) { //Loop through the cartesian result
                        yield $cartesian + [count($cartesian) => $value]; //Return single item
                    }
        } else
            yield array(); //No input means empty array to avoid complicated if statements later
    }

?>
