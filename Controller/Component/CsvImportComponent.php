<?php
App::uses('Component', 'Controller');

/**
 * import csv data
 *
 * @uses Component
 * @package Plugin.CsvSimple.Controller.Component
 */
class CsvImportComponent extends Component
{

    /**
     * number of headers row.
     *
     * @var int
     * @access public
     */
    public $headerRows = 0;

    /**
     * encoding that the string is being converted to.
     *
     * @var string
     * @access public
     */
    public $toEncoding = 'UTF-8';

    /**
     * comma separated string, it tries to detect encoding.
     *
     * @var string
     * @access public
     * @see mb_convert_variables
     */
    public $fromEncoding = 'SJIS-win';

    /**
     * specify LC_ALL locale.
     *
     * @var string
     * @access public
     */
    public $locale = 'ja_JP.UTF-8';

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
     *  allowed mime types.
     *
     * @var array
     * @access public
     */
    public $types = [
        'text/csv',
        'text/plain',
        'text/comma-separated-values',
        'application/x-csv',
        'application/csv',
    ];

    /**
     * Constructor
     *
     * @param ComponentCollection $collection
     * @param array $settings
     * @return void
     * @access public
     */
    public function __construct(ComponentCollection $collection, $settings = array())
    {
        if (isset($settings['types'])) {
            $types = $settings['types'];
            $this->types = array_merge($this->types, $settings['types']);
            unset($settings['types']);
        }

        parent::__construct($collection, $settings);
    }

    /**
     * Create rows generator
     *
     * @param array $files $_FILES[field_name]
     * @return Generator
     * @access public
     * @throws RuntimeException
     */
    public function createGenerator($files)
    {
        $this->_checkError($files['error']);
        $this->_checkType($files['type']);

        $path = $files['tmp_name'];
        $tmp = $this->_createSplTempFileObject($path);

        $cnt = 0;
        foreach ($tmp as $row) {
            if ($row === false) {
                break;
            }

            $cnt++;
            if ($cnt <= $this->headerRows) {
                continue;
            }

            yield $row;
        }
    }

    /**
     * create SplTempFileObject
     *
     * @param path $path path to file
     * @return SplTempFileObject
     * @access protected
     */
    protected function _createSplTempFileObject($path)
    {
        if (!is_readable($path)) {
            $message = sprintf(__d('csv_simple', 'Could not read file. %s'), $path);
            throw new RuntimeException($message);
        }

        setlocale(LC_ALL, $this->locale);

        $data = file_get_contents($path);
        mb_convert_variables($this->toEncoding, $this->fromEncoding, $data);

        $tmp = new SplTempFileObject(0);
        $tmp->setFlags(SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE | SplFileObject::READ_CSV);
        $tmp->setCsvControl($this->delimiter, $this->enclosure, $this->escape);
        $tmp->fwrite($data);
        $tmp->rewind();

        return $tmp;
    }

    /**
     * check $_FILES[field_name]['error']
     *
     * @param int $error error code
     * @access protected
     * @throws RuntimeException
     */
    protected function _checkError($error)
    {
        switch ($error) {
            case UPLOAD_ERR_OK:
                break;

            case UPLOAD_ERR_NO_FILE:
                $message = 'No file was uploaded';
                throw new RuntimeException(__d('csv_simple', $message));

            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $message = sprintf(__d('csv_simple', 'The uploaded file exceeds max file size. error:%d'), $error);
                throw new RuntimeException($message);

            default:
                $message = sprintf(__d('csv_simple', 'Unknown error:%d'), $error);
                throw new RuntimeException($message);
        }
    }

    /**
     * check $_FILES[field_name]['type']
     *
     * @param string $type mime type
     * @access protected
     * @throws RuntimeException
     */
    protected function _checkType($type)
    {
        if (!in_array($type, $this->types)) {
            $message = sprintf(__d('csv_simple', 'The uploaded file\'s mime type is invalid. %s'), $type);
            throw new RuntimeException($message);
        }
    }
}
