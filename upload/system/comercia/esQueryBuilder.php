<?php

class EsQueryBuilder
{
    var $query = [];

    function matchPhrase($field, $text, $analyzer = false)
    {
        $this->query['match_phrase'][$field]["query"] = $text;

        if ($analyzer) {
            $this->query["match_phrase"][$field]["analyzer"] = $analyzer;
        }

        return $this;
    }

    function matchPartialPhrase($field, $text, $analyzer = false)
    {
        $this->query['regexp'][$field] = ".*{$text}.*";

        if ($analyzer) {
            $this->query["regexp"][$field]["analyzer"] = $analyzer;
        }

        return $this;
    }

    function matchRange($field, $min, $max)
    {
        $this->query["bool"]["must"][]["range"][$field] = ["gte" => $min, "lte" => $max];
    }

    function match($field, $values)
    {
        if (is_array($values)) {
            if (isset($values[0])) {
                $shoulds = [];
                foreach ($values as $value) {
                    if (is_array($value)) {
                        foreach ($value as $key => $subValue) {
                            $should["bool"]["must"][]["match"][$field . "." . $key] = $subValue;
                        }
                        $shoulds[] = $should;
                    } else {
                        $shoulds[] = ["match" => [$field => $value]];
                    }
                }
                if (count($shoulds)) {
                    $this->query["bool"]["must"][]["bool"]["should"] = $shoulds;
                }
            } else {
                foreach ($values as $key => $value) {
                    $this->query["bool"]["must"][]["match"][$field . "." . $key] = $value;
                }
            }
        } else {
            $this->query["bool"]["must"][]["match"][$field] = $values;
        }
        return $this;
    }

    function getQuery()
    {
        return $this->query;
    }
}

?>