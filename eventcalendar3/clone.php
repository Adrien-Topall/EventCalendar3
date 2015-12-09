<?php

/** Compatibility function for PHP4.
 *  Don't include this file if you are using PHP5. */
function new_clone($object)
{
  return $object;
}
