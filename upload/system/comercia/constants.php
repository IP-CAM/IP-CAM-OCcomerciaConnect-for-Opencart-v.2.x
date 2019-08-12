<?php
define("ES_ANALYZED_FALSE", 0);
define("ES_ANALYZED_TRUE", 1);
define("ES_ANALYZED_WITH_RAW", 2);

define("ES_ANALYZER_SYNONYM", "synonym_analyzer");
define("ES_ANALYZER_NONE", false);

if(!defined(DIR_ROOT)) {
    define(DIR_ROOT, DIR_CATALOG . "../");
}