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
        $this->load->language('extension/module/salesman');
        $this->document->setTitle($this->language->get('doc_title'));

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->saveSettings();
        }

        // сборка данных страницы
        $data = array_merge(
            $this->getTemplateData(),
            $this->getFormData(),
            $this->getSettingsData(),
            $this->getStatusData()
        );

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
                'user_token=' . $this->session->data['user_token'],
                true
            )
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link(
                'marketplace/extension',
                'user_token=' . $this->session->data['user_token'] . '&type=module',
                true
            )
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link(
                'extension/module/salesman',
                'user_token=' . $this->session->data['user_token'],
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
            'user_token=' . $this->session->data['user_token'],
            true
        );
        $data['cancel'] = $this->url->link(
            'marketplace/extension',
            'user_token=' . $this->session->data['user_token'] . '&type=module',
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
            unset($this->session->data["settings_success"]);
        } else {
            $data['settings_success'] = false;
        }

        // если есть ошибки - показываем
        if (isset($this->session->data['settings_error'])) {
            $data['error_warning'] = implode("<br/>", $this->session->data["settings_error"]);
            unset($this->session->data["settings_error"]);
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
                    'user_token=' . $this->session->data['user_token'],
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
        $this->load->model('setting/event');

        $this->model_setting_event->addEvent(
            'salesman',
            'admin/model/customer/customer/addCustomer/after',
            'extension/module/salesman/eventAddCustomerAfter'
        );

        $this->model_setting_event->addEvent(
            'salesman',
            'catalog/model/account/customer/addCustomer/after',
            'extension/module/salesman/eventAddCustomerAfter'
        );

        $this->model_setting_event->addEvent(
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
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('salesman');
    }

    //######################################################################

    /**
     * Обработчик события после добавления нового пользрвателя из админки
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

        $cusomer = $args[0];
        $client = [
            'uid' => $customerId,
            'title' => $cusomer['lastname'] . ' ' . $cusomer['firstname'],
            'phone' => $cusomer['telephone'],
            'mail_url' => $cusomer['email'],
            "clientpath" => $_SERVER['HTTP_HOST']
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
    private function isEnableModule(): bool
    {
        $settings = $this->model_setting_setting->getSetting("module_salesman");

        return boolval($settings['module_salesman_status']);
    }
}
