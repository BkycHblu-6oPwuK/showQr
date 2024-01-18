<?php

namespace Itb\qrShow;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Bitrix\Main\Engine\Contract\Controllerable;

class show extends \CBitrixComponent implements Controllerable
{
    public function executeComponent()
    {
        if (!$this->arParams['QR_URL']) {
            ShowError('Не передан url');
            return;
        }
        if ($this->startResultCache()) {
            $this->arResult = $this->setData();
            $this->includeComponentTemplate();
        }
    }

    public function configureActions(): array
    {
        return [
            'checkPayment' => [
                'prefilters' => [],
            ],
        ];
    }

    /**
     * Заполняет $arResult
     * @return string
     */

    public function setData(): array
    {
        $data = [];
        $data['QR_URL'] = $this->arParams['QR_URL'];
        $data['QR'] = base64_encode($this->generate());
        if (!empty($this->arParams['CHECK_PAYMENT']) && $this->arParams['CHECK_PAYMENT'] == 'Y') {
            $data['CHECK_PAYMENT'] = $this->arParams['CHECK_PAYMENT'] == 'Y' ? true : false;
            $data['FILE_CHECK_PAYMENT'] = !empty($this->arParams['FILE_CHECK_PAYMENT']) ? $this->arParams['FILE_CHECK_PAYMENT'] : "";
            $data['METHOD_CHECK_PAYMENT'] = !empty($this->arParams['METHOD_CHECK_PAYMENT']) ? $this->arParams['METHOD_CHECK_PAYMENT'] : "";
            $data['REDIRECT_URL'] = !empty($this->arParams['REDIRECT_URL']) ? $this->arParams['REDIRECT_URL'] : "";
            $data['ISSET_ORDER_ID'] = !empty($this->arParams['REDIRECT_URL']) ? (bool) (strpos($data['REDIRECT_URL'], '#ORDER_ID#') !== false) : false;
            $data['REDIRECT_URL'] = $data['ISSET_ORDER_ID'] ? str_replace('#ORDER_ID#', '', $data['REDIRECT_URL']) : $data['ISSET_ORDER_ID'];
        }
        return $data;
    }

    /**
     * Генирирует qr код из ссылки используя библиотеку BaconQrCode
     * @return string
     */

    public function generate(): string
    {
        $string = "";
        if (!empty($this->arParams['QR_URL'])) {
            $renderer = new ImageRenderer(
                new RendererStyle(200),
                new ImagickImageBackEnd()
            );
            $writer = new Writer($renderer);

            $string = $writer->writeString($this->arParams['QR_URL']); // base64_encode()

        }
        return $string;
    }

    /**
     * Проверяет статус оплаты подключая файл и вызывая функцию
     * @param string $requestData
     * @return array
     */

    public function checkPaymentAction($requestData): array
    {
        $params = static::parseData($requestData);
        $result = [];

        if (!empty($params['file']) && !empty($params['method']) && !empty($params['url'])) {
            $file = __DIR__ . "/{$params['file']}";
            include $file;
            $result = call_user_func($params['method'], $params['url']);
        }

        return $result;
    }

    /**
     * @param string $requestData
     * @return array
     */

    public static function parseData($requestData): array
    {
        $params = [];
        preg_match_all("/(\w+)='(.*?)'/", $requestData, $matches);
        $params = array_combine($matches[1], $matches[2]);
        return $params;
    }
}
