<?php
namespace Itb\qrShow;

require_once "{$_SERVER['DOCUMENT_ROOT']}/bitrix/modules/itsagency.sbersbp/lib/entity/transaction.php";
require_once "{$_SERVER['DOCUMENT_ROOT']}/bitrix/modules/itsagency.sbersbp/lib/api.php";
require_once "{$_SERVER['DOCUMENT_ROOT']}/bitrix/modules/itsagency.sbersbp/lib/tmpfile.php";

use Bitrix\Sale\Order;
use Bitrix\Main\Loader;
use Itsagency\Sbersbp\Entity\TransactionTable;
use Itsagency\Sbersbp\Api;
use Bitrix\Sale\Payment;

class CheckPaymentForItsagency
{

    public function __construct()
    {
        Loader::includeModule('sale');
        Loader::includeModule('iblock');
        Loader::includeModule('catalog');
    }

    public static function check(string $url) : array
    {
        $result["paid"] = false;
        if(strlen(trim($url)) === 0){
            return $result;
        }
        $transactionResource = TransactionTable::getList([
            'filter' => [
                '!STATUS' => Api::getEndStatuses(),
                'URL' => $url,
            ],
            'select' => [
                '*',
                'ORDER_ID' => 'PAYMENT.ORDER_ID'
            ],
        ]);

        while ($transactionData = $transactionResource->fetch()) {
            $api = Api::createInstanceFromBusinessValues(
                (string)$transactionData['PAYMENT_CONSUMER_NAME'],
                (string)$transactionData['PAYMENT_PERSON_TYPE_ID']
            );
            $response = $api->getOrderStatus(
                (int)$transactionData['PAYMENT_ID'],
                (string)$transactionData['TRANSACTION_ID']
            );
            if ($response['code'] !== 200) {
                continue;
            }
            $statusCode = $response['data']['order_state'];
            if (!$statusCode) {
                $statusCode = 'RESPONSE_ERROR';
            }
            $update = TransactionTable::update(
                $transactionData['ID'],
                ['STATUS' => $statusCode]
            );
            if (!$update->isSuccess()) {
                continue;
            }
            $statusPosition = Api::getPositionFromStatus($statusCode);
            if ($statusPosition > 0) {
                $order_id = (int)$transactionData['ORDER_ID'];
                $order = Order::load($order_id);
                foreach ($order->getPaymentCollection() as $payment) {
                    /** @var Payment $payment */
                    if ((int)$payment->getId() === (int)$transactionData['PAYMENT_ID'] && !$payment->isPaid()) {
                        $payment->setPaid('Y');
                    }
                }
                $order->save();
                $result["paid"] = true;
                $result['order_id'] = $order_id;
            }
        }
        return $result;
    }
}