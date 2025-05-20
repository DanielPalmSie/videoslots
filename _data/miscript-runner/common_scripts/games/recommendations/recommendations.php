<?php

/**
 * Collects all data from players, bets and games and uploads S3 bucket managed by Zingbrain
 * @return void
 * @throws Exception
 */
function collectFullData() {
    if (!phive('BrandedConfig')->isProduction()) {
        throw new Exception("Data collection should only be done in production");
    }
    try {
        echo "CONTENTS OF S3 BUCKET BEFORE: \n";
        phive('GamesRecommendations')->listContents();
        echo "Starting Full data collection for GAMES RECOMMENDATIONS\n";
        phive('GamesRecommendations')->collectFullData();
        echo "CONTENTS OF S3 BUCKET AFTER: \n";
        phive('GamesRecommendations')->listContents();
        echo "Competed full data collection \n";
    } catch (Exception $e) {
        echo "ERROR collecting full data: " . $e->getMessage();
        throw $e;
    }
}

/**
 * Collects all data from players, bets and games and uploads S3 bucket managed by Zingbrain
 * @return void
 * @throws Exception
 */
function collectDailyData() {
    if (!phive('BrandedConfig')->isProduction()) {
        throw new Exception("Data collection should only be done in production");
    }
    try {
        echo "CONTENTS OF S3 BUCKET BEFORE: \n";
        phive('GamesRecommendations')->listContents();
        echo "Starting Daily data collection for GAMES RECOMMENDATIONS \n";
        phive('GamesRecommendations')->collectDailyData();
        echo "CONTENTS OF S3 BUCKET AFTER: \n";
        phive('GamesRecommendations')->listContents();
        echo "Competed daily data collection\n";
    } catch (Exception $e) {
        echo "ERROR collecting daily data: " . $e->getMessage();
        throw $e;
    }
}

function downloadFilesFromS3($dir = '/tmp/data') {
    try {
        echo "Downloading files from S3\n";
        $result = phive('GamesRecommendations')->downloadFilesFromS3($dir);
        if ($result) {
            echo "Files downloaded successfully\n";
        } else {
            echo "Failed to download files\n";
        }
    } catch (Exception $e) {
        echo "ERROR downloading files from S3: " . $e->getMessage();
    }
}
