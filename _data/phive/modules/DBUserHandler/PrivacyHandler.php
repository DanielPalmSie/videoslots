<?php

/**
 * Class PrivacyHandler
 * A helper class to manage privacy settings for users
 */
class PrivacyHandler extends PhModule {

    const CHANNEL_EMAIL     = 'email';
    const CHANNEL_SMS       = 'sms';
    const CHANNEL_APP       = 'app';
    const CHANNEL_DIRECT    = 'direct_mail';
    const CHANNEL_VOICE     = 'voice';
    const CHANNEL_CALLS     = 'calls';

    const CHANNELS = [
        self::CHANNEL_EMAIL, self::CHANNEL_SMS, self::CHANNEL_APP,
        self::CHANNEL_DIRECT, self::CHANNEL_VOICE, self::CHANNEL_CALLS
    ];

    const TYPE_NEW          = 'new';
    const TYPE_PROMOTIONS   = 'promotions';
    const TYPE_UPDATES      = 'updates';
    const TYPE_OFFERS       = 'offers';

    const TYPES = [
        self::TYPE_NEW, self::TYPE_PROMOTIONS, self::TYPE_UPDATES, self::TYPE_OFFERS
    ];

    const PRODUCT_CASINO    = 'casino';
    const PRODUCT_SPORTS    = 'sports';
    const PRODUCT_BINGO     = 'bingo';
    const PRODUCT_POKER     = 'poker';

    const PRODUCTS = [
        self::PRODUCT_CASINO,
        self::PRODUCT_SPORTS,
        self::PRODUCT_BINGO,
        self::PRODUCT_POKER
    ];

    const TRANSACTIONAL_TYPE = 'transactional';

    const PRIVACY_TABLE = 'users_privacy_settings';

    const CHANNEL   = 'channel';
    const TYPE      = 'type';
    const PRODUCT   = 'product';

    private array $trigger_map = [];

    /**
     * Check if this combination of $channel, $type & $trigger
     * require consent
     *
     * @param string $channel
     * @param string $trigger
     * @return bool
     */
    public function requiresConsent(string $channel, string $trigger): bool
    {
        $settings = $this->getTriggerSettings($channel, $trigger);
        return $settings !== null && $settings[self::TYPE] !== self::TRANSACTIONAL_TYPE;
    }

    /**
     * Try to get the trigger type from the configs
     *
     * @param string $channel
     * @param string $trigger
     * @return array|null
     */
    public function getTriggerSettings(string $channel, string $trigger): ?array
    {
        $channel = strtolower($channel);
        if (!in_array($channel, self::CHANNELS)) return null;

        $trigger = strtolower($trigger);

        $map = $this->getTriggerMap($channel);
        if (isset($map[$trigger])) return $this->parseSettingString("{$channel}.{$map[$trigger]}");
        else phive('Logger')->warning("Trigger '{$trigger}' has not been configured for the '{$channel}' channel");

        return null;
    }

    /**
     * Checks if the specified user can receive
     * a specific trigger via a channel
     *
     * @param User|int|null $user
     * @param string $channel
     * @param string $trigger
     * @return bool
     */
    public function canReceiveTrigger($user, string $channel, string $trigger): bool
    {
        $settings = $this->getTriggerSettings($channel, $trigger);
        if ($settings === null) return false;

        if ($settings[self::TYPE] === self::TRANSACTIONAL_TYPE) return true;

        return $this->getPrivacySetting($user, $settings);
    }

    /**
     * Checks if the provided $user has any privacy settings
     *
     * @param User|int|null $user
     * @return bool
     */
    public function hasPrivacySettings($user): bool
    {
        $user = cu($user);
        if (empty($user)) return false;

        return (int) phive('SQL')
                ->sh($user->getId())
                ->getValue(sprintf(
                    "SELECT COUNT(*) FROM %s WHERE user_id = '%d'",
                    self::PRIVACY_TABLE, $user->getId()
                )) > 0;
    }

    /**
     * Get a privacy setting
     *
     * @param User|int|null $user
     * @param array|string $setting
     * @return bool
     */
    public function getPrivacySetting($user, $setting): bool
    {
        $user = cu($user);
        if (empty($user)) return false;

        if (!is_array($setting)) $setting = $this->parseSettingString($setting);

        $tbl    = self::PRIVACY_TABLE;
        $where  = $this->getSettingWhereQuery($setting, $user->getId());
        return (int) phive('SQL')
                ->sh($user->getId())
                ->getValue("SELECT opt_in FROM {$tbl} WHERE {$where}") === 1;
    }

    /**
     * Set a privacy setting
     *
     * @param User|int|null $user
     * @param string|array $setting - ['channel' => '', 'type' => '', 'product' => '']
     * @param bool $opt
     * @return void
     */
    public function setPrivacySetting($user, $setting, bool $opt = true): void
    {
        $user = cu($user);
        if (empty($user)) return;

        if (!is_array($setting)) $setting = $this->parseSettingString($setting);

        if (empty($setting[self::CHANNEL]) || empty($setting[self::TYPE])) {
            phive('Logger')->error('Cannot set user privacy setting without channel or type', [
                'user' => $user, 'setting' => $setting, 'opt' => $opt
            ]);
            return;
        }

        $data = [
            'user_id'       => (int) $user->getId(),
            'channel'       => $setting[self::CHANNEL],
            'type'          => $setting[self::TYPE],
            'product'       => (empty($setting[self::PRODUCT]) ? null : $setting[self::PRODUCT]),
            'opt_in'        => $opt ? 1 : 0
        ];

        $exists = $this->getSettingID($setting, $user->getId());
        phive('SQL')->sh($user->getId())->insertArray(self::PRIVACY_TABLE, $data, ($exists) ? ['id' => $exists] : null);

        // @todo: Remove once legacy settings are no longer in use
        $legacy = $this->getSetting('legacy_map', []);
        $key    = $this->parseSettingArray($setting);

        if (isset($legacy[$key])) $user->setSetting($legacy[$key], $opt ? '1' : '0');
    }

    /**
     * @param User|int|null $user
     * @return array
     */
    public function getPrivacySettings($user): array
    {
        $user = cu($user);
        if (empty($user)) return [];

        // products
        $productOpts = phive('SQL')->sh($user->getId())->loadArray(sprintf(
            "SELECT channel, type, product, opt_in FROM %s WHERE user_id = %d AND product != '' AND product IS NOT NULL AND opt_in = 1",
            self::PRIVACY_TABLE, (int) $user->getId()
        ));

        // non products
        $typeOpts = phive('SQL')->sh($user->getId())->loadArray(sprintf(
            "SELECT channel, type, opt_in FROM %s WHERE user_id = %d AND (product = '' OR product IS NULL) AND opt_in = 1",
            self::PRIVACY_TABLE, (int) $user->getId()
        ));

        $map = [];

        foreach ($this->getAllConsentOptions() as $option) {
            if (empty($option[self::PRODUCT])) {
                $map[$option[self::CHANNEL]][$option[self::TYPE]] = false;
                foreach ($typeOpts as $typeOpt) {
                    if ($option[self::TYPE] === $typeOpt[self::TYPE]
                        && $option[self::CHANNEL] === $typeOpt[self::CHANNEL]
                    ) {
                        $map[$option[self::CHANNEL]][$option[self::TYPE]] = true;
                        break;
                    }
                }
            } else {
                $map[$option[self::CHANNEL]][$option[self::TYPE]][$option[self::PRODUCT]] = false;
                foreach ($productOpts as $productOpt) {
                    if ($option[self::TYPE] === $productOpt[self::TYPE]
                        && $option[self::CHANNEL] === $productOpt[self::CHANNEL]
                        && $option[self::PRODUCT] === $productOpt[self::PRODUCT]
                    ) {
                        $map[$option[self::CHANNEL]][$option[self::TYPE]][$option[self::PRODUCT]] = true;
                        break;
                    }
                }
            }
        }

        return $map;
    }

    /**
     * A helper function to set multiple settings in 1 go
     *
     * The $data array should follow the structure;
     * [
     *      ['<channel.type.product>' => bool],
     *      [[<setting key map>] => bool],
     *      ...,
     * ]
     *
     * @param User|int|null $user
     * @param array $data
     * @return void
     */
    public function setPrivacySettings($user, array $data): void
    {
        $user = cu($user);
        if (empty($user)) return;

        foreach ($data as $key => $opt) $this->setPrivacySetting($user, $key, (bool) $opt);
    }

    /**
     * Set all privacy setting to true|false
     *
     * @param User|int|null $user
     * @param bool $opt
     * @return void
     */
    public function setAllPrivacySettings($user, bool $opt = true): void
    {
        foreach($this->getAllConsentOptions() as $option) {
            $this->setPrivacySetting($user, $option, $opt);
        }
    }

    /**
     * Get the section for the privacy dashboard in the profile page
     * @todo: Refactor this to make it a bit cleaner, it was done in a rush
     *
     * @param User|int|null $user
     * @return array|array[]
     */
    public function getPrivacySectionsForHTML($user = null): array
    {
        if (empty($user)) $user = cu($user);
        if (empty($user)) return [];

        $settings   = $this->getPrivacySettings($user);
        $channels   = [self::CHANNEL_EMAIL, self::CHANNEL_SMS, self::CHANNEL_APP];

        $map = [
            self::TYPE_PROMOTIONS => [
                'headline'      => t('privacy.settings.promo.and.rewards.headline'),
                'subheadline'   => t('privacy.settings.promo.and.rewards.subheadline'),
            ],

            self::TYPE_NEW => [
                'headline'      => t('privacy.settings.new.games.features.updates.headline'),
                'subheadline'   => t('privacy.settings.new.games.features.updates.subheadline')
            ],

            self::TYPE_UPDATES => [
                'headline'      => t('privacy.settings.status.updates.headline'),
                'subheadline'   => t('privacy.settings.status.updates.subheadline'),
                'opt'           => $this->makeOptArray(self::TYPE_UPDATES, '', $channels, $user, $settings)
            ]
        ];

        foreach ([self::TYPE_PROMOTIONS, self::TYPE_NEW] as $type) {
            foreach (self::PRODUCTS as $product) {
                $map[$type]['products'][$product] = [
                    'opt' => $this->makeOptArray($type, $product, $channels, $user, $settings)
                ];
            }
        }

        foreach($map as $type => $settings) {
            $map[$type]['optOutAll'] = true;

            if (isset($settings['products'])) {
                foreach ($settings['products'] as $pSettings) {
                    foreach ($pSettings['opt'] as $channel) {
                        if ($channel) {
                            $map[$type]['optOutAll'] = false;
                        }
                    }
                }
            } else {
                foreach ($settings['opt'] as $opt) if ($opt) $map[$type]['optOutAll'] = false;
            }
        }

        return $map;
    }

    /**
     * Get the trigger map for $channel, or all if not specified
     *
     * @param string|null $channel
     * @return array
     */
    public function getTriggerMap(string $channel = null): array
    {

        // Cache this config to avoid reevaluating every time
        if (empty($this->trigger_map)) {
            foreach (self::CHANNELS as $_channel) {
                $settings = $this->getSetting("{$_channel}_triggers", []);
                if (empty($settings) || !is_array($settings)) continue;

                $map = [];

                foreach ($settings as $type => $list) {
                    if (empty($list) || !is_array($list)) continue;
                    $hasProducts = count(array_filter(array_keys($list), 'is_string')) > 0;

                    if ($hasProducts) {
                        foreach ($list as $product => $triggers) {
                            foreach ($triggers as $trigger) $map[$trigger] = "{$type}.{$product}";
                        }
                    } else {
                        foreach ($list as $trigger) $map[$trigger] = $type;
                    }
                }

                $this->trigger_map[$_channel] = $map;
            }
        }

        if (!empty($channel)) {
            return (isset($this->trigger_map[$channel])) ? $this->trigger_map[$channel] : [];
        }

        return $this->trigger_map;
    }

    /**
     * @param array $fdata
     * @param User|int|null $user
     * @return void
     */
    public function saveFormData(array $fdata, $user = null): void
    {
        $user = empty($user) ? cu() : cu($user);
        if (!$user) {
            $user = cuRegistration();
        }
        if (empty($user)) return;

        foreach ($this->getAllConsentOptions() as $option) {
            $key = $this->parseSettingArray($option);
            $this->setPrivacySetting(
                $user,
                $option,
                isset($fdata[$key]) && ($fdata[$key] == 'on' || $fdata[$key] == '1' || $fdata[$key] === true)
            );
        }
    }

    /**
     * @param string $setting
     * @return array
     */
    public function parseSettingString(string $setting): array
    {
        $parts = explode('.', $setting);
        return [
            self::CHANNEL   => empty($parts[0]) ? null : strtolower($parts[0]),
            self::TYPE      => empty($parts[1]) ? null : strtolower($parts[1]),
            self::PRODUCT   => empty($parts[2]) ? null : strtolower($parts[2])
        ];
    }

    /**
     * @param array $setting
     * @return string
     */
    public function parseSettingArray(array $setting): string
    {
        return implode('.', array_filter($setting));
    }

    /**
     * Get the search query for a privacy setting
     *
     * @param array $setting
     * @param int|null $user_id
     * @return string
     */
    public function getSettingWhereQuery(array $setting, int $user_id = null): string
    {
        $query[] = "channel = '" . strtolower($setting[self::CHANNEL]) . "'";
        $query[] = "type = '" . strtolower($setting[self::TYPE]) . "'";
        $query[] = empty($setting[self::PRODUCT])
            ? "(product IS NULL OR product = '')"
            : "product = '" . strtolower($setting[self::PRODUCT]) . "'";

        if (!empty($user_id)) $query[] = "user_id = {$user_id}";

        return implode(' AND ', $query);
    }

    /**
     * Get all consent options
     *
     * @return array
     */
    public function getAllConsentOptions(): array
    {
        $options = [];

        foreach ($this->getSetting('consent_map') as $key) {
            $options[] = $this->parseSettingString($key);
        }

        return $options;
    }

    /**
     * Get the setting id if it exists else return 0
     *
     * @param array $setting
     * @param int $user_id
     * @return int
     */
    private function getSettingID(array $setting, int $user_id): int
    {
        $query  = $this->getSettingWhereQuery($setting, $user_id);
        $tbl    = self::PRIVACY_TABLE;
        return (int) phive('SQL')->sh($user_id)->getValue("SELECT id FROM {$tbl} WHERE {$query}");
    }

    /**
     * Get opt-in settings for front-end
     *
     * @param string $type - The type to fetch
     * @param string|null $product - The product to fetch
     * @param array $channels - The list of channels to check
     * @param $user - Pass this to avoid reloading the user
     * @param array $settings - Pass this to avoid reloading settings
     * @return array
     */
    private function makeOptArray(string $type, string $product, array $channels, $user, array $settings): array
    {
        if (empty($user)) $user = cu($user);
        if (empty($user)) return [];

        if (empty($settings)) $settings = $this->getPrivacySettings($user);

        $arr = [];

        foreach ($channels as $channel) {
            $arr[$channel] = ($product)
                ? $settings[$channel][$type][$product]
                : $settings[$channel][$type];
        }

        return $arr;
    }
}
