# CsvSimple

[![Build Status](https://travis-ci.org/oppara/cakephp-plugin-csv_simple.svg?branch=master)](https://travis-ci.org/oppara/cakephp-plugin-csv_simple)

handle csv data

## Requirements

* PHP >= 5.5
* CakePHP >= 2.6


## Installation

composer.json

    {
        "require": {
            "oppara/csv_simple": "*"
        }
    }


### Enable plugin

app/Config/bootstrap.php:

`CakePlugin::load('CsvSimple');` or `CakePlugin::loadAll();`


## Sample

import

    <?php
    App::uses('AppController', 'Controller');
    App::uses('CsvImportComponent', 'CsvSimple.Controller/Component');

    class FooController extends AppController
    {
        public $components = [
            'CsvSimple.CsvImport',
            'CsvSimple.CsvExport',
        ];

        public $uses = [
            'Bar',
        ];

        public function import()
        {
            if ($this->request->is('get')) {
                return;
            }

            try {
                $csv = Hash::get($this->request->data, 'Csv.file');

                $this->CsvImport->headerRows = 1;
                $gen = $this->CsvImport->createGenerator($csv);

                $this->Bar->begin();
                foreach ($gen as $idx => $row) {
                    $this->Bar->import($row);
                }
                $this->Bar->commit();
            }
            catch(Exception$e) {
                $this->Bar->rollback();
                $this->set('error', $e->getMessage());
            }
        }

        public function download()
        {
            try {
                $filename = date('Ymdhis') . '.tsv';
                $this->CsvExport->delimiter = "\t";

                $data = $this->Bar->find('all');
                $this->CsvExport->download($data, $filename);
            }
            catch(Exception$e) {
                $this->set('error', $e->getMessage());
            }
        }

        public function export()
        {
            try {
                $path = '/path/to/export.csv';

                $fields = $this->Bar->getHeaderFields();
                $this->CsvExport->setHeader($fields);

                $data = $this->Bar->find('all');
                $this->CsvExport->export($data, $path);
            }
            catch(Exception$e) {
                $this->set('error', $e->getMessage());
            }
        }
