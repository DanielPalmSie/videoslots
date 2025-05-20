<?= "<?php ";?>

namespace RgEvaluation\Factory;

class <?= $dynamicVariableSupplierClassName ?> extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new <?= $dataSupplierClassName ?>($this->user);
    }
}
