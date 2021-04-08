<?php

namespace Proengeno\Edifact\Validation;

use Proengeno\Edifact\Interfaces\SegValidatorInterface;
use Proengeno\Edifact\Exceptions\SegValidationException;

class SegmentValidator implements SegValidatorInterface
{
    const ALPHA = 'a';
    const NUMERIC = 'n';
    const ALPHA_NUMERIC = 'an';

    /**
     * @param array $blueprint
     * @param array $data
     *
     * @return SegValidatorInterface
     */
    public function validate($blueprint, $data)
    {
        foreach ($blueprint as $dataGroupKey => $dataGroup) {
            foreach ($dataGroup as $dataKey => $validation) {
                if ($validation !== null) {
                    list($necessaryStatus, $type, $lenght) = explode('|', $validation);

                    if ($this->isDatafieldOptional($necessaryStatus) && !$this->isDataIsAvailable($data, $dataGroupKey, $dataKey)) {
                        $this->cleanUp($data, $dataGroupKey, $dataKey);
                        continue;
                    }

                    $this->checkAvailability($data, $dataGroupKey, $dataKey);
                    $this->checkStringType($type, $data, $dataGroupKey, $dataKey);
                    $this->checkStringLenght($lenght, $data, $dataGroupKey, $dataKey);
                }
                $this->cleanUp($data, $dataGroupKey, $dataKey);
            }
            $this->checkUnknowDatafields(@$data[$dataGroupKey]);
            $this->cleanUp($data, $dataGroupKey);
        }
        $this->checkUnknowDataGroup($data);

        return $this;
    }

    /**
     * @param array $data
     */
    private function checkUnknowDataGroup($data): void
    {
        if (empty($data)) {
            return;
        }

        $keys = array_keys($data);
        throw SegValidationException::forKey(array_shift($keys), 'Data-Group not allowed.', 7);
    }

    /**
     * @param array $data
     */
    private function checkUnknowDatafields($data): void
    {
        if (empty($data)) {
            return;
        }
        $key = current(array_keys($data));
        $value = current($data);

        throw SegValidationException::forKeyValue($key, $value, 'Data-Element not allowed.', 6);
    }

    /**
     * @param array $data
     * @param string $dataGroupKey
     * @param string|null $dataKey
     */
    private function cleanUp(&$data, $dataGroupKey, $dataKey = null): void
    {
        if ($dataKey) {
            unset($data[$dataGroupKey][$dataKey]);
        } else {
            unset($data[$dataGroupKey]);
        }
    }

    /**
     * @param array $data
     * @param string $dataGroupKey
     * @param string $dataKey
     */
    private function isDataIsAvailable($data, $dataGroupKey, $dataKey): bool
    {
        return $this->isDatafieldIsAvailable($data, $dataGroupKey, $dataKey)
            && $data[$dataGroupKey][$dataKey] !== null
            && $data[$dataGroupKey][$dataKey] !== '';
    }

    /**
     * @param array $data
     * @param string $dataGroupKey
     * @param string $dataKey
     */
    private function isDatafieldIsAvailable($data, $dataGroupKey, $dataKey): bool
    {
        return isset($data[$dataGroupKey][$dataKey]);
    }

    /**
     * @param array $data
     * @param string $dataGroupKey
     * @param string $dataKey
     */
    private function checkAvailability($data, $dataGroupKey, $dataKey): void
    {
        if ($this->isDatafieldIsAvailable($data, $dataGroupKey, $dataKey)) {
            return;
        }

        throw SegValidationException::forKey($dataKey, 'Data-Element not available, but needed.', 1);
    }

    /**
     * @param string|null $necessaryStatus
     */
    private function isDatafieldOptional($necessaryStatus): bool
    {
        return !($necessaryStatus == 'M' || $necessaryStatus == 'R');
    }

    /**
     * @param string|null $type
     * @param array $data
     * @param string $dataGroupKey
     * @param string $dataKey
     */
    private function checkStringType($type, $data, $dataGroupKey, $dataKey): void
    {
        $string = $data[$dataGroupKey][$dataKey];

        if ($type == static::ALPHA_NUMERIC || $type == null) {
            return;
        }
        if ($type == static::NUMERIC && !is_numeric($string)) {
            throw SegValidationException::forKeyValue($dataKey, $string, 'Data-Element contains non-numeric characters.', 2);
        }
        if ($type == static::ALPHA && !ctype_alpha(str_replace(' ', '', $string))) {
            throw SegValidationException::forKeyValue($dataKey, $string, 'Data-Element contains non-alpha characters.', 3);
        }
    }

    private function checkStringLenght(string $lenght, array $data, string $dataGroupKey, string $dataKey): void
    {
        $string = $data[$dataGroupKey][$dataKey];

        $strLen = strlen($string);
        if ($strLen == 0) {
            throw SegValidationException::forKeyValue($dataKey, $string, 'Data-Element unavailable or empty.', 4);
        }
        if ($lenght < $strLen) {
            throw SegValidationException::forKeyValue($dataKey, $string, 'Data-Element has more than' . $lenght . ' Characters.', 5);
        }
    }
}
