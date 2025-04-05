<?php


namespace App\Services;


class ClientBaseService
{
    private $headers;
    private $params;
    private $data;
    private $data_array;
    private $method;
    private $url;
    private $url_path;
    private $flag_data;
    private $curl_data;
    private $curl_data_test;

    public function __construct(array $data)
    {

        $this->method = $data['method'];
        $this->url = $data['url'];

        if (isset($data['path'])) {
            $this->url = $data['url'] . $data['path'];
            $this->url_path = $data['path'];
        }

        if (isset($data['headers'])) {
            $this->setHeaders($data['headers']);
        } else {
            $this->headers = [];
        }
        if (isset($data['params'])) {
            $this->setParams($data['params']);
        } else {
            $this->params = false;
        }
        if (isset($data['data'])) {
            $this->setData($data['data']);
            $this->data_array = $data['data'];
        }

        $this->curl_data = $this->configCurlData();
    }

    private function setHeaders(array $data)
    {
        $headers = [];
        foreach ($data as $key => $value) {

            if ($key == 'Content-Type' && $value == 'application/x-www-form-urlencoded') {
                $this->flag_data = 'urlencoded';
            }
            if ($key == 'Content-Type2' && $value == 'application/x-www-form-urlencoded') {
                $this->flag_data = 'urlencoded';
            }
            if ($key == 'Content-Type' && $value == 'text/plain') {
                $this->flag_data = 'raw';
            }
            if ($key == 'Content-Type2' && $value == 'text/plain') {
                $this->flag_data = 'raw';
            }
            if ($key != 'Content-Type2') {
                $headers[] = "$key: $value";
            }
        }
        $this->headers = $headers;
    }

    private function setParams(array $data)
    {
        if (isset($data['pattern_check'])) {
            foreach ($data as $key => $val) {
                $params_data[] = "$key=$val";
            }
            $params = implode('&', $params_data);
        } else {
            $params = http_build_query($data, '', '&', PHP_QUERY_RFC3986);
        }
        $this->url = $this->url . '?' . $params;
        $this->params = $params;
    }

    private function setData($data)
    {
        if ($this->flag_data == 'urlencoded') {
            $this->data = http_build_query($data);
        } elseif ($this->flag_data == 'raw') {
            $this->data = json_encode($data);
        } else {
            $this->data = $data;
        }
    }

    public function request()
    {

//        echo "<pre>";
//        var_dump($this->url);
//        var_dump($this->method);
//        var_dump($this->flag_data);
//        var_dump($this->data);
//        dd($this->curl_data);

        $curl_data = $this->curl_data;

//        dd($curl_data);

        $curl = curl_init();
        curl_setopt_array($curl, $curl_data);


        $response = curl_exec($curl);
        $curl_error = curl_error($curl);
        $curl_errno = curl_errno($curl);
        $http_code  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $result = [
            'response' => $response,
            'http_code' => $http_code,
            'curl_errno' => $curl_errno,
            'curl_error' => $curl_error,
            'params' => [
                'url' => $this->url,
                'method' => $this->method,
                'data' => $this->data,
                'headers' => $this->headers
            ]
        ];

        return $result;
    }

    private function configCurlData()
    {
        $curl_data = [
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 1800,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => $this->method,
            CURLOPT_POSTFIELDS => $this->data,
            CURLOPT_HTTPHEADER => $this->headers,
        ];

        $cURL_data[] = "curl --location --request $this->method '$this->url'";

        if (!empty($this->headers)) {
            foreach ($this->headers as $item) {
                $cURL_data[] = "--header '$item'";
            }
        }
        if (!empty($this->data_array)) {
            if ($this->flag_data == 'urlencoded') {
                foreach ($this->data_array as $key => $val) {
                    if(is_string($val)) {
                        $cURL_data[] = "--data-urlencode '$key=$val'";
                    }
                }
            } elseif ($this->flag_data == 'raw') {
                $cURL_data[] = "--data-raw '$this->data'";
            } else {
                if (is_array($this->data_array)) {
                    foreach ($this->data_array as $key => $val) {
                        if (is_string($val)) {
                            $cURL_data[] = "--form '$key=$val'";
                        } else {
                            $cURL_data[] = "--form 'error'";
                        }
                    }
                } else {

                }
            }
        }

        $curl_data_test['cURL'] = implode(" \\\n", $cURL_data);

        $curl_data_test['PHP_cURL'] = [
            'CURLOPT_URL' => $this->url,
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_ENCODING' => '',
            'CURLOPT_MAXREDIRS' => 10,
            'CURLOPT_TIMEOUT' => 1800,
            'CURLOPT_IPRESOLVE' => 'CURL_IPRESOLVE_V4',
            'CURLOPT_FOLLOWLOCATION' => true,
            'CURLOPT_HTTP_VERSION' => 'CURL_HTTP_VERSION_1_1',
            'CURLOPT_SSL_VERIFYPEER' => false,
            'CURLOPT_CUSTOMREQUEST' => $this->method,
            'CURLOPT_POSTFIELDS' => $this->data,
            'CURLOPT_HTTPHEADER' => $this->headers,
        ];

        $this->curl_data_test = $curl_data_test;

        return $curl_data;
    }

    public function requestEmtpy()
    {
        $curl_data = $this->curl_data;
        $curl_data[CURLOPT_TIMEOUT_MS] = '500';
//        dd($curl_data);

        $curl = curl_init();
        curl_setopt_array($curl, $curl_data);


        $response = curl_exec($curl);
        curl_close($curl);

        return true;
    }


    /**
     * @return mixed
     */
    public function getCurlData()
    {
        return $this->curl_data;
    }

    /**
     * @param mixed $curl_data
     */
    public function setCurlData($curl_data): void
    {
        $this->curl_data = $curl_data;
    }

    /**
     * @return mixed
     */
    public function getCurlDataTest()
    {
        return $this->curl_data_test;
    }

    /**
     * @param mixed $curl_data_test
     */
    public function setCurlDataTest($curl_data_test): void
    {
        $this->curl_data_test = $curl_data_test;
    }
}

