<?php

namespace Proengeno\Edifact\Templates;

use Proengeno\Edifact\Message\Delimiter;
use Proengeno\Edifact\Interfaces\SegInterface;
use Proengeno\Edifact\Exceptions\EdifactException;
use Proengeno\Edifact\Validation\SegmentValidator;
use Proengeno\Edifact\Interfaces\SegValidatorInterface;

abstract class AbstractSegment implements SegInterface
{
    protected static $jsonDescribtion = null;
    protected static $buildValidator;
    protected static $buildDelimiter;
    protected $elements = [];
    protected $cache = [];
    protected $delimiter;
    protected $validator;

    protected function __construct(array $elements)
    {
        $this->elements = $elements;
        $this->delimiter = static::getBuildDelimiter();
        $this->validator = static::$buildValidator ?: new SegmentValidator;
    }

    public static function fromSegLine($segLine)
    {
        return new static(static::mapToBlueprint($segLine));
    }

    public static function setBuildDelimiter(Delimiter $buildDelimiter = null)
    {
        self::$buildDelimiter = $buildDelimiter;
    }

    public static function getBuildDelimiter()
    {
        return self::$buildDelimiter ?: new Delimiter;
    }

    public static function setBuildValidator(SegValidatorInterface $buildValidator = null)
    {
        self::$buildValidator = $buildValidator;
    }

    public function getValue($dataGroup, $element)
    {
        return array_values(array_values($this->elements)[$dataGroup])[$element];
    }

    public function getValidator()
    {
        return $this->validator;
    }

    public function getDelimiter()
    {
        return $this->delimiter;
    }

    public function name()
    {
        return $this->getValue(0,0);
    }

    public function validate()
    {
        $this->validator->validate(static::$validationBlueprint, $this->elements);

        return $this;
    }

    public function toArray()
    {
        $result = [];
        foreach ($this->getGetterMethods() as $method) {
            if (null !== $value = $this->{$method}()) {
                $result[$method] = $value;
            }
        }
        return $result;
    }

    public function __toString()
    {
        if (!isset($this->cache['segLine'])) {
            $dataFields = array_map(function($dataGroups) {
                return implode($this->delimiter->getData(), $this->delimiter->terminate($this->deleteEmptyArrayEnds($dataGroups)));
            }, $this->elements);

            $this->cache['segLine'] = implode($this->delimiter->getDataGroup(), $this->deleteEmptyArrayEnds($dataFields));
        }

        return $this->cache['segLine'] . $this->delimiter->getSegment();
    }

    public function __get($attribute)
    {
        try {
            if (in_array($attribute, $this->getGetterMethods())) {
                return $this->$attribute();
            }
        } catch (\Throwable $e) { }

        return null;
    }

    public function __call($name, $arguments)
    {
        if (strpos($name, 'get') === false) {
            throw new \BadMethodCallException;
        }

        $pattern = substr($name, 3);

        foreach ($this->elements as $element) {
            if (array_key_exists($pattern, $element)) {
                return $element[$pattern];
            }
        }

        throw new \BadMethodCallException;
    }

    protected function getGetterMethods()
    {
        if (isset($this->cache['getterMethods'])) {
            return $this->cache['getterMethods'];
        }

        $this->cache['getterMethods'] = [];
        foreach ((new \ReflectionClass(static::class))->getMethods() as $method) {
            if ($method->class === static::class && !$method->isStatic() && $method->isPublic()) {
                $this->cache['getterMethods'][] = $method->name;
            }
        }

        return $this->cache['getterMethods'];
    }

    protected static function mapToBlueprint($segLine)
    {
        $i = 0;
        $elements = [];
        $inputDataGroups = static::getBuildDelimiter()->explodeSegments($segLine);
        foreach (static::$validationBlueprint as $BpDataKey => $BPdataGroups) {
            $inputElement = [];
            if (isset($inputDataGroups[$i])) {
                $inputElement = static::getBuildDelimiter()->explodeElements($inputDataGroups[$i]);
            }

            $j = 0;
            foreach ($BPdataGroups as $key => $value) {
                $elements[$BpDataKey][$key] = isset($inputElement[$j]) ? $inputElement[$j] : null;
                $j++;
            }
            $i++;
        }

        return $elements;
    }

    private function deleteEmptyArrayEnds(array $array)
    {
        $reversed = array_reverse($array);
        foreach ($reversed as $key => $value) {
            if (!empty($value)) {
                break;
            }
            unset($reversed[$key]);
        }
        return array_reverse($reversed);
    }
}
