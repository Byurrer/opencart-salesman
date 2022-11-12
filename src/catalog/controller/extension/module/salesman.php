<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class ControllerExtensionModuleSalesman extends Controller
{
    public function __construct($registry)
    {
        parent::__construct($registry);

        try {
            $this->load->library('SalesmanClient');
        } catch (Exception $e) {
            throw $e;
        }
    }

    // *********************************************************************

    /**
     * Обработчик события добавления истории заказа
     *
     * @param string $route
     * @param array $args
     * @return void
     */
    public function eventAddOrderHistoryAfter($route, $args)
    {
        if (!$this->isEnableModule()) {
            return;
        }

        $orderId  = $args[0];

        // существует ли такая сделка
        try {
            if ($this->SalesmanClient->existsDeal($orderId)) {
                return;
            }
        } catch (\Exception $e) {
            $this->log->write($e->__toString());
            return;
        }

        $this->load->model('checkout/order');
        $order = $this->model_checkout_order->getOrder($orderId);

        // поиск идентфикиатора клиента в CRM
        $clid = null;
        if ($order['customer_id'] > 0) {
            try {
                $clid = $this->SalesmanClient->getClientClid($order['customer_id']);
            } catch (\Exception $e) {
                $this->log->write($e->__toString());
                return;
            }
        } else {
            try {
                $clid = $this->SalesmanClient->searchClientClid($order['customer_id']);
            } catch (\Exception $e) {
                $this->log->write($e->__toString());
                return;
            }
        }

        if (!$clid) {
            $clid = $this->newCustomer($order, sprintf("-%s", $orderId));
        }

        // спецификация
        $orderItems = $this->model_checkout_order->getOrderProducts($orderId);
        $items = [];
        foreach ($orderItems as $item) {
            $items[] = [
                'title' => $item['name'] . '(' . $item['model'] . ')',
                'kol' => $item['quantity'],
                'price' => $item['price'],
                'price_in' => $item['price'],
                'nds' => $item['tax'],
            ];
        }

        $orderTotals = $this->model_checkout_order->getOrderTotals($orderId);
        foreach ($orderTotals as $total) {
            if ($total['code'] == 'shipping') {
                $items[] = [
                    'title' => $total['title'],
                    'kol' => 1,
                    'price' => $total['value'],
                    'price_in' => $total['value'],
                ];
            }
        }

        // сделка
        $deal = [
            'uid' => $orderId,
            'title' => 'Заказ с сайта #' . $orderId,
            'clid' => $clid,
            'adres' => sprintf(
                "(%s) %s, %s, %s, %s",
                $order['shipping_postcode'],
                $order['shipping_country'],
                $order['shipping_zone'],
                $order['shipping_city'],
                $order['shipping_address_1']
            ),
            'speka' => $items
        ];

        try {
            $this->SalesmanClient->newDeal($deal);
        } catch (\Exception $e) {
            $this->log->write($e->__toString());
        }
    }

    /**
     * Обработчик добавления нового пользователя (регистрация)
     *
     * @param string $route
     * @param array $args
     * @param string $customerId
     * @return void
     */
    public function eventAddCustomerAfter($route, $args, $customerId)
    {
        if (!$this->isEnableModule()) {
            return;
        }

        try {
            $this->newCustomer($args[0], $customerId);
        } catch (\Exception $e) {
            $this->log->write($e->__toString());
        }
    }

    //######################################################################
    // PRIVATE
    //######################################################################

    /**
     * Включен ли модуль
     *
     * @return boolean
     */
    private function isEnableModule()
    {
        $settings = $this->model_setting_setting->getSetting("module_salesman");

        return boolval($settings['module_salesman_status']);
    }

    /**
     * Добавить пользователя в CRM
     *
     * @throws \Exception
     *
     * @param array $cusomer
     * @param string|null $customerId
     * @return string
     */
    private function newCustomer(array $cusomer, ?string $customerId): string
    {
        $client = [
            'title' => $cusomer['lastname'] . ' ' . $cusomer['firstname'],
            'phone' => $cusomer['telephone'],
            'mail_url' => $cusomer['email'],
            "clientpath" => $_SERVER['HTTP_HOST']
        ];

        $client['uid'] = ($customerId ? $customerId : '0');

        $clid = $this->SalesmanClient->newClient($client);

        return $clid;
    }
}
