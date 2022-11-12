<?php // phpcs:disable // phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

/**
 * Salesman Client для OpenCart
 * Создает клиентов и сделки
 * @see https://salesman.pro/api2/client
 * @see https://salesman.pro/api2/deal
 */
class SalesmanClient
{
    /**
     * @param Registry $registry
     */
    public function __construct($registry)
    {
        $registry->get('load')->model('setting/setting');
        $settings = $registry->get('model_setting_setting')->getSetting("module_salesman");

        $this->url = (isset($settings['module_salesman_api_url']) ? $settings['module_salesman_api_url'] : '');
        $this->login = (isset($settings['module_salesman_api_login']) ? $settings['module_salesman_api_login'] : '');
        $this->token = (isset($settings['module_salesman_api_token']) ? $settings['module_salesman_api_token'] : '');
    }

    // *********************************************************************

    /**
     * Создать нового клиента
     *
     * @throws \Exception
     *
     * @param array $client данные клиента
     * @return string
     */
    public function newClient(array $client): string
    {
        $response = $this->send('client', array_merge(["action" => "add"], $client));

        if (isset($response['error'])) {
            throw new \Exception($response['error']['text'], $response['error']['code']);
        }
        
        return $response['data'];
    }

    /**
     * Получить clid клиента по uid
     *
     * @throws \Exception
     *
     * @param string $uid идентификатор пользователя в opencart
     * @return string|null
     */
    public function getClientClid(string $uid): ?string
    {
        $response = $this->send(
            'client',
            [
                "action"=> "info",
                "uid"  => $uid,
                "fields"=> 'clid',
            ]
        );

        if (isset($response['data'])) {
            return $response['data']['clid'];
        }

        return null;
    }

    /**
     * Найти clid клиента по контакту
     *
     * @param string $contact
     * @return string|null
     */
    public function searchClientClid(string $contact): ?string
    {
        $response = $this->send(
            'client',
            [
                "action"=> "list",
                "word"  => trim($contact),
                "fields"=> 'clid',
            ]
        );

        if (isset($response['data']) && isset($response['data']['clid'])) {
            return $response['data']['clid'];
        }

        return null;
    }

    // *********************************************************************

    /**
     * Создать новую сделку
     *
     * @throws \Exception
     *
     * @param array $deal данные сделки
     * @return void
     */
    public function newDeal(array $deal): void
    {
        $response = $this->send('deal', array_merge(["action" => "add"], $deal));
    }

    /**
     * Существует ли сделка
     *
     * @throws \Exception
     *
     * @param string $uid идентификатор заказа в opencart
     * @return boolean
     */
    public function existsDeal(string $uid): bool
    {
        $response = $this->send(
            'deal',
            [
                "action"   => "info",
                "uid"      => $uid,
            ]
        );

        return isset($response['data']);
    }

    // *********************************************************************

    /**
     * Тестирование доступа по указанным аргументам
     *
     * @throws \Exception
     *
     * @param string $url
     * @param string $login
     * @param string $token
     * @return boolean
     */
    public function test(string $url, string $login, string $token): bool
    {
        $response = $this->send('client', ["action" => "fields"], $url, $login, $token);

        if (isset($response['error'])) {
            throw new \Exception($response['error']['text'], $response['error']['code']);
        }
        return true;
    }

    //######################################################################
    // PRIVATE
    //######################################################################

    protected $url = '';
    protected $login = '';
    protected $token = '';

    //######################################################################

    /**
     * Отправка запроса в CRM.
     * Необязательные параметры имеющие null будут взяты из аналогичных полей объекта
     *
     * @param string $part раздел CRM (client, deal, etc)
     * @param array $data данные сущности
     * @param string|null $url
     * @param string|null $login
     * @param string|null $token
     * @return array
     */
    protected function send(string $part, array $data, string $url = null, string $login = null, string $token = null): array
    {
        if (!$url) {
            $url = $this->url;
        }

        $url = rtrim($url, '/');

        if (!$login) {
            $login = $this->login;
        }

        if (!$token) {
            $token = $this->token;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_URL, sprintf("%s%s%s", $url, '/developer/v2/', $part));
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Content-type: application/json',
                "login: {$login}",
                "apikey: {$token}",
            ]
        );
    
        $result = curl_exec($ch);

        if (curl_errno($ch) != 0) {
            $code = curl_errno($ch);
            $msg = curl_strerror($code);
            throw new \Exception($msg, $code);
        }

        $a = json_decode($result, true);

        if (!is_array($a)) {
            $msg = sprintf(
                "Request [%s] to [%s] with data %s got strange response %s",
                $part,
                $url,
                print_r($data, true),
                $result
            );
            throw new \Exception($msg);
        }
    
        return $a;
    }
}
