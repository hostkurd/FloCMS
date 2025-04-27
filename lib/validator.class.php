<?php

interface RuleInterface
{
    public function passes ($value):bool;
    public function message ($attribute):string;
}
/**
 * Rules for Validation
 */
class RequiredRule implements RuleInterface
{
    public function passes ($value):bool
    {
        return isset($value) && $value !== '';
    }

    public function message ($attribute):string
    {
        return "$attribute is Required.";
    }
}

class StringRule implements RuleInterface
{
    public function passes ($value):bool
    {
        return is_string($value);
    }

    public function message ($attribute):string
    {
        return "$attribute must be a string.";
    }
}

class IntegerRule implements RuleInterface
{
    public function passes ($value):bool
    {
        return is_int($value);
    }

    public function message ($attribute):string
    {
        return "$attribute must be a Integer.";
    }
}

class MinRule implements RuleInterface
{
    protected $min;

    public function __construct($min){
        $this->min = $min;
    }


    public function passes ($value):bool
    {
        if(strlen($value)){
            return strlen($value) >= $this->min;
        }elseif(is_numeric($value)){
            return $value >= $this->min;
        }
        return false;
    }

    public function message ($attribute):string
    {
        return "$attribute must be a lest $this->min character or value.";
    }
}

class MaxRule implements RuleInterface
{
    protected $max;

    public function __construct($max){
        $this->max = $max;
    }


    public function passes ($value):bool
    {
        if(strlen($value)){
            return strlen($value) <= $this->max;
        }elseif(is_numeric($value)){
            return $value <= $this->max;
        }
        return false;
    }

    public function message ($attribute):string
    {
        return "$attribute must not exceed $this->max character or value.";
    }
}

class InRule implements RuleInterface
{
    protected $rawData;
    protected $valueArray;

    public function __construct($valueArray){

        $this->rawData = $valueArray;
        $this->valueArray = explode(',',$valueArray);
    }

    public function passes ($value):bool
    {
        return in_array($value, $this->valueArray);
    }

    public function message ($attribute):string
    {
        return "$attribute should be ( $this->rawData ).";
    }
}
/**
 * Validation Exception Class
 */
class ValidationException extends Exception
{
    protected array $errors;
    public function __construct(array $errors, $message = "Validation Failed", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors():array
    {
        return $this->errors;
    }
}

/**
 * Validator Class
 */
class Validator
{
    protected static $ruleMap = [
        'required' => RequiredRule::class,
        'string' => StringRule::class,
        'integer' => IntegerRule::class,
        'min' => MinRule::class,
        'max' => MaxRule::class,
        'in' => InRule::class
    ];

    public static function validate(array $data = [], array $rules = [])
    {
        $errors = [];
        foreach($rules as $field => $ruleSet){
            $rulesArray = explode('|', $ruleSet);
            foreach($rulesArray as $rule){
                $parts = explode(':',$rule,2);
                $ruleName = $parts[0];
                $parameter = $parts[1] ?? null;

                if (isset(self::$ruleMap[$ruleName])){
                    $ruleInstance = ($parameter !== null)
                    ? new self::$ruleMap[$ruleName]($parameter)
                    : new self::$ruleMap[$ruleName];

                    $value = $data[$field] ?? null;

                    if(!$ruleInstance->passes($value)){
                        $errors[$field][] = $ruleInstance->message($field);
                    }
                }
            }
        }

        if(!empty($errors)){
            throw new ValidationException($errors);
        }
    }

}