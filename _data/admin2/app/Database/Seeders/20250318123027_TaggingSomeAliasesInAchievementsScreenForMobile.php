<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class TaggingSomeAliasesInAchievementsScreenForMobile extends Seeder
{
    private Connection $connection;
    private array $aliases;
    private string $table;
    private string $tag;

    public function init()
    {
        $this->aliases = ['trophies.all', 'in.progress', 'trophies.not.completed'];
        $this->table = 'localized_strings_connections';
        $this->tag = 'mobile_app_localization_tag';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        foreach ($this->aliases as $alias)
        {
            $this->connection
                ->table($this->table)
                ->insert([
                    'target_alias' => $alias,
                    'bonus_code' => 0,
                    'tag' => $this->tag
                ]);
        }
    }

    public function down()
    {
        $this->connection
             ->table($this->table)
             ->whereIn('target_alias', $this->aliases)
             ->delete();
    }
}
