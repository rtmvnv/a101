<?php

/**
 * Convert stdClass values to arrays
 */
function objectToArray($array)
{
    if (is_array($array)) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = objectToArray($value);
            }
            if ($value instanceof stdClass) {
                $array[$key] = objectToArray((array)$value);
            }
        }
    }
    if ($array instanceof stdClass) {
        return objectToArray((array)$array);
    }
    return $array;
}

/**
 * Возвращает BSONDocument в виде упрощенного массива
 */
function bsondocumentToArray($document)
{
    if (!is_array($document)) {
        $document = json_decode(json_encode($document, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), JSON_OBJECT_AS_ARRAY);
    }

    $result = [];
    foreach ($document as $key => $value) {
        if ($key === '_id' and isset($value['$oid'])) {
            $result['_id'] = $value['$oid'];
            continue;
        }

        if ($key === 'signature' and is_string($value)) {
            continue;
        }

        if ($key === 'data' and is_string($value)) {
            continue;
        }

        if (isset($value['$date']) and isset($value['$date']['$numberLong'])) {
            $result[$key] = date('c', ($value['$date']['$numberLong']) / 1000);
            continue;
        }

        if ($key === 'ts' and is_numeric($value) and $value > 1600000000) {
            $result[$key] = date('c', $value);
            continue;
        }

        if (is_array($value)) {
            $result[$key] = bsondocumentToArray($value);
            continue;
        }

        $result[$key] = $value;
    }
    return $result;
}