<?php

/**
 * CsvSimple Trait
 *
 * @package Plugin.CsvSimple.Lib
 */
trait CsvSimpleTrait
{

    /**
     * convert variables encoding
     *
     * Instead of mb_convert_variables for following reason
     * Warning: mb_convert_variables(): Cannot handle recursive references in Command line code on line 1
     * @param mixed $value
     * @access protected
     */
    protected function _convert_variables(&$value)
    {
        if (is_string($value)) {
            $value = mb_convert_encoding($value, $this->toEncoding, $this->fromEncoding);
            return;
        }

        array_walk_recursive($value, function (&$str) {
            $str = mb_convert_encoding($str, $this->toEncoding, $this->fromEncoding);
        });
    }

}
