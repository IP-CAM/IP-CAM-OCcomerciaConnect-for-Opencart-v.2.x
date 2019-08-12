<?php

namespace comercia;

class Lambda
{
    public function execute($input, $params = [])
    {
        $callable = $this->getCallable($input);
        return call_user_func_array($callable, $params);
    }

    public function getCallable($input)
    {
        if (is_callable($input) || is_array($input) && method_exists($input[0], $input[1])) {
            return $input;
        }

        static $callables = [];

        if (!isset($callables[$input])) {
            $result = null;
            if (is_string($input)) {
                $split = explode("=>", $input, 2);
                $result = eval('return function(' . $split[0] . '){return ' . $split[1] . ';};');
            }
            if (!is_callable($result)) {
                $result = function () {
                };
            }
            $callables[$input] = $result;
        }
        return $callables[$input];
    }
}

?>