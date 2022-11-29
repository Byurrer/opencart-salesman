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
        $this->load->model('setting/setting');
    }

    /**
     * Генерация страницы настроек
     *
     * @return void
     */
    public function index()
    {
        $lang = $this->load->language('extension/module/salesman');
        $this->document->setTitle($this->language->get('doc_title'));

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->saveSettings();
        }

        // сборка данных страницы
        $data = array_merge(
            $lang,
            $this->getTemplateData(),
            $this->getFormData(),
            $this->getSettingsData(),
            $this->getStatusData()
        );

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            unset($this->session->data['settings_success']);
            unset($this->session->data['settings_error']);
        }

        $this->response->setOutput($this->load->view('extension/module/salesman', $data));
    }

    //**********************************************************************

    /**
     * Получить данные шаблона страницы
     *
     * @return array
     */
    public function getTemplateData()
    {
        $data = [];
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');
        $data['heading_title'] = $this->language->get('doc_title');
        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link(
                'common/dashboard',
                'token=' . $this->session->data['token'],
                true
            )
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link(
                'marketplace/extension',
                'token=' . $this->session->data['token'] . '&type=module',
                true
            )
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title_m'),
            'href' => $this->url->link(
                'extension/module/salesman',
                'token=' . $this->session->data['token'],
                true
            )
        ];

        return $data;
    }

    /**
     * Получить основные данные формы (кнопки, заголовок, action)
     *
     * @return array
     */
    public function getFormData()
    {
        $data = [];
        $data['button_save'] = $this->language->get('button_save');
        $data['settings_edit'] = $this->language->get('settings_edit');
        $data['action'] = $this->url->link(
            'extension/module/salesman',
            'token=' . $this->session->data['token'],
            true
        );
        $data['cancel'] = $this->url->link(
            'marketplace/extension',
            'token=' . $this->session->data['token'] . '&type=module',
            true
        );

        return $data;
    }

    /**
     * Получить данные настроек
     *
     * @return array
     */
    public function getSettingsData()
    {
        $settings = $this->model_setting_setting->getSetting("module_salesman");
        return $settings;
    }

    /**
     * Получить данные статусов
     *
     * @return array
     */
    public function getStatusData()
    {
        $data = [];

        // если было успешное изменение настроек - показываем сообщение
        if (isset($this->session->data['settings_success'])) {
            $data['settings_success'] = $this->language->get('settings_success');
            unset($this->session->data['settings_success']);
        } else {
            $data['settings_success'] = false;
        }

        // если есть ошибки - показываем
        if (isset($this->session->data['settings_error'])) {
            $data['error_warning'] = implode('<br/>', $this->session->data['settings_error']);
            unset($this->session->data['settings_error']);
        } else {
            $data['error_warning'] = false;
        }

        return $data;
    }

    //**********************************************************************

    /**
     * Валидация введенных настроек.
     * Описания ошибок доступны в $this->errors
     *
     * @return bool
     */
    public function validateSettings()
    {
        $settings = $this->request->post;

        if (!trim($settings['module_salesman_api_url'])) {
            $this->errors[] = 'Не заполнено поле URL';
        }

        if (!trim($settings['module_salesman_api_login'])) {
            $this->errors[] = 'Не заполнено поле Логин';
        }

        if (!trim($settings['module_salesman_api_token'])) {
            $this->errors[] = 'Не заполнено поле Токен';
        }

        if (count($this->errors)) {
            return false;
        }

        try {
            $this->SalesmanClient->test(
                $settings['module_salesman_api_url'],
                $settings['module_salesman_api_login'],
                $settings['module_salesman_api_token']
            );
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }

        return true;
    }

    /**
     * Сохранение настроек (с валидацией).
     * Описания ошибок доступны в $this->session->data['settings_error'].
     * Описание успеха в $this->session->data['settings_success']
     *
     * @return bool
     */
    public function saveSettings()
    {
        if ($this->validateSettings()) {
            $settings = $this->request->post;
            $this->model_setting_setting->editSetting('module_salesman', $settings);
            $this->session->data['settings_success'] = $this->language->get('settings_success');
            $this->response->redirect(
                $this->url->link(
                    'extension/module/salesman',
                    'token=' . $this->session->data['token'],
                    true
                )
            );
            return true;
        } else {
            $this->session->data['settings_error'] = $this->errors;
            return false;
        }
    }

    //######################################################################

    /**
     * Установка модуля
     *
     * @return void
     */
    public function install()
    {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('module_salesman', [
            'module_salesman_status' => '',
            'module_salesman_api_url' => '',
            'module_salesman_api_login' => '',
            'module_salesman_api_token' => '',
        ]);

        $this->load->model('extension/event');
        $this->model_extension_event->addEvent(
            'salesman',
            'admin/model/customer/customer/addCustomer/after',
            'extension/module/salesman/eventAddCustomerAfter'
        );

        $this->model_extension_event->addEvent(
            'salesman',
            'catalog/model/account/customer/addCustomer/after',
            'extension/module/salesman/eventAddCustomerAfter'
        );

        $this->model_extension_event->addEvent(
            'salesman',
            'catalog/model/checkout/order/addOrderHistory/after',
            'extension/module/salesman/eventAddOrderHistoryAfter'
        );
    }

    /**
     * Удаление модуля
     *
     * @return void
     */
    public function uninstall()
    {
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('module_salesman');
		
		$this->load->model('extension/event');
        $this->model_extension_event->deleteEvent('salesman');
    }

    //######################################################################

    /**
     * Обработчик события после добавления нового пользователя из админки
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

        $this->load->model('customer/customer');
        $customer = $this->model_customer_customer->getCustomer($customerId);

        // проверка наличия контакта в Salesman
        try {
            $contact = $customer['telephone'] ? $customer['telephone'] : $customer['email'];
            if ($this->SalesmanClient->searchClientClid($contact)) {
                return;
            }
        } catch (\Exception $e) {
            $this->log->write($e->__toString());
            return;
        }
        
        $address = '';
        if ($customer['address_id']) {
            if ($addressCustomer = $this->model_customer_customer->getAddress($customer['address_id'])) {
                $address = sprintf(
                    "%s%s, %s, %s, %s%s",
                    ($addressCustomer['postcode'] ? $addressCustomer['postcode'] . ' ' : ''),
                    $addressCustomer['country'],
                    $addressCustomer['zone'],
                    $addressCustomer['city'],
                    $addressCustomer['address_1'],
                    (
                        $addressCustomer['address_2']
                        ? sprintf(" (%s)", $addressCustomer['address_2'])
                        : $addressCustomer['address_2']
                    )
                );
            }
        }

        $client = [
            'uid' => $customerId,
            'title' => $customer['lastname'] . ' ' . $customer['firstname'],
            'type' => 'person',
            'address' => $address,
            'phone' => $customer['telephone'],
            'mail_url' => $customer['email'],
            'clientpath' => $_SERVER['HTTP_HOST']
        ];

        $this->SalesmanClient->newClient($client);
    }

    //######################################################################
    // PRIVATE
    //######################################################################

    /**
     * Массив ошибок при сохранении настроек
     *
     * @var array
     */
    private $errors = [];

    //######################################################################

    /**
     * Включен ли модуль
     *
     * @return boolean
     */
    private function isEnableModule()
    {
        $settings = $this->model_setting_setting->getSetting('module_salesman');

        return boolval($settings['module_salesman_status']);
    }
}
