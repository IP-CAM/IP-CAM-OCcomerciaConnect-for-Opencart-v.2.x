<?php

namespace comercia;

class ElasticSearch
{
    var $es;

    function __construct()
    {
        if (is_dir(DIR_SYSTEM . "vendor/elasticsearch")) {
            include_once(DIR_SYSTEM . "vendor/autoload.php");

            if (!empty(ES_HOST)) {
                $hosts = array();
                $hosts[] = ["host" => ES_HOST];
                $this->es = \Elasticsearch\ClientBuilder::create()->setHosts($hosts)->build();
            } else {
                $this->es = \Elasticsearch\ClientBuilder::create()->build();
            }
        } else {
            throw new \Exception("Elastic search is not installed in the system directory");
        }
    }

    function indexExists($indexName)
    {
        $indexParams['index'] = $indexName;
        return $this->es->indices()->exists($indexParams);
    }

    function deleteIndex($indexName)
    {
        if ($this->indexExists($indexName)) {
            $params['index'] = $indexName;
            $this->es->indices()->delete($params);
        }
    }

    function createIndex($indexName, $mappings, $synonyms = false)
    {

        $indexParams['index'] = $indexName;
        $indexParams['body']['settings']['number_of_shards'] = 3;
        $indexParams['body']['settings']['number_of_replicas'] = 0;
        if ($synonyms) {
            $indexParams['body']['settings']["analysis"] = [
                "filter" => [
                    "synonym_filter" => [
                        "type" => "synonym",
                        "synonyms" => $synonyms
                    ]
                ],
                "analyzer" => [
                    "synonym_analyzer" => [
                        "tokenizer" => "standard",
                        "filter" => [
                            "lowercase",
                            "synonym_filter"
                        ]
                    ]
                ]
            ];


        }
        $indexParams['body']['mappings'] = $mappings;
        $this->es->indices()->create($indexParams);
    }

    function addDocument($indexName, $type, $data, $idField)
    {
        $params = array();

        $params['body'] = $data;
        $params['type'] = $type;
        $params['index'] = $indexName;

        if (!empty($idField) && !empty($data[$idField])) {
            $params['id'] = $data[$idField];
            $this->es->index($params);
        }
    }

    function mapping($properties)
    {
        return ['_source' => ['enabled' => true], 'properties' => $properties];
    }


    function type_string($isAnalyzed = true)
    {
        $field = ["type" => "string"];
        if ($isAnalyzed) {
            $field["fielddata"] = true;
            if ($isAnalyzed > 1) {
                $field["fields"] = ["raw" => ["type" => "string", "index" => "not_analyzed"]];
            }
        } else {
            $field["index"] = "not_analyzed";
        }
        return $field;
    }

    function type_integer()
    {
        return ["type" => "integer"];
    }

    public function type_decimal()
    {
        return ["type" => "double"];
    }

    public function type_object($mapping)
    {
        return ["properties" => $mapping];
    }

    public function type_boolean()
    {
        return ["type" => "boolean"];
    }

    function queryBuilder()
    {
        include_once(__DIR__ . "/esQueryBuilder.php");
        return new \EsQueryBuilder();
    }

    function aggregationBuilder()
    {
        include_once(__DIR__ . "/esAggregationBuilder.php");
        return new \EsAggregationBuilder();
    }

    public function singleFieldSearch($index, $type, $field, $text, $analyzer = false, $offset = false, $amount = false)
    {
        return $this->searchByQuery($index, $type, $this->queryBuilder()->matchPhrase($field, $text, $analyzer)->getQuery(), $offset, $amount);
    }

    public function singlePartialFieldSearch($index, $type, $field, $text, $analyzer = false, $offset = false, $amount = false)
    {
        return $this->searchByQuery($index, $type, $this->queryBuilder()->matchPartialPhrase($field, $text, $analyzer)->getQuery(), $offset, $amount);
    }

    public function searchByQuery($index, $type, $query, $offset = false, $amount = false, $aggregations = false)
    {
        if (is_object($query)) {
            $query = $query->getQuery();
        }
        if (is_object($aggregations)) {
            $aggregations = $aggregations->getAggregations();
        }

        $params = [
            'index' => $index,
            'type' => $type,
            "sort" => [
                "_score"
            ]
        ];

        if($query){
            $params['body']['query']= $query;
        }

        if ($aggregations) {
            $params["body"]["aggs"] = $aggregations;
        }
        if ($offset) {
            $params["from"] = $offset;
        }
        if ($amount) {
            $params["size"] = $amount;
        }


        $results = $this->es->search($params);
        return $results;
    }


}