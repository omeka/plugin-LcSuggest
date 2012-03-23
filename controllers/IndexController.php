<?php
class LcSuggest_IndexController extends Omeka_Controller_Action
{
    public function indexAction()
    {
        $this->view->assign('formElementOptions', $this->_getFormElementOptions());
        $this->view->assign('formSuggestOptions', $this->_getFormSuggestOptions());
    }
    
    public function editElementSuggestAction()
    {
        $elementId = $this->getRequest()->getParam('element_id');
        $suggestEndpoint = $this->getRequest()->getParam('suggest_endpoint');
        
        // Don't process empty select options.
        if ('' == $elementId) {
            $this->redirect->goto('index');
        }
        
        $lcSuggest = $this->getTable('LcSuggest')->findByElementId($elementId);
        
        // Handle an existing suggest record.
        if ($lcSuggest) {
            
            // Delete suggest record if there is no endpoint.
            if ('' == $suggestEndpoint) {
                $lcSuggest->delete();
                $this->flashSuccess('Successfully disabled the element\'s suggest feature.');
                $this->redirect->goto('index');
            }
            
            // Don't process an invalid suggest endpoint.
            if (!$this->_suggestEndpointExists($suggestEndpoint)) {
                $this->flashError('Invalid suggest endpoint. No changes have been made.');
                $this->redirect->goto('index');
            }
            
            $lcSuggest->suggest_endpoint = $suggestEndpoint;
            $this->flashSuccess('Successfully edited the element\'s suggest feature.');
        
        // Handle a new suggest record.
        } else {
            
            // Don't process an invalid suggest endpoint.
            if (!$this->_suggestEndpointExists($suggestEndpoint)) {
                $this->flashError('Invalid suggest endpoint. No changes have been made.');
                $this->redirect->goto('index');
            }
            
            $lcSuggest = new LcSuggest;
            $lcSuggest->element_id = $elementId;
            $lcSuggest->suggest_endpoint = $suggestEndpoint;
            $this->flashSuccess('Successfully enabled the element\'s suggest feature.');
        }
        
        $lcSuggest->save();
        $this->redirect->goto('index');
    }
    
    /**
     * Outputs the suggest endpoint URL of the specified element or NULL if 
     * there is none.
     */
    public function suggestEndpointAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $elementId = $this->getRequest()->getParam('element_id');
        $lcSuggest = $this->getTable('LcSuggest')->findByElementId($elementId);
        echo $lcSuggest->suggest_endpoint;
    }
    
    /**
     * Proxy for the Library of Congress suggest endpoints.
     */
    public function lcSuggestProxyAction()
    {
        // Get the element ID.
        $elementId = $this->getRequest()->getParam('element-id');
        $lcSuggest = $this->getDb()->getTable('LcSuggest')->findByElementId($elementId);
        
        // Query the specified Library of Congress suggest endpoint, get the 
        // response, and output suggestions in JSON.
        $client = new Zend_Http_Client();
        $client->setUri($lcSuggest->suggest_endpoint);
        $client->setParameterGet('q', $this->getRequest()->getParam('term'));
        $json = json_decode($client->request()->getBody());
        $this->_helper->json($json[1]);
    }
    /**
     * Check of the specified suggest endpoint exists.
     * 
     * @param string $suggestEndpoint
     * @return bool
     */
    private function _suggestEndpointExists($suggestEndpoint)
    {
        $suggestEndpoints = $this->getDb()->getTable('LcSuggest')->getSuggestEndpoints();
        if (!array_key_exists($suggestEndpoint, $suggestEndpoints)) {
            return false;
        }
        return true;
    }
    
    /**
     * Get an array to be used in formSelect() containing all elements.
     * 
     * @return array
     */
    private function _getFormElementOptions()
    {
        $db = $this->getDb();
        $select = $db->select()
                     ->from(array('rt' => $db->RecordType), 
                            array())
                     ->join(array('es' => $db->ElementSet), 
                            'rt.id = es.record_type_id', 
                            array('element_set_name' => 'name'))
                     ->join(array('e' => $db->Element), 
                            'es.id = e.element_set_id', 
                            array('element_id' =>'e.id', 
                                  'element_name' => 'e.name'))
                     ->joinLeft(array('ite' => $db->ItemTypesElements), 
                                'e.id = ite.element_id',
                                array())
                     ->joinLeft(array('it' => $db->ItemType), 
                                'ite.item_type_id = it.id', 
                                array('item_type_name' => 'it.name'))
                     ->joinLeft(array('ls' => $db->LcSuggest), 
                                'e.id = ls.element_id', 
                                array('lc_suggest_id' => 'ls.id'))
                     ->where('rt.name = "All" OR rt.name = "Item"')
                     ->order(array('es.name', 'it.name', 'e.name'));
        $elements = $db->fetchAll($select);
        $options = array('' => 'Select Below');
        foreach ($elements as $element) {
            $optGroup = $element['item_type_name'] 
                      ? 'Item Type: ' . $element['item_type_name'] 
                      : $element['element_set_name'];
            $value = $element['element_name'];
            if ($element['lc_suggest_id']) {
                $value .= ' *';
            }
            $options[$optGroup][$element['element_id']] = $value;
        }
        return $options;
    }
    
    /**
     * Get an array to be used in formSelect() containing all sugggest endpoints.
     * 
     * @return array
     */
    private function _getFormSuggestOptions()
    {
        $suggests = $this->getDb()->getTable('LcSuggest')->getSuggestEndpoints();
        $options = array('' => 'Select Below');
        foreach ($suggests as $suggestEndpoint => $suggest) {
            $options[$suggestEndpoint] = $suggest['name'];
        }
        return $options;
    }
}