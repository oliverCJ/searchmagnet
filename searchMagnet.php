<?php
require_once('workflows.php');
/**
 * 资源搜索类
 * 感谢快搜盒子提供的API支持
 * http://www.kuaisohezi.com
 * @author oliverCJ <cgjp123@163.com>
 */
class searchMagnet {

    protected $workFlows;

    private $soKeyUrl = 'https://api.kuaisohezi.com/search';

    private $soHashUrl = 'https://api.kuaisohezi.com/json_info';

    private $icon = 'icon.png';

    private $magnetPrefix = 'magnet:?xt=urn:btih:';

    private $catMap = array(
        'video' => '视频',
        'image' => '图片',
        'document' => '文档',
        'music' => '音乐',
        'package' => '压缩包', 
        'software' => '软件',
        'other' => '其他'
        );

    public function __construct($query) {
        $this->workFlows = new Workflows();
        $this->getResult($query);
    }

    private function getResult($query) {
        if (!empty($query)) {
            if (ctype_alnum($query) && strlen($query) == 40) {
                $this->searchHash($query);
            } else {
                $this->searchKeyWord($query);
            }
        }
    }

    private function searchKeyWord($keyWord) {
        $queryStr = http_build_query(array(
            'keyword' => $keyWord,
            'start' => 0,
            'count' => 10,
            ));
        $result = $this->workFlows->request($this->soKeyUrl . '?' . $queryStr);
        if (!empty($result)) {
            $resultArray = json_decode($result, true);
            if (!empty($resultArray['result'])) {
                $this->display($resultArray['result']);
            } else {
                $this->display('');
            }
        } else {
            $this->display('');
        }
        
    }

    private function searchHash($hash) {
        $queryStr = http_build_query(array(
            'hashes' => $hash
            ));
        $result = $this->workFlows->request($this->soHashUrl . '?' . $queryStr);
        if (!empty($result)) {
            $resultArray = json_decode($result, true);
            if (!empty($resultArray) && !empty($resultArray[$hash])) {
                $result = $resultArray[$hash];
                $cat = $this->getCat($result['category']);
                $size = $this->getFormatSize($result['length']);
                $seenTime = $result['last_seen'];
                $this->workFlows->result(0, $this->magnetPrefix . $hash, $result['name'], '类型：' . $cat . '，大小：' . $size . '，时间：' . $seenTime . '，(回车复制到剪贴板)' ,$this->icon);
                echo $this->workFlows->toxml();
            } else {
                $this->display('');
            }
        } else {
            $this->display('');
        }
    }

    private function display($result) {
        if (!empty($result)) {
            $metaInfo = $result['meta'];
            $resultInfo = $result['items'];
            foreach ($resultInfo as $key => $value) {
                $cat = $this->getCat($value['cat_value']);
                $size = $this->getFormatSize($value['length']);
                $seenTime = date('Y-m-d H:i:s', $value['last_seen']);
                $this->workFlows->result($key, $this->magnetPrefix . $value['info_hash'], $value['name'], '类型：' . $cat . '，大小：' . $size . '，时间：' . $seenTime . '，(回车复制到剪贴板)' ,$this->icon);
            }
        } else {
            $this->workFlows->result(0, '', '搜索失败', '请稍候再试' ,$this->icon);
        }
        echo $this->workFlows->toxml();
    }

    private function getCat($data) {
        if (array_key_exists($data, $this->catMap)) {
            $cat = $this->catMap[$data];
        } else {
            $cat = '未知';
        }
        return $cat;
    }

    private function getFormatSize($size) {
        if (($size / 1024) < 1024 ) {
            $formatSize = round($size / 1024, 1) . 'KB';
        } elseif (($size / 1024 / 1024) < 1024) {
            $formatSize = round($size / 1024 / 1024 , 1) . 'MB';
        } elseif (($size / 1024 / 1024 / 1024) < 1024) {
            $formatSize = round($size / 1024 / 1024 / 1024, 1) . 'GB';
        } else {
            $formatSize = round($size / 1024 / 1024 / 1024 / 1024, 2) . 'TB';
        }
        return $formatSize;
    }
}