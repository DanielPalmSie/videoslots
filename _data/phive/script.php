<?php

/*$requester = "@daniel";            # change requester
# $sc_id = 123456;           # enable to override story ID if different from folder name
$post_shortcut = true;       # enable posting of script output - set false if script produces secret output
$close_story = true;         # enable closing story - set false if same story requires multiple scripts to be run
$move_story_folder = true;   # enable moving story folder to archived folder - set false if not needed
$push_script_output = true;  # enable pushing story output to git - set false if not needed
$is_test = false;             # 'true' will override and disable the 4 variables above - set 'false' for production
$create_lockfile = true;     # handles creation and pushing of lockfile if set to true
# $extra_args can store additional parameter supplied from the pipeline*/

$old_alias = 'special-valentine_wheelofjackpots';
$new_alias = 'valentine_wheelofjackpots';

$sql = phive('SQL');

/* 1. Обновляем мастер-базу  */
$rows = $sql->exec(
    'UPDATE trophy_awards SET alias = ? WHERE alias = ?',
    [$new_alias, $old_alias]
);
echo "Master DB │ alias обновлён в {$rows} строк(ах)\n";

/* 2. Если таблица есть в шардовых базах — пробегаемся по всем 0-9   */
/*    (если trophy_awards хранится только в мастере, этот цикл       */
/*     можно удалить, он просто вернёт 0 строк и пойдёт дальше)      */
for ($i = 0; $i <= 9; $i++) {
    $rows = $sql->sh($i)->exec(
        'UPDATE trophy_awards SET alias = ? WHERE alias = ?',
        [$new_alias, $old_alias]
    );
    echo "Node {$i}   │ alias обновлён в {$rows} строк(ах)\n";
}

/* 3. Проверим, что больше “спешиалов” не осталось                  */
$total_left = $sql->loadValue(
    'SELECT COUNT(*) FROM trophy_awards WHERE alias LIKE "special-%"'
);
echo "\nОсталось записей с префиксом «special-»: {$total_left}\n";
