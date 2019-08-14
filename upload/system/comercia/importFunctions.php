<?php

namespace comercia;

class ImportFunctions
{

    public function fetch_db($table, $fields = [], $where = [], $structure = [])
    {
        return function () use ($table, $fields, $where, $structure) {
            return Util::db()->select($table, $fields, $where, $structure);
        };
    }

    public function fetch_upload($fieldName)
    {
        return function () use ($fieldName) {
            return file_get_contents(Util::request()->file()->$fieldName->tmp_name);
        };
    }

    public function fetch_file($fileName)
    {
        return function () use ($fileName) {
            return file_get_contents($fileName);
        };
    }

    public function prepare_jsonUnserialize()
    {
        return function ($data) {
            return json_decode($data, true);
        };
    }

    public function prepare_xmlUnserialize()
    {
        return function ($data) {
            return json_decode(json_encode(simplexml_load_string($data)), true);
        };
    }

    function prepare_elementEnsureList($elements)
    {

        if (is_string($elements)) {
            $elements = [$elements];
        }

        return function ($data) use ($elements) {
            foreach ($data as $key => &$val) {
                if (in_array($key, $elements)) {
                    if (!isset($val[0])) {
                        $val = [$val];
                    }
                }

                if (is_array($val)) {
                    if (!isset($val[0])) {
                        $func = $this->prepare_elementEnsureList($elements);
                        $val = $func($val);
                    } else {
                        foreach ($val as &$subval) {
                            $func = $this->prepare_elementEnsureList($elements);
                            $subval = $func($subval);
                        }
                    }
                }

            }
            return $data;
        };


    }

    function preSave_setForAll($table, $field, $value)
    {
        return function () use ($table, $field, $value) {
            Util::db()->query("UPDATE `" . DB_PREFIX . $table . "` SET `" . $field . "`='" . $value . "'");
        };
    }

    function presave_esSetupIndex($index, $mapping, $delete = true, $synonyms = false)
    {
        return function () use ($index, $mapping, $delete,$synonyms) {
            if ($delete) {
                Util::elasticSearch()->deleteIndex($index);
            }

            Util::elasticSearch()->createIndex($index, $mapping,$synonyms);
        };
    }

    function saveRow_toDbTable($table, $keys = [], $structure = [])
    {
        return function ($row) use ($table, $keys, $structure) {
            Util::db()->saveDataObject($table, $row, $keys, $structure);
        };
    }

    function saveRow_esAddDocument($index, $type, $idField)
    {
        return function ($row) use ($index, $type, $idField) {
            Util::elasticSearch()->addDocument($index, $type, $row, $idField);
        };
    }

    function save_toDbTable($table, $keys = [], $structure = [])
    {
        return function ($data) use ($table, $keys, $structure) {
            Util::db()->saveDataObjectArray($table, $data, $keys, $structure);
        };
    }


}

?>