<?php

/**
 * Reads a csv from a given path and returns an array if successful otherwise false.
 *
 * @param string $csv_path
 * @return array
 */
function readCsv(string $csv_path): array
{
    if (empty($csv_path)) {
        throw new InvalidArgumentException("readCsv: No path to read from");
    }

    $csv = new ParseCsv\Csv($csv_path);
    return $csv->data;
}

function readCsvPR(string $csv_path): array
{
    if (empty($csv_path)) {
        throw new InvalidArgumentException("readCsv: No path to read from");
    }

    $csv = new ParseCsv($csv_path);
    return $csv->data;
}

/**
 * Saves contents of an array compatible with parseCsv to a csv to a predefined path.
 *
 * @param array $to_save
 * @param string $csv_path
 * @return bool
 */
function saveCsv(array $to_save, string $csv_path): bool
{
    if (empty($to_save)) {
        throw new InvalidArgumentException("saveCsv: Empty dataset to save");
    }

    if (empty($csv_path)) {
        throw new InvalidArgumentException("saveCsv: Empty csv_path");
    }

    echo "\nSaving to {$csv_path}\n";
    $csv = new ParseCsv\Csv();
    $data = json_decode(json_encode($to_save), true);
    $csv->data = $data;
    $csv->heading = true;
    $csv->titles = array_keys($data[0]);
    $csv->save($csv_path);
    return true;
}

function saveCsvPR(array $to_save, string $csv_path): bool
{
    if (empty($to_save)) {
        throw new InvalidArgumentException("saveCsv: Empty dataset to save");
    }

    if (empty($csv_path)) {
        throw new InvalidArgumentException("saveCsv: Empty csv_path");
    }

    echo "\nSaving to {$csv_path}\n";
    $csv = new ParseCsv();
    $data = json_decode(json_encode($to_save), true);
    $csv->data = $data;
    $csv->heading = true;
    $csv->titles = array_keys($data[0]);
    $csv->save($csv_path);
    return true;
}
