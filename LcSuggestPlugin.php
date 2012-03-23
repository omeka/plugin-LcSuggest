<?php
require_once 'Omeka/Plugin/Abstract.php';

class LcSuggestPlugin extends Omeka_Plugin_Abstract
{
    protected $_hooks = array(
        'install', 
        'uninstall', 
        'initialize', 
        'define_acl', 
    );
    
    protected $_filters = array(
        'admin_navigation_main', 
    );
    
    public function hookInstall()
    {
        $sql = "
        CREATE TABLE `{$this->_db->prefix}lc_suggests` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `element_id` int(10) unsigned NOT NULL,
            `suggest_endpoint` tinytext COLLATE utf8_unicode_ci NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `element_id` (`element_id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $this->_db->query($sql);
    }
    
    public function hookUninstall()
    {
        $sql = "DROP TABLE IF EXISTS `{$this->_db->prefix}lc_suggests`";
        $this->_db->query($sql);
    }
    
    /**
     * Register the SelectFilter controller plugin.
     */
    public function hookInitialize()
    {
        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new LcSuggest_Controller_Plugin_SelectFilter);
    }
    
    /**
     * Restrict access to super and admin.
     */
    public function hookDefineAcl($acl)
    {
        $acl->loadResourceList(array('LcSuggest_Index' => array(
            'index', 'editElementSuggest', 'suggestEndpoint', 
        )));
    }
    
    public function filterAdminNavigationMain($nav)
    {
        if (has_permission('LcSuggest_Index', 'index')) {
            $nav['LC Suggest'] = uri('lc-suggest');
        }
        return $nav;
    }
}
