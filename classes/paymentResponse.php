<?php

namespace classes;

class paymentResponse
{
    /**
     * Extract data from the payment response
     *
     * @param $data
     * @return array
     */
    public static function extract_data_from_the_payment_response($data): array
    {
        $singleDimArray = explode("|", $data);

        foreach ($singleDimArray as $value) {
            $fieldTable = explode("=", $value);
            $key = $fieldTable[0];
            $value = $fieldTable[1];
            $responseData[$key] = $value;
            unset($fieldTable);
        }
        return $responseData;
    }
}