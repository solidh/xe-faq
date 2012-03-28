<?php
    /**
     * @class  faq
     * @author NHN (developers@xpressengine.com)
     * @brief  faq module high class
     **/

	require_once(_XE_PATH_.'modules/faq/faq.item.php');

    class faq extends ModuleObject {

        var $search_option = array('question','answer'); ///< search options
        var $order_target = array('question_srl','list_order', 'update_order'); //< order options

        var $skin = "default"; ///< skin name
        var $list_count = 20; ///< the question count shown in a page
        var $page_count = 10; ///< the page count shown in the page
        var $category_list = NULL; ///< category 


        /**
         * @brief module installation
         **/
        function moduleInstall() {
            // action forward get module controller and model
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            return new Object();
        }

        /**
         * @brief check update method
         **/
        function checkUpdate() {
            $oModuleModel = &getModel('module');
            return false;
        }

        /**
         * @brief update module
         **/
        function moduleUpdate() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            return new Object(0, 'success_updated');
        }

		function moduleUninstall() {
			$output = executeQueryArray("faq.getAllFaq");
			if(!$output->data) return new Object();
			set_time_limit(0);
			$oModuleController =& getController('module');
			foreach($output->data as $faq)
			{
				$oModuleController->deleteModule($faq->module_srl);
			}
			return new Object();
		}

        /**
         * @brief create cache file
         **/
        function recompileCache() {
        }

    }
?>
