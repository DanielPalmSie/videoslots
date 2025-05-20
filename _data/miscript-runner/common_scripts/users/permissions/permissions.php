<?php

/**
 * Creates new permission tag in a permission_tags table
 * @param $sc_id
 * @param string $tag
 * @return void
 */
function createPermissionTag(string $tag){
    $sql = Phive('SQL');

    $qry = " SELECT *
                FROM permission_tags
                WHERE tag = '{$tag}'
            ";

    $permission_tag_check = $sql->loadAssoc($qry);
    if (!empty($permission_tag_check)) {
        echo "WARN: Tag '{$tag}' already exists!\n";
        return;
    }

    $to_insert = [
        'tag' => $tag,
    ];
    $sql->insertArray('permission_tags', $to_insert);

    $permission_tag_check = $sql->loadAssoc($qry);
    if (!empty($permission_tag_check)) {
        echo "Permission tag added: {$tag}";
    } else {
        echo "WARN: Tag '{$tag}' wasn't added!\n";
    }

}

/**
 * Add a list of permission tags to a group.
 *
 * @param string $sc_id     Story Id used in logging and auditing
 * @param int    $group_id  The group ID to add tags to
 * @param string $tag       A tag to add to group
 */
function insertPermissionTag($sc_id, int $group_id, string $tag)
{
    $sql = Phive('SQL');

    // Query and check if permission is assigned already
    $qry = " SELECT *
                FROM permission_groups
                WHERE group_id = {$group_id}
                  AND tag = '{$tag}'
            ";
    $permission_group_check = $sql->loadAssoc($qry);
    if (!empty($permission_group_check)) {
        echo "WARN: Tag '{$tag}' already assigned, skipping entry!\n";
        return;
    }

    $to_insert = [
        'group_id' => $group_id,
        'tag' => $tag,
        'mod_value' => '',
        'permission' => 'grant',
    ];
    $sql->insertArray('permission_groups', $to_insert);

    $permission_group_check = $sql->loadAssoc($qry);
    echo "Permission tag added:   group: [{$permission_group_check['group_id']}]  tag: [{$permission_group_check['tag']}]  \n";
}

/**
 * Add permission tags to specific groups from a csv file.
 *
 * @param string $sc_id         Story Id used in logging and auditing
 * @param string $full_csv_path The CSV file name (including full path) to be processed
 *                              CSV Columns: [group_id],[tag]
 */
function insertPermissionTagsCsv($sc_id, $full_csv_path)
{
    echo "Inserting Permission Tags ----------\n";
    $csv = readCsv($full_csv_path);
    $cnt = 0;
    foreach ($csv as $entry) {
        insertPermissionTag($sc_id, $entry['group_id'], $entry['tag']);
        $cnt++;
    }
    echo "Processed {$cnt} entries  ----------\n\n";
}

/**
 * Add a list of permission tags to a group.
 *
 * @param string $sc_id     Story Id used in logging and auditing
 * @param int    $group_id  The group ID to add tags to
 * @param array  $tags      An array of tags to add to the group
 */
function insertPermissionTagsMulti($sc_id, int $group_id, array $tags)
{
    echo "Inserting Permission Tags ----------\n";
    $cnt = 0;
    foreach ($tags as $tag) {
        insertPermissionTag($sc_id, $group_id, $tag);
        $cnt++;
    }
    echo "Processed {$cnt} entries  ----------\n\n";
}
