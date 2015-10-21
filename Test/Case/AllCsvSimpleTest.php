<?php
/**
 * All app tests
 */
class AllCsvSimpleTest extends PHPUnit_Framework_TestSuite
{

    /**
     * Suite define the tests for this suite
     *
     * @return CakeTestSuite
     */
    public static function suite()
    {
        $suite = new CakeTestSuite('All CsvSimple tests');
        $path =  dirname(__FILE__) . DS;
        $suite->addTestDirectoryRecursive($path);
        return $suite;
    }
}

