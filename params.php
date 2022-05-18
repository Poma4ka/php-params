<?php

namespace zcl;

use Exception;

class params
{
    private static $errorCallback;
    protected mixed $value;

    static public function onError($callback)
    {
        self::$errorCallback = $callback;
    }

    static public function number(&$value = null,$error = null) :numberParams
    {
        return new numberParams($value,$error);
    }
    static public function string(&$value = null,$error = null) :stringParams
    {
        return new stringParams($value,$error);
    }
    static public function bool(&$value = null,$error = null) :boolParams
    {
        return new boolParams($value,$error);
    }
    static public function array(&$value = null,$error = null) :arrayParams
    {
        return new arrayParams($value,$error);
    }
    static public function json(&$value = null,$error = null) :jsonParams
    {
        return new jsonParams($value,$error);
    }
    static public function date(&$value = null,$error = null) :dateParams
    {
        return new dateParams($value,$error);
    }
    static public function file(&$value = null,$error = null) :fileParams
    {
        return new fileParams($value,$error);
    }


    public function __construct($value)
    {
        $this->value = $value ?? null;
    }

    protected function error($error)
    {
        $callback = self::$errorCallback;
        $callback && $callback($this->value,$error);
    }

    public function required($error = null) :params
    {
        if ($this->value === null) {
            $this->error($error);
        }
        return $this;
    }

    public function oneOf(array $values,$error = null) :params
    {
        if ($this->value !== null) {
            if (!in_array($this->value,$values)) {
                $this->error($error);
            }
        }
        return $this;
    }

    public function get($defaultValue = null) :mixed
    {
        return $this->value ?? $defaultValue;
    }
}

class numberParams extends params
{
    public function __construct($value, $error = null)
    {
        parent::__construct($value);
        $this->checkNumber($error);
    }

    private function checkNumber($error = null) {
        if ($this->value !== null) {
            if (!is_numeric($this->value)) {
                $this->error($error);
            }
            $this->value = +$this->value;
        }
    }

    public function min($min,$error = null): self
    {
        if ($this->value !== null && $this->value < $min) {
            $this->error($error);
        }
        return $this;
    }

    public function max($max,$error = null): self
    {
        if ($this->value !== null && $this->value > $max) {
            $this->error($error);
        }
        return $this;
    }
}

class boolParams extends params
{
    public function __construct($value, $error = null)
    {
        parent::__construct($value);
        $this->checkBool($error);
    }

    private function checkBool($error = null)
    {
        if ($this->value !== null) {
            $this->value = (bool)$this->value;
        }
    }
}

class stringParams extends params
{
    public function __construct($value, $error = null)
    {
        parent::__construct($value);
        $this->checkString($error);
    }

    private function checkString($error = null)
    {
        if ($this->value !== null) {
            if ((string)$this->value != $this->value) {
                $this->error($error);
            }
            $this->value = (string)$this->value;
        }
    }

    public function email($error = null) : self
    {
        if ($this->value !== null) {
            if (!filter_var($this->value,FILTER_VALIDATE_EMAIL)) {
                $this->error($error);
            }
        }
        return $this;
    }

    public function min(int $length,$error = null) : self
    {
        if ($this->value !== null) {
            if (mb_strlen($this->value) < $length) {
                $this->error($error);
            }
        }
        return $this;
    }

    public function max(int $length,$error = null) : self
    {
        if ($this->value !== null) {
            if (mb_strlen($this->value) > $length) {
                $this->error($error);
            }
        }
        return $this;
    }

    public function symbols($symbols = null,$error = null) : self
    {
        if ($this->value !== null) {
            if ($symbols && !preg_match("/^[$symbols]+$/iu",$this->value)) {
                $this->error($error);
            }
        }
        return $this;
    }

    public function regex($regex = null,$error = null) : self
    {
        if ($this->value !== null) {
            if ($regex && !preg_match($regex,$this->value)) {
                $this->error($error);
            }
        }
        return $this;
    }
}

class arrayParams extends params
{
    public function __construct($value, $error = null)
    {
        parent::__construct($value);
        $this->checkArray($error);
    }

    private function checkArray($error = null) {
        if ($this->value !== null) {
            if (!is_array($this->value)) {
                $this->error($error);
            }
        }
    }

    public function options(array $options,$error = null) :self
    {
        if ($this->value !== null) {
            foreach ($options as $option) {
                if (!isset($this->value[$option])) {
                    $this->error($error);
                }
            }
        }
        return $this;
    }
}

class jsonParams extends params
{
    public function __construct($value, $error = null)
    {
        parent::__construct($value);
        $this->checkJson($error);
    }

    private function checkJson($error = null) {
        if ($this->value !== null) {
            $value = null;
            try {
                $value = json_decode((string)$this->value,true);
            } catch (Exception $e) {}
            if (!is_array($value)) {
                $this->error($error);
            }
        }
    }

    public function parse($error = null) : arrayParams
    {
        return new arrayParams(json_decode((string)$this->value,true),$error);
    }
}

class dateParams extends params
{
    private int|bool $timestamp;

    public function __construct($value, $error = null)
    {
        parent::__construct($value);
        $this->checkDate($error);
    }

    private function checkDate($error = null) {
        if ($this->value !== null) {
            $this->timestamp = strtotime($this->value);
            if ($this->timestamp === false) {
                $this->error($error);
            }
        }
    }

    public function format(string $format) :self
    {
        if ($this->timestamp !== false) {
            if ($format === "mysql") {
                $this->value = date('Y-m-d H:i:s',$this->timestamp);
            } else {
                $this->value = date($format,$this->timestamp);
            }
        }
        return $this;
    }
}

class fileParams extends params
{

    public function __construct($value, $error = null)
    {
        parent::__construct($value);
        $this->checkFile($error);
    }

    private function checkFile($error = null) {
        if ($this->value !== null) {
            if (!is_array($this->value) || !isset($this->value['name'],$this->value['type'],$this->value['tmp_name'],$this->value['error'],$this->value['size']) || $this->value['error']) {
                $this->error($error);
            }
        }
    }

    public function min($sizeKB,$error = null) :self
    {
        if ($this->value !== null) {
            if (($this->value['size'] / 1024) < $sizeKB) {
                $this->error($error);
            }
        }
        return $this;
    }
    public function max($sizeKB,$error = null) :self
    {
        if ($this->value !== null) {
            if (($this->value['size'] / 1024) > $sizeKB) {
                $this->error($error);
            }
        }
        return $this;
    }

    public function type(array $types,$error = null) :self
    {
        if ($this->value !== null) {
            $array = explode("/",$this->value['type']);
            if ($array === false) {
                $this->error($error);
            } else {
                if (!in_array(array_shift($array),$types)) {
                    $this->error($error);
                }
            }
        }
        return $this;
    }

    public function extension(array $extensions,$error = null) :self
    {
        if ($this->value !== null) {
            $array = explode(".",$this->value['name']);
            if ($array === false) {
                $this->error($error);
            } else {
                if (!in_array(array_pop($array),$extensions)) {
                    $this->error($error);
                }
            }
        }
        return $this;
    }
}
