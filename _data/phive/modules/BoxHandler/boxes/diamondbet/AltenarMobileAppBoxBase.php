<?php

require_once __DIR__ . '/AltenarBoxBase.php';

class AltenarMobileAppBoxBase extends AltenarBoxBase
{
    public function init(): void
    {
        if (empty($_GET['auth_token'])) {
            http_response_code(401);
            exit();
        }
    }

    public function printHTML(): void
    {
        $this->loadSportsbook(cu());
    }
}
