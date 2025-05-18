<?php

declare(strict_types=1);

namespace Videoslots\User\Services;

class UpdatePrivacyDashboardSettingsService
{
    private $compareFalse = 'off';
    /**
     * @param bool $isApi
     * @param array $data
     *
     * @return void
     */
    public function updatePrivacyDashboardSettings(bool $isApi, array $data): void
    {
        /** @var \DBUserHandler $userHandler */
        $userHandler = phive('DBUserHandler');

        $user = empty(cu()) ? cuRegistration() : cu();
        $compareValue = $isApi ?: 'on';

        foreach ($userHandler->getDataFormPrivacyForm() as $setting) {
            $this->updateSettings($user, $data, $setting, $compareValue);
        }
    }

    /**
     * @param \DBUser $user
     * @param array $data
     * @param string $setting
     * @param mixed $compareValue
     *
     * @return void
     */
    private function updateSettings(\DBUser $user, array $data, string $setting, $compareValue): void {
        if (isset($data[$setting]) && $data[$setting] == $compareValue) {
            $user->setSetting($setting, 1, true, 0, '', false);
        } else if (isset($data[$setting]) && $data[$setting] == $this->compareFalse) {
            $user->setSetting($setting, 0, true, 0, '', true);
        } else {
            $user->deleteSetting($setting);
        }
    }
}
