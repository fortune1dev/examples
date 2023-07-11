<?php
\Bitrix\Main\Loader::includeModule('iblock');
\Bitrix\Main\Loader::includeModule('catalog');

class Store {
    private int $id;
    private array $stoks = array();

    public function __construct(int $storeID) {
        $this->id = $storeID;
    }

    public function getId() {
        return $this->id;
    }

    public function setQuantity(int $productId, int $quantity) {

    }

    public function getQuantity(string $uuid) {

    }

}