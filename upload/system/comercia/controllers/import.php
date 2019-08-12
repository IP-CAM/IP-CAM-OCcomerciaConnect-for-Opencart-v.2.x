<?php

namespace comercia\controllers;

use comercia\Util;

class Import
{

    private $_dataSources = [];
    private $_fetchData = [];
    private $_prepareData = [];
    private $_filter = [];
    private $_prepareRow = [];
    private $_saveRow = [];
    private $_save = [];
    private $_preSave=[];

    var $dataSourceData = [];

    var $data;

    function __construct()
    {
    }

    function addDataSource($name)
    {
        $this->_dataSources[$name] = $name;
    }

    function fetchData($datasource, $function)
    {
        $this->_fetchData[$datasource] = $function;
    }

    function prepareData($datasource, $function)
    {
        $this->_prepareData[$datasource][] = $function;
    }

    function prepareRow($datasource, $function)
    {
        $this->_prepareRow[$datasource][] = $function;
    }

    function save($dataSource, $function)
    {
        $this->_save[$dataSource][] = $function;
    }

    function saveRow($dataSource, $function)
    {
        $this->_saveRow[$dataSource][] = $function;
    }

    function filter($dataSource, $function)
    {
        $this->_filter[$dataSource][] = $function;
    }

    function preSave($dataSource,$function){
        $this->_preSave[$dataSource][]=$function;
    }

    function run($dataSources = false)
    {
        if (!$dataSources) {
            $dataSources = $this->_dataSources;
        }
        if (is_string($dataSources)) {
            $dataSources = [$dataSources];
        }

        foreach ($dataSources as $dataSource) {
            $data = $this->getDataForSource($dataSource);

            if (isset($this->_preSave[$dataSource])) {
                    foreach ($this->_preSave[$dataSource] as $preSaver) {
                        Util::lambda()->execute($preSaver, $data);
                    }
            }

            if (isset($this->_saveRow[$dataSource])) {
                foreach ($data as $row) {
                    foreach ($this->_saveRow[$dataSource] as $saver) {
                        Util::lambda()->execute($saver, [$row]);
                    }
                }
            }

            if (isset($this->_save[$dataSource])) {
                foreach ($this->_save[$dataSource] as $saver) {
                    Util::lambda()->execute($saver, $data);
                }
            }
        }
    }

    function getDataForSource($dataSource)
    {
        if (!isset($this->data[$dataSource])) {
            $data = "";
            if (isset($this->_fetchData[$dataSource])) {
                $data = Util::lambda()->execute($this->_fetchData[$dataSource]);
            }

            if (isset($this->_prepareData[$dataSource])) {
                foreach ($this->_prepareData[$dataSource] as $preparer) {
                    $data = Util::lambda()->execute($preparer, [$data]);
                }
            }

            if (isset($this->_filter[$dataSource])) {
                foreach ($this->_filter[$dataSource] as $filter) {
                    $data = array_filter($data, Util::lambda()->getCallable($filter));
                }
            }

            if (isset($this->_prepareRow[$dataSource])) {
                foreach ($this->_prepareRow[$dataSource] as $preparer) {
                    $data = array_map(Util::lambda()->getCallable($preparer), $data);
                }
            }

            $this->data[$dataSource] = $data;
        }
        return $this->data[$dataSource];
    }

}

?>