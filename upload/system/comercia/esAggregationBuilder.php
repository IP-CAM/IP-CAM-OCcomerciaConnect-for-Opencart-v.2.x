<?php

class EsAggregationBuilder
{
    var $aggregations = [];


    function options($field, $name = "options")
    {
        $this->aggregations[$name] = [
            "terms" => [
                "field" => $field,
                "size" => "999"
            ],
        ];
    }

    function range($field, $name = "range")
    {
        $this->aggregations[$name . ".min"] = [
            "min" => [
                "field" => $field]
        ];

        $this->aggregations[$name . ".max"] = [
            "max" => [
                "field" => $field
            ]
        ];
    }

    function getAggregations()
    {
        return $this->aggregations;
    }
}

?>