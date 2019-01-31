<?php

namespace Tree6bee\Any\Pagination;

/**
 * 通用分页类
 *
 * @date 2014.12.11
 * @example
 * 样式参考:
.page {font:12px/16px arial; height:24px;}
.page a, span{display:block;float:left;margin:0 3px;border:1px solid #ddd;padding:3px 7px; }
.page a {text-decoration:none;color:#666}
.page .cur, .page a:hover{color:#fff;background:#05c}
 *
 * 调用方式:
 * $pager = new Pagination($config); //数组$config 参考类中的$config写法,其中total_rows必有，其他可以使用默认值
 * $pagerInfo = $pager->getLimit();
 * echo $pager;
 *
 */
class Pagination {
    private $config = array(
        'total_rows'    =>  0,   //总的数据记录数,必须
        'per_page'      =>  10,    //单页数据条数
        'offset'        =>  5,  //最大显示的offset * 2的分页链接
        'page_key'      =>  'p',  //url中获取页数的键
        'debug'         =>  false,   //是否输出调试信息
        'show'          =>  array('first', 'prev', 'list', 'next', 'last'),
        'lang'          =>  array(
            'first' =>  '首页',
            'prev'  =>  '上一页',
            'next'  =>  '下一页',
            'last'  =>  '尾页',
        ),
        'anchor_class'  =>  array(    //输出html中标签的类
            'p'     =>  'page',  //最外层的div的类名
            'cur'   =>  'cur', //当前选中的a的类名
        ),
    );

    private $pageInfo = array(true, null);
    private $baseUri = '';
    private $total_page = 0;
    private $cur_page = 1; // The current page being viewed

    /**
     * Constructor
     *
     * @access	public
     * @param	array	initialization parameters
     */
    public function __construct($params = array()) {
        if (count($params) > 0) {
            $this->config = array_merge($this->config, $params);
        }
        if ($this->config['total_rows'] == 0) {
            $this->setPageInfo(array(false, 'total row page is zero'));
        }
        $this->total_page = ceil($this->config['total_rows'] / $this->config['per_page']);
        if ($this->total_page == 1) {
            $this->setPageInfo(array(false, 'only one page'));
        }
        $this->baseUri = $this->getUri();
        $this->getCurPage();
    }

    /**
     * Generate the pagination links
     *
     * @access	public
     * @return	string
     */
    public function __toString() {
        if (!$this->pageInfo[0]) {
            return $this->config['debug'] ? $this->pageInfo[1] : '';
        }
        $html = "<div class='{$this->config['anchor_class']['p']}'>";
        foreach ($this->config['show'] as $s) {
            $m = $s . 'Page';
            if (method_exists($this, $m)) {
                $html .= $this->$m();
            } else if ($this->config['debug']) {
                return "function: $m not exist";
            }
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * 返回sql limit 信息
     * @return array(起始行，条数)
     */
    public function getLimit() {
        $start_row = ($this->cur_page - 1) * $this->config['per_page'];
        return array($start_row, $this->config['per_page']);
    }

    /**
     *
     * @param array $info
     * @return bool
     */
    private function setPageInfo($info) {
        $this->pageInfo = $info;
        return true;
    }

    private function getCurPage() {
        $cur_page = empty($_GET[$this->config['page_key']]) ? 1 : (int)$_GET[$this->config['page_key']];
        $cur_page = $cur_page > 0 ? $cur_page : 1;
        $this->cur_page = ($cur_page > $this->total_page) ? $this->total_page : $cur_page;
        return $this->cur_page;
    }

    private function getUri() {
        $url = $_SERVER["REQUEST_URI"] . (strpos($_SERVER["REQUEST_URI"], '?') ? '' : '?');
        $parse = parse_url($url);
        if (isset($parse["query"])) {
            parse_str($parse['query'], $params);
            unset($params[$this->config['page_key']]);
            $url = $parse['path'] . '?' . http_build_query($params);
        }
        if (!empty($params)) $url .= '&';
        return $url . "{$this->config['page_key']}=";
    }

    private function firstPage() {
        if ($this->cur_page == 1)   return '';
        return $this->getLink(1, $this->config['lang']['first']);
    }

    private function lastPage() {
        if ($this->cur_page < $this->total_page) {
            return $this->getLink($this->total_page, $this->config['lang']['last']);
        }
        return '';
    }

    private function prevPage() {
        if ($this->cur_page == 1)   return '';
        return $this->getLink($this->cur_page - 1, $this->config['lang']['prev']);
    }

    private function nextPage() {
        if ($this->cur_page < $this->total_page) {
            return $this->getLink($this->cur_page + 1, $this->config['lang']['next']);
        }
        return '';
    }

    private function listPage() {
        $html = '';
        $start = 1;
        $end = $this->config['offset'] * 2;
        if ($this->cur_page > $this->config['offset']) {
            $start = $this->cur_page - $this->config['offset'] + 1;
            $end = $this->cur_page + $this->config['offset'];
        }
        if ($this->cur_page > ($this->total_page - $this->config['offset'])) {
            $start = $this->total_page - $this->config['offset'] * 2 + 1;
            $end = $this->total_page;
            $start = $start > 0 ? $start : 1;
        }
        $end = $end > $this->total_page ? $this->total_page : $end;
        for ($i = $start; $i <= $end; $i++) {
            $html .= $this->getLink($i);
        }
        return $html;
    }

    private function getLink($page, $text = '') {
        $text = $text ? $text : $page;
        if ($this->cur_page == $page) {
            $class = $this->config['anchor_class']['cur'];
            return "<span class='{$class}'>{$text}</span>";
        }
        return "<a href='{$this->baseUri}{$page}'>{$text}</a>";
    }
}
