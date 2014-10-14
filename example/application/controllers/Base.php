<?php
class BaseController extends Yaf_Controller_Abstract {

    /**
     * 初始化方法
     * @author tabalt
     */
    public function init() {
    	header("Content-Type:text/html;charset=utf-8");	
    }
}