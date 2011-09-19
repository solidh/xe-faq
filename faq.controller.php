<?php
/**
 * @class  faqController
 * @author NHN (developers@xpressengine.com)
 * @brief  faq module Controller class
 **/

class faqController extends faq {

	/**
	 * @brief initialization
	 **/
	function init() {
	}

	/**
	 * @brief insert/update question (faq_item)
	 **/
	function procFaqInsertQuestion() {

		// check permission
		if($this->module_info->module != "faq") return new Object(-1, "msg_invalid_request");
        $logged_info = Context::get('logged_info');

		// get form variables submitted
		$obj = Context::getRequestVars();
		$obj->module_srl = $this->module_srl;

		settype($obj->question, "string");
		if($obj->question == '') $obj->question = cut_str(strip_tags($obj->answer),20,'...');
		//Question Undefined
		if($obj->question == '') $obj->question = 'Question Undefined';

		// get faq module model
		$oFaqtModel = &getModel('faq');

		// get faq module controller
		$oFaqController = &getController('faq');

		// get question object
		$oQuestion = $oFaqtModel->getQuestion($obj->question_srl);

	   // if question exists, then update question
		if($oQuestion->isExists() && $oQuestion->question_srl == $obj->question_srl) {
			$output = $oFaqController->updateQuestion($oQuestion, $obj);
			$msg_code = 'success_updated';

		// if question not exists, then insert question
		} else {
			$output = $oFaqController->insertQuestion($obj);
			$msg_code = 'success_registed';
			$obj->question_srl = $output->get('question_srl');
		}

		// if there is an error, then stop
		if(!$output->toBool()) return $output;

		// return result
		$this->add('mid', Context::get('mid'));
		$this->add('question_srl', $output->get('question_srl'));

		// output success inserted/updated message
		$this->setMessage($msg_code);
	}

	/**
	 * @brief delete question
	 **/
	function procFaqDeleteQuestion() {
		// get question_srl
		$question_srl = Context::get('question_srl');

		// if question not exists, then alert an error
		if(!$question_srl) return $this->doError('msg_invalid_document');
		            
		// get faq module model
		$oFaqController = &getController('faq');

		// delete question
		$output = $oFaqController->deleteQuestion($question_srl);
		if(!$output->toBool()) return $output;

		// alert success deleted message
		$this->add('mid', Context::get('mid'));
		$this->add('page', $output->get('page'));
		$this->setMessage('success_deleted');

	}

	/**
	 * @brief insert question
	 **/
	function insertQuestion($obj, $manual_inserted = false) {

		// begin transaction
		$oDB = &DB::getInstance();
		$oDB->begin();

		$obj->ipaddress = $_SERVER['REMOTE_ADDR'];	//get client ip, or remote proxy server ip address 

		// $extra_vars serialize
		$obj->extra_vars = serialize($obj->extra_vars);

		// unset auto save
		unset($obj->_saved_doc_srl);
		unset($obj->_saved_doc_question);
		unset($obj->_saved_doc_answer);
		unset($obj->_saved_doc_message);


		// create a question_srl
		if(!$obj->question_srl) $obj->question_srl = getNextSequence();

		$oFaqModel = &getModel('faq');

		// get category list
		if($obj->question_srl) {
			$category_list = $oFaqModel->getCategoryList($obj->module_srl);
			if(!$category_list[$obj->category_srl]) $obj->category_srl = 0;
		}

		// set read_count, update_order&list_order
		if(!$obj->readed_count) $obj->readed_count = 0;
		$obj->update_order = $obj->list_order = getNextSequence() * -1;

		// md5 user password
		if($obj->password && !$obj->password_is_hashed) $obj->password = md5($obj->password);

		// set up log user inforamtion
		if(Context::get('is_logged')&&!$manual_inserted) {
			$logged_info = Context::get('logged_info');
			$obj->member_srl = $logged_info->member_srl;
			$obj->user_id = $logged_info->user_id;
			$obj->user_name = $logged_info->user_name;
			$obj->nick_name = $logged_info->nick_name;
			$obj->email_address = $logged_info->email_address;
			$obj->homepage = $logged_info->homepage;
		}

		// set up question
		settype($obj->question, "string");
		if($obj->question == '') $obj->question = cut_str(strip_tags($obj->anwser),20,'...');
		// Question Undefined
		if($obj->question == '') $obj->question = 'Question Undefined';

		if($logged_info->is_admin != 'Y') $obj->anwser = removeHackTag($obj->anwser);

		// if user is not a member, return error
		if(!$logged_info->member_srl && !$obj->nick_name) return new Object(-1,'msg_invalid_request');

		$obj->lang_code = Context::getLangType();

		// DB quesry
		$output = executeQuery('faq.insertQuestion', $obj);
		if(!$output->toBool()) {
			$oDB->rollback();
			return $output;
		}

		// update category count
		if($obj->category_srl) $this->updateCategoryCount($obj->module_srl, $obj->category_srl);


		// DB commit
		$oDB->commit();

		$output->add('question_srl',$obj->document_srl);
		$output->add('category_srl',$obj->category_srl);
		return $output;

	}


	/**
	 * @brief update question
	 **/
	function updateQuestion($source_obj, $obj) {

		if(!$source_obj->question_srl || !$obj->question_srl) return new Object(-1,'msg_invalied_request');

		// begin transaction
		$oDB = &DB::getInstance();
		$oDB->begin();

		$oModuleModel = &getModel('module');
		if(!$obj->module_srl) $obj->module_srl = $source_obj->get('module_srl');
		$module_srl = $obj->module_srl;

		// unset auto save
		unset($obj->_saved_doc_srl);
		unset($obj->_saved_doc_question);
		unset($obj->_saved_doc_answer);
		unset($obj->_saved_doc_message);

		$oFaqModel = &getModel('faq');

		// get updated category
		if($source_obj->get('category_srl')!=$obj->category_srl) {
			$category_list = $oFaqModel->getCategoryList($obj->module_srl);
			if(!$category_list[$obj->category_srl]) $obj->category_srl = 0;
		}

		// change update_order
		$obj->update_order = getNextSequence() * -1;

		// set up log user information
		if(Context::get('is_logged')) {
			$logged_info = Context::get('logged_info');
			if($source_obj->get('member_srl')==$logged_info->member_srl) {
				$obj->member_srl = $logged_info->member_srl;
			}
		}

		// then only question provider can update question
		if($source_obj->get('member_srl')&& !$obj->nick_name) {
			$obj->member_srl = $source_obj->get('member_srl');
		}

		// set up question
		settype($obj->question, "string");
		if($obj->question == '') $obj->question = cut_str(strip_tags($obj->anwser),20,'...');
		// Question Undefined
		if($obj->question == '') $obj->question = 'Question Undefined';

		if($logged_info->is_admin != 'Y') $obj->answer = removeHackTag($obj->answer);


		// DB update question
		$output = executeQuery('faq.updateQuestion', $obj);
		if(!$output->toBool()) {
			$oDB->rollback();
			return $output;
		}

		// update category count when the question's category changed 
		if($source_obj->get('category_srl') != $obj->category_srl || $source_obj->get('module_srl') == $logged_info->member_srl) {
			if($source_obj->get('category_srl') != $obj->category_srl) $this->updateCategoryCount($obj->module_srl, $source_obj->get('category_srl'));
			if($obj->category_srl) $this->updateCategoryCount($obj->module_srl, $obj->category_srl);
		}


		// DB commit
		$oDB->commit();

		// remove thumbnail
		FileHandler::removeDir(sprintf('files/cache/thumbnails/%s',getNumberingPath($obj->question_srl, 3)));

		$output->add('question_srl',$obj->question_srl);
		return $output;


	
	}

	/**
	 * @brief delete question
	 **/
	function deleteQuestion($question_srl, $is_admin = false) {


		// begin transaction
		$oDB = &DB::getInstance();
		$oDB->begin();

		// get faq model
		$oFaqModel = &getModel('faq');

		// get question object
		$oQuestion = $oFaqModel->getQuestion($question_srl, $is_admin);
		if(!$oQuestion->isExists() || $oQuestion->question_srl != $question_srl) return new Object(-1, 'msg_invalid_document');

		$args->question_srl = $question_srl;
		$output = executeQuery('faq.deleteQuestion', $args);
		if(!$output->toBool()) {
			$oDB->rollback();
			return $output;
		}

		// update category count when the question has beeen deleted
		if($oQuestion->get('category_srl')) $this->updateCategoryCount($oQuestion->get('module_srl'),$oQuestion->get('category_srl'));


		// remove thumbnail
		FileHandler::removeDir(sprintf('files/cache/thumbnails/%s',getNumberingPath($question_srl, 3)));

		// commit
		$oDB->commit();

		return $output;
	}


	/**
	 * @brief update category count
	 **/
	function updateCategoryCount($module_srl, $category_srl, $question_count = 0) {
		// get faq model
		$oFaqModel = &getModel('faq');
		if(!$question_count) $question_count = $oFaqModel->getCategoryQuestionCount($module_srl,$category_srl);
		$args->category_srl = $category_srl;
		$args->question_count = $question_count;
		$output = executeQuery('faq.updateCategoryCount', $args);
		if($output->toBool()) $this->makeCategoryFile($module_srl);

		return $output;
	}

	/**
	 * @brief insert faq category
	 **/
	function insertCategory($obj) {
		// set category list order
		if($obj->parent_srl) {
			// when insert a subcategory
			$oFaqModel = &getModel('faq');
			$parent_category = $oFaqModel->getCategory($obj->parent_srl);
			$obj->list_order = $parent_category->list_order;
			$this->updateCategoryListOrder($parent_category->module_srl, $parent_category->list_order+1);
			if(!$obj->category_srl) $obj->category_srl = getNextSequence();
		} else {
			$obj->list_order = $obj->category_srl = getNextSequence();
		}

		$output = executeQuery('faq.insertCategory', $obj);
		if($output->toBool()) {
			$output->add('category_srl', $obj->category_srl);
			$this->makeCategoryFile($obj->module_srl);
		}

		return $output;
	}

	/**
	 * @brief update category list order
	 **/
	function updateCategoryListOrder($module_srl, $list_order) {
		$args->module_srl = $module_srl;
		$args->list_order = $list_order;
		return executeQuery('faq.updateCategoryOrder', $args);
	}

	/**
	 * @brief update category
	 **/
	function updateCategory($obj) {
		$output = executeQuery('faq.updateCategory', $obj);
		if($output->toBool()) $this->makeCategoryFile($obj->module_srl);
		return $output;
	}

	/**
	 * @brief delete category
	 **/
	function deleteCategory($category_srl) {
		$args->category_srl = $category_srl;
		$oFaqModel = &getModel('faq');
		$category_info = $oFaqModel->getCategory($category_srl);

		// if the category has any child, then return an error
		$output = executeQuery('faq.getChildCategoryCount', $args);
		if(!$output->toBool()) return $output;
		if($output->data->count>0) return new Object(-1, 'msg_cannot_delete_for_child');

		// execute delete query
		$output = executeQuery('faq.deleteCategory', $args);
		if(!$output->toBool()) return $output;

		$this->makeCategoryFile($category_info->module_srl);

		unset($args);

		$args->target_category_srl = 0;
		$args->source_category_srl = $category_srl;
		$output = executeQuery('faq.updateQuestionCategory', $args);

		return $output;
	}

	/**
	 * @brief proc insert/update category
	 **/
	function procFaqInsertCategory($args = null) {
		if(!$args) $args = Context::gets('category_srl','module_srl','parent_srl','title','group_srls','color','mid');

		if(!$args->module_srl && $args->mid){
			$mid = $args->mid;
			unset($args->mid);
			$args->module_srl = $this->module_srl;
		}

		// get module information, check permission
		$oModuleModel = &getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
		$grant = $oModuleModel->getGrant($module_info, Context::get('logged_info'));
		if(!$grant->manager) return new Object(-1,'msg_not_permitted');


		$args->group_srls = str_replace('|@|',',',$args->group_srls);
		$args->parent_srl = (int)$args->parent_srl;

		$oFaqModel = &getModel('faq');

		$oDB = &DB::getInstance();
		$oDB->begin();

		// check whether the category exists
		if($args->category_srl) {
			$category_info = $oFaqModel->getCategory($args->category_srl);
			if($category_info->category_srl != $args->category_srl) $args->category_srl = null;
		}

		// update category
		if($args->category_srl) {
			$output = $this->updateCategory($args);
			if(!$output->toBool()) {
				$oDB->rollback();
				return $output;
			}

		// insert category
		} else {
			$output = $this->insertCategory($args);
			if(!$output->toBool()) {
				$oDB->rollback();
				return $output;
			}
		}

		// create XML file
		$xml_file = $this->makeCategoryFile($args->module_srl);

		$oDB->commit();

		$this->add('xml_file', $xml_file);
		$this->add('module_srl', $args->module_srl);
		$this->add('category_srl', $args->category_srl);
		$this->add('parent_srl', $args->parent_srl);

	}

	/**
	 * @brief proc move drag category
	 **/
	function procFaqMoveCategory() {
		$source_category_srl = Context::get('source_srl');

		// get parent_srl
		$parent_category_srl = Context::get('parent_srl');

		// get target_srl
		$target_category_srl = Context::get('target_srl');

		$oFaqModel = &getModel('faq');
		$source_category = $oFaqModel->getCategory($source_category_srl);

		// check permission
		$oModuleModel = &getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($source_category->module_srl);
		$grant = $oModuleModel->getGrant($module_info, Context::get('logged_info'));
		if(!$grant->manager) return new Object(-1,'msg_not_permitted');
		
		// parent_category_srl, move the category as the first child of the parent category 
		if($parent_category_srl > 0 || ($parent_category_srl == 0 && $target_category_srl == 0)){
			$parent_category = $oFaqModel->getCategory($parent_category_srl);

			$args->module_srl = $source_category->module_srl;
			$args->parent_srl = $parent_category_srl;
			$output = executeQuery('faq.getChildCategoryMinListOrder', $args);

			if(!$output->toBool()) return $output;
			$args->list_order = (int)$output->data->list_order;
			if(!$args->list_order) $args->list_order = 0;
			$args->list_order--;


			$source_args->category_srl = $source_category_srl;
			$source_args->parent_srl = $parent_category_srl;
			$source_args->list_order = $args->list_order;
			$output = $this->updateCategory($source_args);
			if(!$output->toBool()) return $output;

		// $target_category_srl, change category order
		}else if($target_category_srl > 0){
			$target_category = $oFaqModel->getCategory($target_category_srl);

			// $target_category, original list_order ++
			$output = $this->updateCategoryListOrder($target_category->module_srl, $target_category->list_order+1);
			if(!$output->toBool()) return $output;


			$source_args->category_srl = $source_category_srl;
			$source_args->parent_srl = $target_category->parent_srl;
			$source_args->list_order = $target_category->list_order+1;
			$output = $this->updateCategory($source_args);
			if(!$output->toBool()) return $output;
		}

		// update category XML file
		$xml_file = $this->makeCategoryFile($source_category->module_srl);

		// return variable
		$this->add('xml_file', $xml_file);
		$this->add('source_category_srl', $source_category_srl);

	}

	/**
	 * @brief proc delete category
	 **/
	function procFaqDeleteCategory() {

		$args = Context::gets('module_srl','category_srl');

		$oDB = &DB::getInstance();
		$oDB->begin();

		// check permission
		$oModuleModel = &getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
		$grant = $oModuleModel->getGrant($module_info, Context::get('logged_info'));
		if(!$grant->manager) return new Object(-1,'msg_not_permitted');

		$oFaqModel = &getModel('faq');

		// get category information
		$category_info = $oFaqModel->getCategory($args->category_srl);
		if($category_info->parent_srl) $parent_srl = $category_info->parent_srl;

		// if the category has any child, then return an error
		if($oFaqModel->getCategoryChlidCount($args->category_srl)) return new Object(-1, 'msg_cannot_delete_for_child');

		// delete category
		$output = $this->deleteCategory($args->category_srl);
		if(!$output->toBool()) {
			$oDB->rollback();
			return $output;
		}

		// update XML file
		$xml_file = $this->makeCategoryFile($args->module_srl);

		$oDB->commit();

		$this->add('xml_file', $xml_file);
		$this->add('category_srl', $parent_srl);
		$this->setMessage('success_deleted');
	}

	function makeCategoryFile($module_srl) {
		// if module not exists, return
		if(!$module_srl) return false;

		// get module information
		$oModuleModel = &getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		$mid = $module_info->mid;
		
		if(!is_dir('./files/cache/faq_category')) FileHandler::makeDir('./files/cache/faq_category');

		// set the name of cached files
		$xml_file = sprintf("./files/cache/faq_category/%s.xml.php", $module_srl);
		$php_file = sprintf("./files/cache/faq_category/%s.php", $module_srl);

		// get category list
		$args->module_srl = $module_srl;
		$args->sort_index = 'list_order';
		$output = executeQuery('faq.getCategoryList', $args);

		$category_list = $output->data;

		if(!$category_list) {
			FileHandler::removeFile($xml_file);
			FileHandler::removeFile($php_file);
			return false;
		}

		if(!is_array($category_list)) $category_list = array($category_list);

		$category_count = count($category_list);
		for($i=0;$i<$category_count;$i++) {
			$category_srl = $category_list[$i]->category_srl;
			if(!preg_match('/^[0-9,]+$/', $category_list[$i]->group_srls)) $category_list[$i]->group_srls = '';
			$list[$category_srl] = $category_list[$i];
		}

		// if there is not a category list, then return an empty xml file
		if(!$list) {
			$xml_buff = "<root />";
			FileHandler::writeFile($xml_file, $xml_buff);
			FileHandler::writeFile($php_file, '<?php if(!defined("__ZBXE__")) exit(); ?>');
			return $xml_file;
		}

		// check if $list is array
		if(!is_array($list)) $list = array($list);

		// set up tree nodes 
		foreach($list as $category_srl => $node) {
			$node->mid = $mid;
			$parent_srl = (int)$node->parent_srl;
			$tree[$parent_srl][$category_srl] = $node;
		}

		// set the common header
		$header_script =
			'$lang_type = Context::getLangType(); '.
			'$is_logged = Context::get(\'is_logged\'); '.
			'$logged_info = Context::get(\'logged_info\'); '.
			'if($is_logged) {'.
				'if($logged_info->is_admin=="Y") $is_admin = true; '.
				'else $is_admin = false; '.
				'$group_srls = array_keys($logged_info->group_list); '.
			'} else { '.
				'$is_admin = false; '.
				'$group_srsl = array(); '.
			'} '."\n";

		// create cached xml file
		$xml_header_buff = '';
		$xml_body_buff = $this->getXmlTree($tree[0], $tree, $module_info->site_srl, $xml_header_buff);
		$xml_buff = sprintf(
			'<?php '.
			'define(\'__ZBXE__\', true); '.
			'require_once(\''.FileHandler::getRealPath('./config/config.inc.php').'\'); '.
			'$oContext = &Context::getInstance(); '.
			'$oContext->init(); '.
			'header("Content-Type: text/xml; charset=UTF-8"); '.
			'header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); '.
			'header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); '.
			'header("Cache-Control: no-store, no-cache, must-revalidate"); '.
			'header("Cache-Control: post-check=0, pre-check=0", false); '.
			'header("Pragma: no-cache"); '.
			'%s'.
			'%s '.
			'$oContext->close();'.
			'?>'.
			'<root>%s</root>',
			$header_script,
			$xml_header_buff,
			$xml_body_buff
		);

		// create cached php file
		$php_output = $this->getPhpCacheCode($tree[0], $tree, $module_info->site_srl, $php_header_buff);
		$php_buff = sprintf(
			'<?php '.
			'if(!defined("__ZBXE__")) exit(); '.
			'%s; '.
			'%s; '.
			'$menu->list = array(%s); '.
			'?>',
			$header_script,
			$php_header_buff,
			$php_output['buff']
		);

		// write content to file
		FileHandler::writeFile($xml_file, $xml_buff);
		FileHandler::writeFile($php_file, $php_buff);
		return $xml_file;

	}

	/**
	 * @brief array로 정렬된 노드들을 parent_srl을 참조하면서 recursive하게 돌면서 xml 데이터 생성
	 * 메뉴 xml파일은 node라는 tag가 중첩으로 사용되며 이 xml doc으로 관리자 페이지에서 메뉴를 구성해줌\n
	 * (tree_menu.js 에서 xml파일을 바로 읽고 tree menu를 구현)
	 **/
	function getXmlTree($source_node, $tree, $site_srl, &$xml_header_buff) {
		if(!$source_node) return;

		foreach($source_node as $category_srl => $node) {
			$child_buff = "";

			// 자식 노드의 데이터 가져옴
			if($category_srl && $tree[$category_srl]) $child_buff = $this->getXmlTree($tree[$category_srl], $tree, $site_srl, $xml_header_buff);

			// 변수 정리
			$expand = $node->expand;
			$group_srls = $node->group_srls;
			$mid = $node->mid;
			$module_srl = $node->module_srl;
			$parent_srl = $node->parent_srl;
			$color = $node->color;
			// node->group_srls값이 있으면
			if($group_srls) $group_check_code = sprintf('($is_admin==true||(is_array($group_srls)&&count(array_intersect($group_srls, array(%s)))))',$group_srls);
			else $group_check_code = "true";

			$title = $node->title;
			$oModuleAdminModel = &getAdminModel('module');
			$langs = $oModuleAdminModel->getLangCode($site_srl, $title);
			if(count($langs)) foreach($langs as $key => $val) $xml_header_buff .= sprintf('$_titles[%d]["%s"] = "%s"; ', $category_srl, $key, str_replace('"','\\"',htmlspecialchars($val)));

			$attribute = sprintf(
					'mid="%s" module_srl="%d" node_srl="%d" parent_srl="%d" category_srl="%d" text="<?php echo (%s?($_titles[%d][$lang_type]):"")?>" url="%s"  color="%s" question_count="%d" ',
					$mid,
					$module_srl,
					$category_srl,
					$parent_srl,
					$category_srl,
					$group_check_code,
					$category_srl,
					getUrl('','mid',$node->mid,'category',$category_srl),
					$color,
					$node->question_count
			);

			if($child_buff) $buff .= sprintf('<node %s>%s</node>', $attribute, $child_buff);
			else $buff .=  sprintf('<node %s />', $attribute);
		}
		return $buff;
	}

	/**
	 * @brief array로 정렬된 노드들을 php code로 변경하여 return
	 * 메뉴에서 메뉴를 tpl에 사용시 xml데이터를 사용할 수도 있지만 별도의 javascript 사용이 필요하기에
	 * php로 된 캐시파일을 만들어서 db이용없이 바로 메뉴 정보를 구할 수 있도록 한다
	 * 이 캐시는 ModuleHandler::displayContent() 에서 include하여 Context::set() 한다
	 **/
	function getPhpCacheCode($source_node, $tree, $site_srl, &$php_header_buff) {
		$output = array("buff"=>"", "category_srl_list"=>array());
		if(!$source_node) return $output;

		// 루프를 돌면서 1차 배열로 정리하고 include할 수 있는 php script 코드를 생성
		foreach($source_node as $category_srl => $node) {

			// 자식 노드가 있으면 자식 노드의 데이터를 먼저 얻어옴
			if($category_srl&&$tree[$category_srl]) $child_output = $this->getPhpCacheCode($tree[$category_srl], $tree, $site_srl, $php_header_buff);
			else $child_output = array("buff"=>"", "category_srl_list"=>array());

			// 현재 노드의 url값이 공란이 아니라면 category_srl_list 배열값에 입력
			$child_output['category_srl_list'][] = $node->category_srl;
			$output['category_srl_list'] = array_merge($output['category_srl_list'], $child_output['category_srl_list']);

			// node->group_srls값이 있으면
			if($node->group_srls) $group_check_code = sprintf('($is_admin==true||(is_array($group_srls)&&count(array_intersect($group_srls, array(%s)))))',$node->group_srls);
			else $group_check_code = "true";

			// 변수 정리
			$selected = '"'.implode('","',$child_output['category_srl_list']).'"';
			$child_buff = $child_output['buff'];
			$expand = $node->expand;

			$title = $node->title;
			$oModuleAdminModel = &getAdminModel('module');
			$langs = $oModuleAdminModel->getLangCode($site_srl, $title);
			if(count($langs)) foreach($langs as $key => $val) $php_header_buff .= sprintf('$_titles[%d]["%s"] = "%s"; ', $category_srl, $key, str_replace('"','\\"',htmlspecialchars($val)));

			// 속성을 생성한다 ( category_srl_list를 이용해서 선택된 메뉴의 노드에 속하는지를 검사한다. 꽁수지만 빠르고 강력하다고 생각;;)
			$attribute = sprintf(
				'"mid" => "%s", "module_srl" => "%d","node_srl"=>"%s","category_srl"=>"%s","parent_srl"=>"%s","text"=>$_titles[%d][$lang_type],"selected"=>(in_array(Context::get("category"),array(%s))?1:0),"color"=>"%s", "list"=>array(%s),"question_count"=>"%d","grant"=>%s?true:false',
				$node->mid,
				$node->module_srl,
				$node->category_srl,
				$node->category_srl,
				$node->parent_srl,
				$node->category_srl,
				$selected,
				$node->color,
				$child_buff,
				$node->question_count,
				$group_check_code
			);

			// buff 데이터를 생성한다
			$output['buff'] .=  sprintf('%s=>array(%s),', $node->category_srl, $attribute);
		}
		return $output;
	}


}
?>
