<?php
    /**
     * @class  faqModel
     * @author NHN (developers@xpressengine.com)
     * @brief  faq module Model class
     **/

    class faqModel extends module {

		/**
		 * @brief initialization
		 **/
		function init() {
		}

        /**
         * @brief getListConfig
         **/
        function getListConfig($module_srl) {
            $oModuleModel = &getModel('module');
            $oFaqModel = &getModel('faq');

            $list_config = $oModuleModel->getModulePartConfig('faq', $module_srl);
            if(!$list_config || !count($list_config)) $list_config = array( 'no', 'title', 'nick_name','regdate','readed_count');

            foreach($list_config as $key) {
                if(preg_match('/^([0-9]+)$/',$key)) $output['extra_vars'.$key] = $inserted_extra_vars[$key];
                else $output[$key] = new ExtraItem($module_srl, -1, Context::getLang($key), $key, 'N', 'N', 'N', null);
            }

            return $output;
        }


		/**
         * @brief get faq question object (faq item)
         **/
        function getQuestion($question_srl=0, $is_admin = false, $load_extra_vars=true) {
            if(!$question_srl) return new faqItem();

            if(!isset($GLOBALS['XE_QUESTION_LIST'][$question_srl])) {
                $oQuestion = new faqItem($question_srl, $load_extra_vars);
                $GLOBALS['XE_QUESTION_LIST'][$question_srl] = $oQuestion;
            }
            if($is_admin) $GLOBALS['XE_QUESTION_LIST'][$question_srl]->setGrant();

            return $GLOBALS['XE_QUESTION_LIST'][$question_srl];
        }

        /**
         * @brief get category question count
         **/
        function getCategoryQuestionCount($module_srl, $category_srl) {
            $args->module_srl = $module_srl;
            $args->category_srl = $category_srl;
            $output = executeQuery('faq.getCategoryQuestionCount', $args);
            return (int)$output->data->count;
        }

        /**
         * @brief get faq category infor
         **/
        function getCategory($category_srl) {
            $args->category_srl = $category_srl;
            $output = executeQuery('faq.getCategory', $args);

            $node = $output->data;
            if(!$node) return;

            if($node->group_srls) {
                $group_srls = explode(',',$node->group_srls);
                unset($node->group_srls);
                $node->group_srls = $group_srls;
            } else {
                unset($node->group_srls);
                $node->group_srls = array();
            }
            return $node;
        }

		/**
		 * @brief get category HTML which shown in category admin page
		 **/
		function getCategoryHTML($module_srl) {

			$category_xml_file = $this->getCategoryXmlFile($module_srl);

			Context::set('category_xml_file', $category_xml_file);

			Context::loadJavascriptPlugin('ui.tree');

			$oTemplate = &TemplateHandler::getInstance();
			return $oTemplate->compile($this->module_path.'tpl', 'category_admin');
		}

		/**
		 * @brief get cached category xml file, then return
		 **/
		function getCategoryXmlFile($module_srl) {
			$xml_file = sprintf('files/cache/faq_category/%s.xml.php',$module_srl);
			if(!file_exists($xml_file)) {
				$oFaqController = &getController('faq');
				$oFaqController->makeCategoryFile($module_srl);
			}
			return $xml_file;
		}

		/**
		 * @brief get Faq category template information
		 **/
        function getFaqCategoryTplInfo() {
            $oModuleModel = &getModel('module');
            $oMemberModel = &getModel('member');

            // get module information
            $module_srl = Context::get('module_srl');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

            // get user permission
            $grant = $oModuleModel->getGrant($module_info, Context::get('logged_info'));
            if(!$grant->manager) return new Object(-1,'msg_not_permitted');

            $category_srl = Context::get('category_srl');
            $parent_srl = Context::get('parent_srl');

            // get user member group
            $group_list = $oMemberModel->getGroups($module_info->site_srl);
            Context::set('group_list', $group_list);

            // if parent_srl is exists, category_srl not exists
            if(!$category_srl && $parent_srl) {
                // get parent categry information
                $parent_info = $this->getCategory($parent_srl);

                // create a new category_srl
                $category_info->category_srl = getNextSequence();
                $category_info->parent_srl = $parent_srl;
                $category_info->parent_category_title = $parent_info->title;

            // add categroy list, or update category list
            } else {
                // if category_srl exists (update) 
                if($category_srl) $category_info = $this->getCategory($category_srl);

                // if category_srl not exists (add)
                if(!$category_info->category_srl) {
                    $category_info->category_srl = getNextSequence();
                }
            }


            $category_info->title = htmlspecialchars($category_info->title);
            Context::set('category_info', $category_info);

            // compile category template
            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile('./modules/faq/tpl', 'category_info');
            $tpl = str_replace("\n",'',$tpl);

            // set user language
            $oModuleController = &getController('module');
            $oModuleController->replaceDefinedLangCode($tpl);

            // add template
            $this->add('tpl', $tpl);
        }

        /**
         * @brief get category child count
         **/
        function getCategoryChlidCount($category_srl) {
            $args->category_srl = $category_srl;
            $output = executeQuery('faq.getChildCategoryCount',$args);
            if($output->data->count > 0) return true;
            return false;
        }

        /**
         * @brief get category list from cached file
         **/
        function getCategoryList($module_srl) {
            // find cached php file under cached category folder
            $filename = sprintf("./files/cache/faq_category/%s.php", $module_srl);

            // if file not exists, then create a new file
            if(!file_exists($filename)) {
                $oFaqController = &getController('faq');
                if(!$oFaqController->makeCategoryFile($module_srl)) return array();
            }

            @include($filename);

            // arrange category
            $faq_category = array();
            $this->_arrangeCategory($faq_category, $menu->list, 0);
            return $faq_category;
        }

        /**
         * @brief arrange category method
         **/
        function _arrangeCategory(&$faq_category, $list, $depth) {
            if(!count($list)) return;
            $idx = 0;
            $list_order = array();
            foreach($list as $key => $val) {
                $obj = null;
                $obj->mid = $val['mid'];
                $obj->module_srl = $val['module_srl'];
                $obj->category_srl = $val['category_srl'];
                $obj->parent_srl = $val['parent_srl'];
                $obj->title = $obj->text = $val['text'];
                $obj->color = $val['color'];
                $obj->question_count = $val['question_count'];
                $obj->depth = $depth;
                $obj->child_count = 0;
                $obj->childs = array();
                $obj->grant = $val['grant'];

                if(Context::get('mid') == $obj->mid && Context::get('category') == $obj->category_srl) $selected = true;
                else $selected = false;

                $obj->selected = $selected;

                $list_order[$idx++] = $obj->category_srl;

                // if there is a parent category
                if($obj->parent_srl) {

                    $parent_srl = $obj->parent_srl;
                    $question_count = $obj->question_count;

                    while($parent_srl) {
						// parent category question count add 1
                        $faq_category[$parent_srl]->question_count += $question_count;
                        $faq_category[$parent_srl]->childs[] = $obj->category_srl;
                        $faq_category[$parent_srl]->child_count = count($faq_category[$parent_srl]->childs);

                        $parent_srl = $faq_category[$parent_srl]->parent_srl;
                    }
                }

                $faq_category[$key] = $obj;

                if(count($val['list'])) $this->_arrangeCategory($faq_category, $val['list'], $depth+1);
            }
            $faq_category[$list_order[0]]->first = true;
            $faq_category[$list_order[count($list_order)-1]]->last = true;
        }

        /**
         * @brief get question list
         **/
        function getQuestionList($obj, $load_extra_vars=true) {
            // set sorting infor 
            if(!in_array($obj->sort_index, array('question_srl','list_order','update_order'))) $obj->sort_index = 'question_srl';
            if(!in_array($obj->order_type, array('desc','asc'))) $obj->order_type = 'asc';

            // set module_srl 
            if($obj->mid) {
                $oModuleModel = &getModel('module');
                $obj->module_srl = $oModuleModel->getModuleSrlByMid($obj->mid);
                unset($obj->mid);
            }

            // is module_srl is an array
            if(is_array($obj->module_srl)) $args->module_srl = implode(',', $obj->module_srl);
            else $args->module_srl = $obj->module_srl;

            // test exclude_module_srl
            if(is_array($obj->exclude_module_srl)) $args->exclude_module_srl = implode(',', $obj->exclude_module_srl);
            else $args->exclude_module_srl = $obj->exclude_module_srl;

            // set up args
            $args->category_srl = $obj->category_srl?$obj->category_srl:null;
            $args->sort_index = $obj->sort_index;
            $args->order_type = $obj->order_type;
            $args->page = $obj->page?$obj->page:1;
            $args->list_count = $obj->list_count?$obj->list_count:20;
            $args->page_count = $obj->page_count?$obj->page_count:10;
            $args->start_date = $obj->start_date?$obj->start_date:null;
            $args->end_date = $obj->end_date?$obj->end_date:null;
            $args->member_srl = $obj->member_srl;

            // if it has category, add its all sub-categories
            if($args->category_srl) {
                $category_list = $this->getCategoryList($args->module_srl);
                $category_info = $category_list[$args->category_srl];
                $category_info->childs[] = $args->category_srl;
                $args->category_srl = implode(',',$category_info->childs);
            }


            // set up query id
            $query_id = 'faq.getQuestionList';

            // set up question division
            $use_division = false;

            // set search args
            $searchOpt->search_target = $obj->search_target;
            $searchOpt->search_keyword = $obj->search_keyword;

			if($obj->search_keyword){
				$args->s_question = $obj->search_keyword;
				$args->s_answer = $obj->search_keyword;
			}

            /**
             * do not use division if sorting index=list_order or order!=asc
             **/
            if($args->sort_index != 'list_order' || $args->order_type != 'asc') $use_division = false;

			
            $output = executeQueryArray($query_id, $args);

            // if there is no data return
            if(!$output->toBool()||!count($output->data)) return $output;

            $idx = 0;
            $data = $output->data;
            unset($output->data);

            if(!isset($virtual_number))
            {
                $keys = array_keys($data);
                $virtual_number = $keys[0];
            }

            foreach($data as $key => $attribute) {

                $question_srl = $attribute->question_srl;
                if(!$GLOBALS['XE_QUESTION_LIST'][$question_srl]) {
                    $oQuestion = null;
                    $oQuestion = new faqItem();
                    $oQuestion->setAttribute($attribute, false);
                    $GLOBALS['XE_QUESTION_LIST'][$question_srl] = $oQuestion;
                }

                $output->data[$virtual_number] = $GLOBALS['XE_QUESTION_LIST'][$question_srl];
                $virtual_number --;

            }			        

            if(count($output->data)) {
                foreach($output->data as $number => $question) {
                    $output->data[$number] = $GLOBALS['XE_QUESTION_LIST'][$question->question_srl];
                }
            }
  
            return $output;
        }


        /**
         * @brief get question count based on module_srl
         **/
        function getQuestionCount($module_srl, $search_obj = NULL) {
            $args->module_srl = $module_srl;
            $args->s_question = $search_obj->s_question;
            $args->s_answer = $search_obj->s_answer;
            $args->s_member_srl = $search_obj->s_member_srl;
            $args->s_regdate = $search_obj->s_regdate;
            $args->category_srl = $search_obj->category_srl;

            $output = executeQuery('faq.getQuestionCount', $args);

            // return total count
            $total_count = $output->data->count;
            return (int)$total_count;
        }

    }
?>
