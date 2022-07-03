<?php

namespace classes;

class sealCalculation
{
    /** @var array $singleDimArray */
    private array $singleDimArray = array();

    /**
     * Compute payment init seal
     *
     * @param $sealAlgorithm
     * @param $data
     * @param $secretKey
     * @return false|string
     */
    public function compute_payment_init_seal($sealAlgorithm, $data, $secretKey)
    {
        $dataStr = $this->flatten($data);
        return $this->compute_seal_from_string($sealAlgorithm, $dataStr, $secretKey, true);
    }

    /**
     * Flat the multidimensional array
     *
     * @param $multiDimArray
     * @return string
     */
    public function flatten($multiDimArray): string
    {
        $sortedMultiDimArray = $this->recursive_table_sort($multiDimArray);
        array_walk_recursive($sortedMultiDimArray, [__CLASS__, 'valueResearch']);
        $string = implode("", $this->singleDimArray);
        $this->singleDimArray = [];
        return $string;
    }

    /**
     * @param $table
     * @return mixed
     */
    private function recursive_table_sort($table)
    {
        ksort($table);
        foreach ($table as $key => $value) {
            if (is_array($value)) {
                $value = $this->recursive_table_sort($value);
                $table[$key] = $value;
            }
        }
        return $table;
    }

    /**
     * Compute seal from string
     *
     * @param $sealAlgorithm
     * @param $data
     * @param $secretKey
     * @param $hmac256IsDefault
     * @return false|string
     */
    private function compute_seal_from_string($sealAlgorithm, $data, $secretKey, $hmac256IsDefault)
    {
        if (strcmp($sealAlgorithm, "HMAC-SHA-256") == 0) {
            $hmac256 = true;
        } elseif (empty($sealAlgorithm)) {
            $hmac256 = $hmac256IsDefault;
        } else {
            $hmac256 = false;
        }
        return $this->compute_seal($hmac256, $data, $secretKey);
    }

    /**
     * Alphabetical order of field names in the table
     *
     * @param $hmac256
     * @param $data
     * @param $secretKey
     * @return false|string
     */
    private function compute_seal($hmac256, $data, $secretKey)
    {
        if ($hmac256) {
            $seal = hash_hmac('sha256', $data, $secretKey);
        } else {
            $seal = hash('sha256', $data . $secretKey);
        }
        return $seal;
    }

    /**
     * Compute payment response seal
     *
     * @param $sealAlgorithm
     * @param $data
     * @param $secretKey
     * @return false|string
     */
    public function compute_payment_response_seal($sealAlgorithm, $data, $secretKey)
    {
        return $this->compute_seal_from_string($sealAlgorithm, $data, $secretKey, false);
    }

    /**
     * This function flattens the sorted payment data table into singleDimArray
     *
     * @param $value
     * @return array
     */
    public function valueResearch($value): array
    {
        $this->singleDimArray[] = $value;
        return $this->singleDimArray;
    }
}