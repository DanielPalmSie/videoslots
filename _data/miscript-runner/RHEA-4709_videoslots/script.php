<?php
require_once "{$root_folder_path}/phive/phive.php";
require_once "{$commons_folder}/common_functions.php";

$requester = "@ndubuisi.onyemenam";            # change requester
# $sc_id = 123456;           # enable to override story ID if different from folder name
$post_shortcut = true;       # enable posting of script output - set false if script produces secret output
$close_story = true;         # enable closing story - set false if same story requires multiple scripts to be run
$move_story_folder = true;   # enable moving story folder to archived folder - set false if not needed
$push_script_output = true;  # enable pushing story output to git - set false if not needed
$is_test = false;             # 'true' will override and disable the 4 variables above - set 'false' for production
$create_lockfile = false;     # handles creation and pushing of lockfile if set to true

// Initialize SQL connection
$sql = phive("SQL");
if (!isCli()) {
    die("Error: the script must be run in a CLI environment" . PHP_EOL);
}

echo "Starting migration from users_settings to users_privacy_settings...\n";

// Updated mapping based on new CSV and migration structure
// Format: 'setting_name_in_old_table' => ['channel' => X, 'type' => Y, 'product' => Z (optional)]
$mappings = [
    // Bonus communications
    'privacy-bonus-direct-mail' => ['channel' => 'direct_mail', 'type' => 'offers', 'product' => null],
    'privacy-bonus-interactive-voice' => ['channel' => 'voice', 'type' => 'offers', 'product' => null],
    'privacy-bonus-outbound-calls' => ['channel' => 'calls', 'type' => 'offers', 'product' => null],
    
    // Main communications - for new things
    'privacy-main-new-email' => ['channel' => 'email', 'type' => 'new', 'product' => 'casino'],
    'privacy-main-new-notification' => ['channel' => 'app', 'type' => 'new', 'product' => 'casino'],
    'privacy-main-new-sms' => ['channel' => 'sms', 'type' => 'new', 'product' => 'casino'],
    
    // Promotional communications - casino
    'privacy-main-promo-email' => ['channel' => 'email', 'type' => 'promotions', 'product' => 'casino'],
    'privacy-main-promo-notification' => ['channel' => 'app', 'type' => 'promotions', 'product' => 'casino'],
    'privacy-main-promo-sms' => ['channel' => 'sms', 'type' => 'promotions', 'product' => 'casino'],
    
    // Status communications
    'privacy-main-status-email' => ['channel' => 'email', 'type' => 'updates', 'product' => null],
    'privacy-main-status-notification' => ['channel' => 'app', 'type' => 'updates', 'product' => null],
    'privacy-main-status-sms' => ['channel' => 'sms', 'type' => 'updates', 'product' => null]
];

// Create a log file for unknown/unmapped settings
$unknownSettingsLog = __DIR__ . "/unmapped_settings.log";
$unknownSettingsCount = 0;
file_put_contents($unknownSettingsLog, "-- Unmapped settings log --\n", FILE_APPEND);

// Function to convert value to boolean for opt_in
function convertToOptIn($value) {
    // Assuming common values like 'true', 'yes', '1', etc. for opt-in
    if (in_array(strtolower(trim($value)), ['true', 'yes', '1', 'on', 'enabled'])) {
        return true;
    }
    return false;
}

// Process each shard
$shards = $sql->getShards();
if (empty($shards)) {
    echo "No shards found. Running on single database...\n";
    processShard($sql);
} else {
    echo "Found " . count($shards) . " shards. Processing each shard...\n";
    $sql->loopShardsSynced(function($db, $shard, $id) {
        echo "Processing shard #$id...\n";
        processShard($db);
    });
}

echo "Migration completed successfully!\n";
echo "Total unmapped settings found: $unknownSettingsCount\n";
echo "Check $unknownSettingsLog for details.\n";

/**
 * Process data migration for a single shard or database connection
 * 
 * @param SQL $db The database connection
 */
function processShard($db) {
    global $mappings, $unknownSettingsLog, $unknownSettingsCount;
    
    // Set batch size to prevent memory issues
    $batchSize = 1000;
    $processedCount = 0;
    $totalProcessed = 0;
    $migratedCount = 0;
    $skippedCount = 0;
    
    // Get the current timestamp for updated_at field
    $currentTimestamp = date('Y-m-d H:i:s');
    
    echo "Fetching settings that need to be migrated...\n";
    
    // Track unique unmapped settings to avoid duplicate logging
    $loggedUnmappedSettings = [];
    
    // Process in batches to avoid memory issues
    while (true) {
        $query = "SELECT id, user_id, setting, value, created_at FROM users_settings 
                  LIMIT $totalProcessed, $batchSize";
        
        $rows = $db->loadArray($query);
        
        if (empty($rows)) {
            break; // No more data to process
        }
        
        $processedCount = count($rows);
        
        foreach ($rows as $row) {
            $setting = $row['setting'];
            
            // Check if this setting has a mapping
            if (isset($mappings[$setting])) {
                // Skip if mapping is marked as unknown
                if ($mappings[$setting] === '??') {
                    $skippedCount++;
                    
                    // Log unique unmapped settings (once per setting name)
                    if (!isset($loggedUnmappedSettings[$setting])) {
                        $logEntry = date('Y-m-d H:i:s') . " - Setting: '$setting' is marked as unknown mapping (??)\n";
                        file_put_contents($unknownSettingsLog, $logEntry, FILE_APPEND);
                        $loggedUnmappedSettings[$setting] = true;
                        $unknownSettingsCount++;
                    }
                    continue;
                }
                
                // Prepare data for insertion with new product field
                $data = [
                    'user_id' => $row['user_id'],
                    'channel' => $mappings[$setting]['channel'],
                    'type' => $mappings[$setting]['type'],
                    'opt_in' => convertToOptIn($row['value']),
                    'updated_at' => $currentTimestamp
                ];
                
                // Add product field if it's not null
                if ($mappings[$setting]['product'] !== null) {
                    $data['product'] = $mappings[$setting]['product'];
                }
                
                // Construct the existingQuery differently based on whether product is null or not
                if ($mappings[$setting]['product'] === null) {
                    $existingQuery = "SELECT id FROM users_privacy_settings 
                                     WHERE user_id = {$row['user_id']} 
                                     AND channel = '{$mappings[$setting]['channel']}' 
                                     AND type = '{$mappings[$setting]['type']}'
                                     AND product IS NULL";
                } else {
                    $existingQuery = "SELECT id FROM users_privacy_settings 
                                     WHERE user_id = {$row['user_id']} 
                                     AND channel = '{$mappings[$setting]['channel']}' 
                                     AND type = '{$mappings[$setting]['type']}'
                                     AND product = '{$mappings[$setting]['product']}'";
                }
                
                $existing = $db->loadAssoc($existingQuery);
                
                if ($existing) {
                    // Update existing entry
                    $db->updateArray('users_privacy_settings', $data, ['id' => $existing['id']]);
                } else {
                    // Insert new entry
                    $db->insertArray('users_privacy_settings', $data);
                }
                
                $migratedCount++;
            } else {
                // This is a completely unknown setting not listed in our mappings
                $skippedCount++;
                
                // Log unique unmapped settings (once per setting name)
                if (!isset($loggedUnmappedSettings[$setting])) {
                    $logEntry = date('Y-m-d H:i:s') . " - Setting: '$setting' was not found in mapping table\n";
                    file_put_contents($unknownSettingsLog, $logEntry, FILE_APPEND);
                    $loggedUnmappedSettings[$setting] = true;
                    $unknownSettingsCount++;
                }
            }
        }
        
        $totalProcessed += $processedCount;
        echo "Processed $totalProcessed records, Migrated: $migratedCount, Skipped: $skippedCount...\n";
        
        // If we processed less than the batch size, we're done
        if (count($rows) < $batchSize) {
            break;
        }
    }
    
    echo "Completed processing shard. Total: $totalProcessed, Migrated: $migratedCount, Skipped: $skippedCount\n";
}