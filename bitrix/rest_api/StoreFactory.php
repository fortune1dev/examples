<?php

use Store;

final class StoreFactory {

    /**
     * метод вернет либо id найденного склада, либо id дефолтного склада
     */
    private static function findStoreByUuid(string $uuid): int {
        return 1;
    }

    /**
     * метод возвращается объект Store 
     */
    static function makeStore(int $stockUUID): Store {
        $stockID = self::findStoreByUuid($stockUUID);
        return new Store($stockID);

    }
}