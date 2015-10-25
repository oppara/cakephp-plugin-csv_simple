<?php
App::uses('Controller', 'Controller');
App::uses('CsvExportComponent', 'CsvSimple.Controller/Component');

class CsvExportTestComponent extends CsvExportComponent
{
    public $filename;

    protected function _printHeaders($filename)
    {
        $this->filename = $filename;
    }
}

class CsvExportTestController extends Controller
{
    public $uses = false;

    public $components = [
        'CsvExportTest',
    ];
}

class CsvExportComponentTest extends CakeTestCase
{
    protected $headers = [
        'foo', 'bar', 'baz',
    ];

    protected $data = [
        ['1', '2', '3'],
        ['4', '5', '6'],
        ['7', '8', '9'],
    ];
    protected $expected = "1,2,3\n4,5,6\n7,8,9\n";

    protected $dataCP932 = [
        ['株式', '会社', 'グーグル'],
        ['です～', 'ます～', 'モス～'],
        ['ソ', 'ポ', 'ー－'],
    ];
    protected $expectedCP932 = "株式,会社,グーグル\nです～,ます～,モス～\n\"ソ\",ポ,ー－\n";

    protected $dataAssoc = [
        [ 'User' => ['id' => 1, 'name' => "foo 'bar"] ],
        [ 'User' => ['id' => 2, 'name' => 'hoge "moge'] ],
        [ 'User' => ['id' => 3, 'name' => 'baz,baz'] ],
        [ 'User' => ['id' => 4, 'name' => "aaa\nbbb"] ],
    ];
    protected $expectedAssoc = [
        '1,"foo \'bar"',
        '2,"hoge ""moge"',
        '3,"baz,baz"',
        '4,"aaa',
        'bbb"',
    ];

    public function setUp()
    {
        parent::setUp();

        $this->expectedAssoc = implode("\n", $this->expectedAssoc) . "\n";

        $this->path = TMP . 'tests' . DS . 'out.csv';

        $request = new CakeRequest(null, false);
        $this->Controller = new CsvExportTestController($request, $this->getMock('CakeResponse'));
        $this->Controller->constructClasses();
        $this->Controller->CsvExportTest->startup($this->Controller);
        $this->Component = $this->Controller->CsvExportTest;
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        if (is_file($this->path)) {
            unlink($this->path);
        }

        unset($this->Controller, $this->Component);
    }

    /**
     * @test
     */
    public function exportStandartOut()
    {
        ob_start();
        $this->Component->export($this->data);
        $actual = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($this->expected, $actual);
    }

    /**
     * @test
     */
    public function exportStandartOutWithHeader()
    {
        $this->Component->setHeader($this->headers);

        ob_start();
        $this->Component->export($this->data);
        $actual = ob_get_contents();
        ob_end_clean();

        $expected = "foo,bar,baz\n" . $this->expected;
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function exportStandartOutWithPipeDelimiter()
    {
        $this->Component->delimiter = '|';

        ob_start();
        $this->Component->export($this->data);
        $actual = ob_get_contents();
        ob_end_clean();

        $expected = "1|2|3\n4|5|6\n7|8|9\n";
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function exportFile()
    {
        $this->Component->export($this->data, $this->path);

        $actual = file_get_contents($this->path);
        $this->assertEquals($this->expected, $actual);
    }

    /**
     * @test
     */
    public function exportFileCP932()
    {
        $this->Component->export($this->dataCP932, $this->path);

        $actual = file_get_contents($this->path);
        mb_convert_variables($this->Component->fromEncoding, $this->Component->toEncoding, $actual);
        $this->assertEquals($this->expectedCP932, $actual);
    }

    /**
     * @test
     */
    public function exportFileWithHeaderCP932()
    {
        $headers = ['番号', '名前', '住所'];
        $this->Component->setHeader($headers);
        $this->Component->export($this->dataCP932, $this->path);

        $expected = "番号,名前,住所\n" . $this->expectedCP932;
        $actual = file_get_contents($this->path);
        mb_convert_variables($this->Component->fromEncoding, $this->Component->toEncoding, $actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function exportFileWithAssocArray()
    {
        $this->Component->export($this->dataAssoc, $this->path);

        $actual = file_get_contents($this->path);
        $this->assertEquals($this->expectedAssoc, $actual);
    }

    /**
     * @test
     */
    public function download()
    {
        ob_start();
        $this->Component->download($this->data);
        $actual = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($this->expected, $actual);
        $this->assertEquals('export.csv', $this->Component->filename);
    }

    /**
     * @test
     */
    public function downloadCP932()
    {
        ob_start();
        $this->Component->download($this->dataCP932);
        $actual = ob_get_contents();
        ob_end_clean();

        mb_convert_variables($this->Component->fromEncoding, $this->Component->toEncoding, $actual);
        $this->assertEquals($this->expectedCP932, $actual);
        $this->assertEquals('export.csv', $this->Component->filename);
    }

    /**
     * @test
     */
    public function downloadAssoc()
    {
        ob_start();
        $this->Component->download($this->dataAssoc);
        $actual = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($this->expectedAssoc, $actual);
        $this->assertEquals('export.csv', $this->Component->filename);
    }

    /**
     * @test
     */
    public function downloadWithFilename()
    {
        $filename = 'foo.csv';
        ob_start();
        $this->Component->download($this->data, $filename);
        $actual = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($this->expected, $actual);
        $this->assertEquals($filename, $this->Component->filename);
    }

}
