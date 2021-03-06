<?php
App::uses('Controller', 'Controller');
App::uses('CsvImportComponent', 'CsvSimple.Controller/Component');

class CsvImportTestModel extends CakeTestModel
{
    public $useDbConfig = 'test';
}

class CsvImportTestController extends Controller
{

    public $uses = [
        'CsvImportTestModel',
    ];

    public $components = [
        'CsvSimple.CsvImport',
    ];
}

class CsvImportTestMimeTypeController extends Controller
{

    public $uses = [
        'CsvImportTestModel',
    ];

    public $components = [
        'CsvSimple.CsvImport' => [
            'types' => ['application/vnd.ms-excel', 'application/excel'],
        ],
    ];
}

class CsvImportComponentTest extends CakeTestCase
{
    public $fixtures = [
        'plugin.CsvSimple.csv_import_test_model',
    ];

    public function setUp()
    {
        parent::setUp();

        $this->dir = dirname(dirname(dirname(__DIR__))) . DS . 'files' . DS;

        $request = new CakeRequest(null, false);
        $this->Controller = new CsvImportTestController($request, $this->getMock('CakeResponse'));
        $this->Controller->constructClasses();
        $this->Model = $this->Controller->CsvImportTestModel;
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        unset($this->Controller, $this->Model);
    }

    /**
     * @test
     */
    public function createGeneratorWithSimpleCsv()
    {
        $path = $this->dir . 'simple.csv';
        $files = $this->makeFiles($path);
        $g = $this->Controller->CsvImport->createGenerator($files);
        $expected = [
            ['1', '2', '3'],
            ['4', '5', '6'],
            ['7', '8', '9'],
        ];
        foreach ($g as $idx => $row) {
            $this->assertEquals($expected[$idx], $row);
        }
    }

    /**
     * @test
     */
    public function createGeneratorWithPipeDelimiterCsv()
    {
        $path = $this->dir . 'simple_pipe_delimiter.csv';
        $files = $this->makeFiles($path);
        $this->Controller->CsvImport->delimiter = '|';
        $g = $this->Controller->CsvImport->createGenerator($files);
        $expected = [
            ['1', '2', '3'],
            ['4', '5', '6'],
            ['7', '8', '9'],
        ];
        foreach ($g as $idx => $row) {
            $this->assertEquals($expected[$idx], $row);
        }
    }

    /**
     * @test
     */
    public function createGeneratorWithSimpleCsvWithHeader()
    {
        $path = $this->dir . 'simple_with_header.csv';
        $files = $this->makeFiles($path);
        $this->Controller->CsvImport->headerRows = 1;
        $g = $this->Controller->CsvImport->createGenerator($files);
        $expected = [
            ['1', '2', '3'],
            ['4', '5', '6'],
            ['7', '8', '9'],
        ];
        foreach ($g as $idx => $row) {
            $this->assertEquals($expected[$idx], $row);
        }
    }

    /**
     * @test
     */
    public function createGeneratorWithSimpleCsvWithThreeRowsHeader()
    {
        $path = $this->dir . 'simple_with_header3.csv';
        $files = $this->makeFiles($path);
        $this->Controller->CsvImport->headerRows = 3;
        $g = $this->Controller->CsvImport->createGenerator($files);
        $expected = [
            ['1', '2', '3'],
            ['4', '5', '6'],
            ['7', '8', '9'],
        ];
        foreach ($g as $idx => $row) {
            $this->assertEquals($expected[$idx], $row);
        }
    }

    /**
     * @test
     */
    public function createGeneratorWithCP932Csv()
    {
        $path = $this->dir . 'cp932.csv';
        $files = $this->makeFiles($path);
        $g = $this->Controller->CsvImport->createGenerator($files);

        $expected = [
            ['株式', '会社', 'グーグル'],
            ['です～', 'ます～', 'モス～'],
            ['ソ', 'ポ', 'ー－'],
        ];
        foreach ($g as $idx => $row) {
            $this->assertEquals($expected[$idx], $row);
        }
    }

    /**
     * @test
     * @expectedException RuntimeException
     * @expectedExceptionMessage Could not read file.
     */
    public function createGeneratorThrowException()
    {
        $path = $this->dir . 'not_found.csv';
        $files = $this->makeFiles($path);
        $g = $this->Controller->CsvImport->createGenerator($files);
        foreach ($g as $idx => $row) {
        }
    }

    /**
     * @test
     */
    public function saveCsvData()
    {
        $this->assertEquals(0, $this->Model->find('count'));

        $path = $this->dir . 'simple.csv';
        $files = $this->makeFiles($path);
        $g = $this->Controller->CsvImport->createGenerator($files);
        foreach ($g as $idx => $row) {
            $data = array_combine(['foo', 'bar', 'baz'], $row);
            $this->Model->create();
            $this->Model->save($data);
        }

        $this->assertEquals(3, $this->Model->find('count'));
    }

    /**
     * @test
     */
    public function saveInvalidCsvData()
    {
        $this->assertEquals(0, $this->Model->find('count'));

        try {
            $path = $this->dir . 'invalid.csv';
            $files = $this->makeFiles($path);
            $g = $this->Controller->CsvImport->createGenerator($files);

            $ds = $this->Model->getDataSource();
            $ds->begin();
            foreach ($g as $idx => $row) {
                $keys = ['foo', 'bar', 'baz'];
                if (count($keys) != count($row)) {
                    throw new Exception('invalid row. line:' . ($idx + 1));
                }
                $data = array_combine($keys, $row);
                $this->Model->create();
                $this->Model->save($data);
            }
            $ds->commit();

        } catch (Exception $e) {
            $ds->rollback();
            $this->assertEquals('invalid row. line:2', $e->getMessage());
        }

        $this->assertEquals(0, $this->Model->find('count'));
    }

    /**
     * @test
     * @expectedException RuntimeException
     * @expectedExceptionMessage No file was uploaded
     */
    public function uploadNoFileException()
    {
        $path = $this->dir . 'not_found.csv';
        $files = $this->makeFiles($path, UPLOAD_ERR_NO_FILE);
        $g = $this->Controller->CsvImport->createGenerator($files);
        foreach ($g as $idx => $row) {
        }
    }

    /**
     * @test
     * @expectedException RuntimeException
     * @expectedExceptionMessage image/png
     */
    public function throwExceptionWhenPngfileUploaded()
    {
        $path = $this->dir . '10x10.png';
        $files = $this->makeFiles($path);
        $g = $this->Controller->CsvImport->createGenerator($files);
        foreach ($g as $idx => $row) {
        }
    }

    /**
     * @test
     */
    public function addAllowedMimeType()
    {
        $request = new CakeRequest(null, false);
        $this->Controller = new CsvImportTestMimeTypeController($request, $this->getMock('CakeResponse'));
        $this->Controller->constructClasses();

        $types = $this->Controller->CsvImport->types;
        $this->assertEquals(7, count($types));
        $this->assertTrue(in_array('application/vnd.ms-excel', $types));
        $this->assertTrue(in_array('application/excel', $types));
    }

    protected function makeFiles($path, $error = UPLOAD_ERR_OK)
    {
        $type = 'text/plain';
        if (is_file($path)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $type = finfo_file($finfo, $path);
        }

        return [
            'tmp_name' => $path,
            'type' => $type,
            'error' => $error,
        ];
    }
}
