Компонент генерирует qr код для пк версии и список банков с ссылками в приложение для телефонов.

Параметр "QR_URL" обязательный. Платежный модуль отдает url вида - https://qr.nspk.ru/AD100025R4EED18V9ELRCJIEA3JSD9C8?type=02&bank=100000000111&sum=956000&cur=RUB&crc=717D

Так же передаются параметры необходимые для проверки оплаты.
"CHECK_PAYMENT" => 'Y',
"FILE_CHECK_PAYMENT" => 'CheckPaymentForItsagency.php',
"METHOD_CHECK_PAYMENT" => '\Itb\qrShow\CheckPaymentForItsagency::check', 
"REDIRECT_URL" => '/account/orders/#ORDER_ID#'

FILE_CHECK_PAYMENT - Файл в котором идет проверка статуса.

\Itb\qrShow\CheckPaymentForItsagency::check - метод проверяет статус оплаты и проставляет статус оплачен в админке.
Работает с платежным модулем - itsagency.sbersbp.
Модуль возвращает только ссылку на оплату.
Сбербанк не возвращает статус оплаты, поэтому в модуле используется агент для проверки и выставления статуса.

Для генерации qr используется библиотека - Bacon/BaconQrCode
Так же необходима библиотека - DASPRiD/Enum

Для уставноки через composer:
composer require bacon/bacon-qr-code; composer require dasprid/enum

В шаблоне используется vue.js. Тестировал на версии 2.6.4. На версиях ниже может не работать, на версиях выше проблем быть не должно.
