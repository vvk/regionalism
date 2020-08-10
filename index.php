<?php

error_reporting(1);

ini_set('memory_limit', '2G');
$obj = new Regionalism();
$obj->run();

class Regionalism
{
    protected $url = 'http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2019/index.html';
    protected $flag = '';
    protected $logFile = 'log';
    protected $csvFile = './data.csv';

    public function run()
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }

        try {
            $data = $this->getProvinceList();
            foreach ($data as $k => $item) {
                echo $item['name'].' start...'.PHP_EOL;
                $cityList = $this->getCityList($item);
                foreach ($cityList as $cityKey => $cityItem) {
                    echo $item['name']."\t".$cityItem['name'].' start...'.PHP_EOL;
                    $districtList = $this->getDistrict($cityItem);
                    foreach ($districtList as $districtKey => $districtItem) {
                        echo $item['name']."\t".$cityItem['name']."\t".$districtItem['name'].' start...'.PHP_EOL;
                        $streetList = $this->getStreet($districtItem);
                        foreach ($streetList as $streetKey => $streetItem) {
                            echo $item['name']."\t".$cityItem['name']."\t".$districtItem['name']."\t".$streetItem['name'];
                            $community = $this->getCommunity($streetItem);
                            unset($streetItem['sub_url']);
                            $streetItem['list'] = $community;

                            $streetList[$streetKey] = $streetItem;
                            echo "\t".count($community).PHP_EOL;
                            usleep(10);
                        }
                        $districtItem['list'] = $streetList;
                        unset($districtItem['sub_url']);
                        $districtList[$districtKey] = $districtItem;
                        echo $item['name']."\t".$cityItem['name']."\t".$districtItem['name']."\t".count($streetList).PHP_EOL;
                        usleep(100);
                    }
                    $cityItem['list'] = $districtList;
                    unset($cityItem['sub_url']);
                    $cityList[$cityKey] = $cityItem;

                    echo $item['name']."\t".$cityItem['name'].' end...'.PHP_EOL;
                    usleep(200);
                }

                $item['list'] = $cityList;
                unset($item['sub_url']);
                $data[$k] = $item;

                echo $item['name'].'end'.PHP_EOL;

                sleep(1);
            }
            file_put_contents('data.json', json_encode($data, JSON_UNESCAPED_UNICODE));

            $this->saveToCsv($data);
        } catch (Exception $e) {
            echo 'file: '.$e->getFile().PHP_EOL;
            echo 'line: '.$e->getLine().PHP_EOL;
            echo 'msg: '.$e->getMessage().PHP_EOL;
        }
    }

    /**
     * 省
     * @return array
     */
    protected function getProvinceList()
    {
        $this->flag = '';
        $xpath = '//tr[@class="provincetr"]/td/a';
        $data = $this->getXpathData($this->url, $xpath);
        if (count($data) == 0) {
            $data = $this->getXpathData($item['sub_url'], $xpath);
        }
        return $data;
    }

    /**
     * 城市
     * @param $item
     * @return array
     */
    protected function getCityList($item)
    {
        if (!isset($item['sub_url'])) {
            print_r($item);
            exit;
        }

        $this->flag = '';
        $xpath = '//tr[@class="citytr"]/td/a';
        $data = $this->getXpathData($item['sub_url'], $xpath, true);
        if (count($data) == 0) {
            $data = $this->getXpathData($item['sub_url'], $xpath, true);
        }
        return $data;
    }

    /**
     * 区、县
     * @param $item
     */
    protected function getDistrict($item)
    {
        if (!isset($item['sub_url'])) {
            print_r($item);
            exit;
        }

        $this->flag = '';
        $xpath = '//tr[@class="countytr"]/td/a';
        $data = $this->getXpathData($item['sub_url'], $xpath, true);
        if (count($data) == 0) {
            $data = $this->getXpathData($item['sub_url'], $xpath, true);
        }
        return $data;
    }

    /**
     * 街道
     * @param $item
     * @return mixed
     */
    protected function getStreet($item)
    {
        if (!isset($item['sub_url'])) {
            print_r($item);
            exit;
        }

        $this->flag = '';
        $xpath = '//tr[@class="towntr"]/td/a';
        $data = $this->getXpathData($item['sub_url'], $xpath, true);
        return $data;
    }

    protected function getCommunity($item)
    {
        if (!isset($item['sub_url'])) {
            print_r($item);
            exit;
        }

        $this->flag = 'community';
        $xpath = '//tr[@class="villagetr"]/td';
        $data = $this->getXpathData($item['sub_url'], $xpath);
        if (count($data) == 0) {
            $data = $this->getXpathData($item['sub_url'], $xpath);
        }
        return $data;
    }

    protected function getXpathData($url, $path, $fullCode = false)
    {
        $fileName = './file/'.md5($url);
        $result = file_exists($fileName) ? file_get_contents($fileName) : '';
        if (!empty($result)) {
            return json_decode($result, true);
        }

        for ($i = 0; $i < 5; $i++) {
            $html = $this->curl($url);
            if (!empty($html)) {
                break;
            }
            $log  = $url. ' 第 '.($i+1). ' 次重试'.PHP_EOL;
            echo $log;
            sleep(1);
        }
        $html = $this->curl($url);
        if (empty($html)) {
            $log = '获取 '.$url.' 信息失败'.PHP_EOL;
            echo $log;
            error_log($log, 3, $this->logFile);
            return [];
        }

        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $dom->normalize();

        $xpath = new DOMXPath($dom);
        $data = $xpath->query($path);

        $result = [];
        $k = 0;
        $baseUrl = dirname($url).'/';
        for ($i = 0; $i < $data->length; $i++) {
            if ($this->flag == 'community') {
                $code = $data->item($i)->textContent;

                $i = $i + 2;
                $result[$k] = [
                    'name' => $data->item($i)->textContent,
                    'code' => $code,
                    'full_code' => $code,
                ];
            } else {
                $name = $data->item($i)->textContent;
                foreach ($data->item($i)->attributes as $attr) {
                    $arr = explode('/', $attr->textContent);
                    $id = intval(end($arr));
                    if ($attr->name == 'href') {
                        $result[$k] = [
                            'name' => $name,
                            'code' => $id,
                            'sub_url' => $baseUrl.$attr->textContent
                        ];
                        break;
                    }
                }

                if ($fullCode) {
                    $result[$k]['name'] = $data->item($i+1)->textContent;
                    $result[$k]['full_code'] = $data->item($i)->textContent;
                    $i++;
                }
            }

            $k++;
        }

        if (!empty($result)) {
            file_put_contents($fileName, json_encode($result, JSON_UNESCAPED_UNICODE));
        }

        return $result;
    }

    /**
     * curl
     * @param string $url
     * @param array $data
     * @param boolean $mode 是否为post请求，默认为get
     * @return boolean|mixed
     */
    protected function curl($url, $data = [], $isPost = false, $time = 30, array $headers = [])
    {
        if ($isPost) {
            $options[CURLOPT_POST] = 1;
            $options[CURLOPT_POSTFIELDS] = $data;
        } else {
            if ($data) {
                $url .= '?' . http_build_query($data);
            }
        }
        if ($headers) {
            foreach( $headers as $key => $value ) {
                $headers_array[] = $key .':' . $value;
            }
            $options[CURLOPT_HTTPHEADER] = $headers_array;
        }
        $options[CURLOPT_RETURNTRANSFER] = true;
        $options[CURLOPT_TIMEOUT] = $time;
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $output = curl_exec($ch);
        $res = curl_getinfo($ch);
        if(curl_errno($ch)){
            return '';
        }else{
            return $output;
        }
    }

    protected function saveToCsv($data)
    {

        if (file_exists($this->csvFile)) {
            unlink($this->csvFile);
        }

        $row = [
            '省', '省代码', '城市', '城市代码', '城市完整代码', '区（县）名称', '区（县）代码', '区（县）完整代码',
            '街道（乡镇）名称', '街道（乡镇）代码', '街道（乡镇）完整代码', '居委会（村）名称', '居委会（村）代码',
            '居委会（村）完整代码',
        ];
        $str = implode(',', $row);
        error_log($str."\n", 3, $this->csvFile);

        $count = 0;
        foreach ($data as $province) {
            foreach ($province['list'] as $city) {
                foreach ($city['list'] as $distinct) {
                    foreach ($distinct['list'] as $street) {
                        $arr = [];
                        foreach ($street['list'] as $community) {
                            $row = [
                                $province['name'],
                                $province['code'],
                                $city['name'],
                                $city['code'],
                                $city['full_code'],
                                $distinct['name'],
                                $distinct['code'],
                                $distinct['full_code'],
                                $street['name'],
                                $street['code'],
                                $street['full_code'].' ',
                                $community['name'],
                                $community['code'],
                                $community['full_code'].' ',
                            ];
                            $arr[] = implode(",\t", $row);
                        }

                        $count += count($arr);
                        $str = implode("\n", $arr)."\n";
                        error_log($str, 3, $this->csvFile);

                        echo $count."\n";
                        if ($count % 2000) {
                            usleep(100);
                        }
                    }
                }
            }
        }
    }
}







