<?php

use Laraphive\Domain\Cashier\DataTransferObjects\MakeDepositData;
use Laraphive\Domain\Cashier\DataTransferObjects\MakeDepositResponse;

require_once __DIR__ . '/ApplePay.php';

/**
 * @api
 */
final class Deposit
{
    private CasinoCashier $casinoCashier;

    public function __construct()
    {
        $this->casinoCashier = new CasinoCashier();
    }

    /**
     * @return array{'errors': array<string>, 'cents': int}
     */
    public function phiveInit(string $supplier, int $amount, int $userId, array $payload): array
    {
        $user = cuPl($userId);

        if (count($user->getBonusesToForfeitBeforeDeposit())) {
            return ['errors' => ['forfeit.deposit.blocked.error'], 'cents' => null];
        }

        if (!$this->casinoCashier->withdrawDepositAllowed($user, $supplier, 'deposit')) {
            return ['errors' => ['psp.supplier.not.active'], 'cents' => null];
        }

        // TODO: Temporary change - This should be removed once Phive is refactored to handle amounts in cents in [PM-1704]
        $amount = $payload['amountInCents'] ? $amount / 100 : $amount;

        list($err, $amount) = $this->casinoCashier->transferStart([], $user, $supplier, 'in', $amount);

        if (!empty($err)) {
            return ['errors' => $err, 'cents' => null];
        }

        // TODO: Temporary change - This should be removed once Phive is refactored to handle amounts in cents in [PM-1704]
        $cents = $amount * 100;

        list($res, $action) = $this->casinoCashier->checkOverLimits($user, $cents);
        if ($res) {
            return $action
                ? ['errors' => ['action' => $action]]
                : ['errors' => ['deposits.over.limit.html'], 'cents' => $cents];
        }

        $err_msg = lic('validateDeposit', [$user, 'bank', $payload], $user);
        if ($err_msg) {
            return ['errors' => [$err_msg], 'cents' => $cents];
        }

        return ['errors' => [], 'cents' => $cents];
    }

    public function phiveSync(string $supplier, int $cents, int $userId, array $args): void
    {
        rgLimits()->addPendingDeposit(cuPl($userId), $cents);
    }

    /**
     * @api endpoint for deposit
     *
     * @param \Laraphive\Domain\Cashier\DataTransferObjects\MakeDepositData $makeDepositData
     *
     * @return \Laraphive\Domain\Cashier\DataTransferObjects\MakeDepositResponse
     */
    public function makeDeposit(MakeDepositData $makeDepositData): MakeDepositResponse
    {
        $request = $this->createRequest($makeDepositData);
        $lang = phive('Localizer')->getCurNonSubLang();
        $res = $this->handle($lang, $request, true);

        if ($res['success']) {
            $response = MakeDepositResponse::createSuccess($res['result']);
        } else {
            $errors = is_array($res['errors']) ? $res['errors'] : [$res['errors']];
            $response = MakeDepositResponse::createError($errors);
        }

        return $response;
    }

    /**
     * @param string $lang
     * @param array $request
     * @param bool $isApi
     *
     * @return array
     */
    public function handle(string $lang, array $request, bool $isApi = false): array
    {
        if (!empty($lang)) {
            phive('Localizer')->setLanguage($lang, true);
        }

        $payment = new ApplePay($request['action'], $isApi);
        if ($payment->hasError()) {
            return $payment->failStop($payment->getError(), false);
        }

        // TODO: Temporary change - This should be removed once Phive is refactored to handle amounts in cents in [PM-1704]
        $request['amount'] = $request['amountInCents'] ? $request['amount'] / 100 : $request['amount'];

        $res = $payment->deposit($request);
        if ($payment->hasError()) {
            return $payment->failStop($payment->getError(), false);
        } elseif (is_array($res) && empty($res['success']) && empty($res['failover'])) {
            $payment->cashier->fireOnFailedDeposit($payment->u_obj, $payment->action);
        }

        if (!$payment->hasError() && !empty($request['bonus_code'])) {
            $payment->setReloadCode($request);
            $currency = $payment->u_obj->getCurrency();
            $amountInCents = $orgAmountInCents = (int)$request['amount'] * 100;

            phive('Bonuses')->handleReloadDeposit($payment->u_obj, $amountInCents, $orgAmountInCents, $currency);
        }

        return $payment->stop($res) ?? [];
    }

    /**
     * @param \Laraphive\Domain\Cashier\DataTransferObjects\MakeDepositData $makeDepositData
     *
     * @return array
     */
    private function createRequest(MakeDepositData $makeDepositData): array
    {
        $request = [
            'amount' => $makeDepositData->getAmount(),
            'action' => 'deposit',
            'supplier' => $makeDepositData->getPaymentMethod(),
            'bonus_code' => $makeDepositData->getBonusCode()
        ];

        return array_merge($request, $makeDepositData->getPayload());
    }
}
