<?php
use \Bitrix\Main\Application;

class Auth {
    protected bool $_authorized = false;
    protected string $_method = '';
    protected string $_token = '';
    protected string $_user = '';
    protected string $_password = '';

    public function __construct(Request $request, $identity) {
        if (is_array($identity)) {
            // Basic авторизация. Сверяем данные из $identity с данными $request, если мимо, то сразу выходим.
            $this->_method = 'Basic';

            // code...
        } elseif (is_string($identity)) {
            // Bearer авторизация. Сверяем данные из $identity с данными $request, если мимо, то сразу выходим.
            $this->_method = 'Bearer';

            // code...
        }

        $this->_authorized = true;
    }

    public function isAuthorized(): bool {
        return $this->_authorized;
    }
}