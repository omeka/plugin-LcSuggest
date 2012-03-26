<?php
class LcSuggest_Controller_Plugin_SelectFilter extends Zend_Controller_Plugin_Abstract
{
    /**
     * Add form filters only during selected actions.
     */
    public function preDispatch($request)
    {
        $db = get_db();
        
        // Set NULL modules to default. Some routes do not have a default 
        // module, which resolves to NULL.
        $module = $request->getModuleName();
        if (is_null($module)) {
            $module = 'default';
        }
        $controller = $request->getControllerName();
        $action = $request->getActionName();
        
        // Include all routes (route + controller + actions) that render an 
        // element form, including actions requested via AJAX.
        $routes = array(
            array('module' => 'default', 
                  'controller' => 'items', 
                  'actions' => array('add', 'edit', 'element-form', 'change-type'))
        );
        
        // Allow plugins to add routes that contain form inputs rendered by 
        // Omeka_View_Helper_ElementForm::_displayFormInput().
        $routes = apply_filters('lc_suggest_routes', $routes);
        
        // Iterate the defined routes.
        foreach ($routes as $route) {
            if ($route['module'] === $module 
             && $route['controller'] === $controller 
             && in_array($action, $route['actions'])) {
                
                // Add filters to defined routes.
                $lcSuggests = $db->getTable('LcSuggest')->findAll();
                foreach ($lcSuggests as $lcSuggest) {
                    
                    // Get the element and element set.
                    $element = $db->getTable('Element')->find($lcSuggest->element_id);
                    $elementSet = $db->getTable('ElementSet')->find($element->element_set_id);
                    
                    // Add the autosuggest JavaScript to the JS queue.
                    $view = Zend_Registry::get('view');
                    $view->headScript()->captureStart();
?>
// Add autosuggest to selected form inputs. Used by the LC Suggest plugin.
jQuery(document).bind('omeka:elementformload', function(event) {
    jQuery('#element-<?php echo $element->id; ?> textarea').autocomplete({
        minLength: 3,
        source: <?php echo json_encode($view->url('lc-suggest/index/suggest-endpoint-proxy/element-id/' . $element->id)); ?>
    });
});
<?php
                    $view->headScript()->captureEnd();
                    
                    add_filter(array('Form', 'Item', $elementSet->name, $element->name), 
                               array($this, 'filterElement'));
                }
                // Once the filter is applied for one action it is applied for 
                // all subsequent actions, so there is no need to continue 
                // looping the routes.
                break;
            }
        }
    }
    
    /**
     * Add autosuggest (jQuery UI autocomplete) to the element form.
     */
    public function filterElement($html, $inputNameStem, $value, $options, $item, $element) {
        return __v()->formTextarea($inputNameStem . '[text]', 
                                   $value, 
                                   array('rows' => '2', 'cols' => '50', 'class' => 'textinput'));
    }
}
