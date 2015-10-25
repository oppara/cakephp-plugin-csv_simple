<?php
App::uses('Component', 'Controller');

/**
 * export csv data
 *
 * @uses Component
 * @package Plugin.CsvSimple.Controller.Component
 */
class CsvExportComponent extends Component
{

    /**
     * encoding that the string is being converted to.
     *
     * @var string
     * @access public
     */
    public $toEncoding = 'SJIS-win';

    /**
     * encoding that the string is being converted from.
     *
     * @var string
     * @access public
     */
    public $fromEncoding = 'UTF-8';

    /**
     * field delimiter.
     *
     * @var string
     * @access public
     */
    public $delimiter = ',';

    /**
     * field enclosure character.
     *
     * @var string
     * @access public
     */
    public $enclosure = '"';

    /**
     *  field escape character.
     *
     * @var string
     * @access public
     */
    public $escape = '\\';

    /**
     * csv header
     *
     * @var array
     * @access protected
     */
    protected $_headers = [];

    /**
     * output path
     *
     * @var string
     * @access protected
     */
    protected $_path = 'php://output';

    /**
     * download file name
     *
     * @var string
     * @access protected
     */
    protected $_filename = 'export.csv';

    /**
     * set Controller
     *
     * @param Controller $controller
     * @return void
     * @access public
     */
    public function startup (Controller $controller)
    {
        $this->Controller = $controller;
    }

    /**
     * set csv header
     *
     * @param array $headers header part of csv
     * @return void
     * @access public
     */
    public function setHeader($headers)
    {
        $this->_headers = $headers;
    }

    /**
     * export csv
     *
     * @param array $data two-dimensional array or Model::find() results
     * @param string $path path for save to
     * @return void
     * @access public
     */
    public function export($data, $path = '')
    {
        if ($path == '') {
            $path = $this->_path;
        }
        $file = new SplFileObject($path, 'w');
        $file->setCsvControl($this->delimiter, $this->enclosure, $this->escape);

        mb_convert_variables($this->toEncoding, $this->fromEncoding, $this->_headers, $data);

        if (!empty($this->_headers)) {
            $file->fputcsv($this->_headers);
        }

        $rows = $this->_makeExportRows($data);
        foreach ($rows as $row) {
            $file->fputcsv($row);
        }
    }

    /**
     * make data for export
     *
     * @param array $data two-dimensional array or Model::find() results
     * @return array two-dimensional array
     * @access protected
     */
    protected function _makeExportRows($data)
    {
        $tmp = $data[0];
        if ($tmp === array_values($tmp)) {
            return $data;
        }

        $model = array_keys($tmp)[0];
        $ret = [];
        foreach ($data as $row) {
            $ret[] = array_values($row[$model]);
        }

        return $ret;
    }


    /**
     * download csv file
     *
     * @param array $data two-dimensional array or Model::find() results
     * @param string $filename name of downloaded file
     * @return void
     * @access public
     */
    public function download($data, $filename = '')
    {
        $this->Controller->autoRender = false;

        if ($filename == '') {
            $filename = $this->_filename;
        }

        $this->_printHeaders($filename);

        $this->export($data);
    }

    /**
     * print request header
     *
     * @param string $filename name of downloaded file
     * @return void
     * @access protected
     */
    protected function _printHeaders($filename)
    {
        header('Content-Type: application/x-csv');
        header('Content-disposition: attachment;filename=' . $filename);
    }
}
