<?php

require_once "${root_folder_path}/phive/phive.php";
require_once "${commons_folder}/common_functions.php";

$requester           = "daniel.palm";
$post_shortcut       = true;
$close_story         = true;
$move_story_folder   = true;
$push_script_output  = true;
$is_test             = false;
$create_lockfile     = true;

$sql = Phive("SQL");
if (!isCli()) {
    die("Error: the script must be run in a CLI environment" . PHP_EOL);
}

echo "=== Updating menus: adding 'SE' to excluded_countries where name LIKE '%cashback%' ===\n";
$sql->begin();

/**
 * 1. забираем все строки-кандидаты
 *    ⚠️ query() здесь должна вернуть массив ассоц-массивов
 */
$rows = $sql->query("
    SELECT id, excluded_countries
    FROM menus
    WHERE name LIKE '%cashback%'
");

$affected = 0;

foreach ($rows as $row) {
    $excluded = $row['excluded_countries'];
    $needsUpdate = false;

    // 2. рассчитываем новое значение
    if (empty($excluded)) {
        $excluded   = 'SE';
        $needsUpdate = true;
    } else {
        $countries = array_filter(array_map('trim', explode(',', $excluded)));
        if (!in_array('SE', $countries, true)) {
            $countries[] = 'SE';
            $excluded    = implode(',', $countries);
            $needsUpdate = true;
        }
    }

    // 3. если строку нужно поменять – сохраняем её через insertArray()
    if ($needsUpdate) {
        /**
         * insertArray(
         *     string $table,
         *     array  $array,          // только поля, которые хотим перезаписать + PK
         *     array  $where,          // WHERE id = :id
         *     bool   $replace,        // REPLACE INTO не нужен → false
         *     bool   $ret_new_id      // id не нужен → false
         * )
         */
        $ok = $sql->insertArray(
            'menus',
            [
                'id'                 => $row['id'],
                'excluded_countries' => $excluded,
            ],
            ['id' => $row['id']],
            false,
            false
        );

        if ($ok) {
            ++$affected;
        }
    }
}

$sql->commit();

echo "Rows updated: {$affected}\n";
