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

        // поиск идентификатора клиента в CRM
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
                $contact = $order['telephone'] ? $order['telephone'] : $order['email'];
                $clid = $this->SalesmanClient->searchClientClid($contact);
            } catch (\Exception $e) {
                $this->log->write($e->__toString());
                return;
            }
        }

        if (!$clid) {
            $clid = $this->newCustomerFromOrder($order, sprintf("-%s", $orderId));
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

        $this->load->model('account/customer');
        $this->load->model('account/address');
        $customer = $this->model_account_customer->getCustomer($customerId);
        $address = $this->model_account_address->getAddress($customer['address_id']);

        try {
            $this->newCustomer($customer, ($address ? $address : null));
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
     * Добавить пользователя в CRM из заказа
     *
     * @throws \Exception
     *
     * @param array $order
     * @param string|null $customerId
     * @return string
     */
    private function newCustomerFromOrder(array $order, ?string $customerId): string
    {
        $address = sprintf(
            "%s%s, %s, %s, %s%s",
            ($order['shipping_postcode'] ? $order['shipping_postcode'] . ' ' : ''),
            $order['shipping_country'],
            $order['shipping_zone'],
            $order['shipping_city'],
            $order['shipping_address_1'],
            (
                $order['shipping_address_2']
                ? sprintf(" (%s)", $order['shipping_address_2'])
                : $order['shipping_address_2']
            )
        );
        $client = [
            'title' => $order['lastname'] . ' ' . $order['firstname'],
            'type' => 'person',
            'address' => $address,
            'phone' => $order['telephone'],
            'mail_url' => $order['email'],
            "clientpath" => $_SERVER['HTTP_HOST']
        ];

        $client['uid'] = ($customerId ? $customerId : '0');

        $clid = $this->SalesmanClient->newClient($client);

        return $clid;
    }

    /**
     * Добавить пользователя в CRM
     *
     * @throws \Exception
     *
     * @param array $customer
     * @param array|null $address
     * @return string
     */
    private function newCustomer(array $customer, array $address = null): string
    {
        $addressStr = '';
        if ($address) {
            $addressStr = sprintf(
                "%s%s, %s, %s, %s%s",
                ($customer['postcode'] ? $customer['postcode'] . ' ' : ''),
                $customer['country'],
                $customer['zone'],
                $customer['city'],
                $customer['address_1'],
                (
                    $customer['address_2']
                    ? sprintf(" (%s)", $customer['address_2'])
                    : $customer['address_2']
                )
            );
        }

        $client = [
            'title' => $customer['lastname'] . ' ' . $customer['firstname'],
            'type' => 'person',
            'uid' => $customer['customer_id'],
            'address' => $addressStr,
            'phone' => $customer['telephone'],
            'mail_url' => $customer['email'],
            "clientpath" => $_SERVER['HTTP_HOST']
        ];

        $clid = $this->SalesmanClient->newClient($client);

        return $clid;
    }
}
