<?= "<?php ";?>

namespace RgEvaluation\Factory;

use DBUser;

class <?= $dataSupplierClassName ?> implements TriggerDataSupplier
{
    private const TRIGGER_NAME = "<?= $triggerName ?>";
    private DBUser $user;
    private $arf;
    private $uh;

    public function __construct(DBUser $user)
    {
        $this->user = $user;
        $this->arf = phive('Cashier/Arf');
        $this->uh = phive('DBUserHandler');
    }

    public function getRgPopupVariables(): array
    {
        return $this->getVariables();
    }

    public function getUserCommentsVariables(): array
    {
        return $this->getVariables();
    }

    private function getVariables(): array
    {
        return [];
    }
}
