<?php
class Bootstrap extends Yaf_Bootstrap_Abstract {
    
    /**
     * 初始化配置
     */
    public function _initConfig() {
        //把配置保存起来
        $config = Yaf_Application::app()->getConfig();
        Yaf_Registry::set('config', $config->toArray());
    }
    
    /**
     * 初始化模板
     * @param Yaf_Dispatcher $dispatcher
     */
    public function _initView(Yaf_Dispatcher $dispatcher){
        $dispatcher->autoRender(false);
    }
    
    /**
     * 初始化加载
     */
    public function _initLoader(Yaf_Dispatcher $dispatcher) {
        spl_autoload_register(function($className){
            //echo "$className\n";
        });
    }
    
    /**
     * 初始化路由
     */
    public function _initRoute(Yaf_Dispatcher $dispatcher) {
        
    }
    
    /**
     * 初始化插件
     */
    public function _initPlugin(Yaf_Dispatcher $dispatcher) {
    
    }
}