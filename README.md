# CsvSimple

[![Build Status](https://travis-ci.org/oppara/cakephp-plugin-csv_simple.svg?branch=master)](https://travis-ci.org/oppara/cakephp-plugin-csv_simple)

handle csv data

## Requirements

* PHP >= 5.5
* CakePHP >= 2.6


## Installation

    {
        "require": {
            "oppara/csv_simple": "*"
        }
    }


### Enable plugin

app/Config/bootstrap.php:

`CakePlugin::load('CsvSimple');` or `CakePlugin::loadAll();`


## Import sample

    <?php
    App::uses('AppController', 'Controller');
    App::uses('CsvImportComponent', 'CsvSimple.Controller/Component');

    class SomeController extends Controller
    {
        public $components = [
            'CsvSimple.CsvImport',
        ];

        public function import()
        {
            if ($this->request->is('get')) {
                return;
            }

            $this->CsvImport->headerRows = 1;
            $csv = Hash::get($this->request->data, 'Csv.file');
            $fields = $this->Model->getFieldsName();

            $this->Model->begin();
            try {
                $gen = $this->CsvImport->createGenerator($csv);
                foreach ($gen as $idx => $row) {
                    $data = array_combine($fields, $row);
                    $this->Model->create();
                    $this->Model->import($data);
                }
                $this->Model->commit();
            }
            catch(Exception$e) {
                $this->Model->rollback();
                $this->set('error', $e->getMessage());
            }
        }
