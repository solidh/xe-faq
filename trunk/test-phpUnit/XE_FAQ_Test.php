<?php 
require('config/config.inc.php');

class XE_FAQ_Test extends PHPUnit_Framework_TestCase{

    protected $faqClass,$faqModel,$faqAdminView,$faqView,$faqAdminController,$faqController;
	protected $message;
 
    protected function setUp()
    {
		ini_set('memory_limit', '-1');
		$context_instance = &Context::getInstance();
		$db_info = $context_instance->loadDBInfo();

		$this->faqClass = &getClass("faq");
		$this->faqModel = &getModel("faq");
		$this->faqAdminView = &getAdminView("faq");
		$this->faqView = &getView("faq");
		$this->faqAdminController = &getAdminController("faq");
		$this->faqController = &getController("faq");
		$this->faqClass = &getClass("faq");
	}

	// Test FAQ Basic Class: Faq module install, Faq module update
	public function testFaqInstall()
    {
		$this->faqClass->moduleInstall();
		$needUpdate = $this->faqClass->checkUpdate();
		if($needUpdate)
			$this->faqClass->moduleUpdate();
	}

	// Test FAQ Admin View: Faq module lists page, Faq Creation page, Faq info page, Manage Categories page, Addtional Setup page and Faq Delete page
	public function testFaqAdminView()
    {
		$this->faqAdminView->init();
		//test for display the faq list page 
		$this->faqAdminView->dispFaqAdminContent();
		//test for display the faq creation page (no selected module_srl)
		$this->faqAdminView->dispFaqAdminInsertFaq();

		//model an existing faq module
		$oModuleAdminModel = &getAdminModel("module");
		$args->module = 'faq';
		$faq_modules = $oModuleAdminModel->getModuleMidList($args)->data;
		$module_srl = $faq_modules[1]->module_srl;
		Context::set('module_srl',$module_srl);

		$this->faqAdminView->init();
		//test for display faq info page
		$this->faqAdminView->dispFaqAdminFaqInfo();
		//test for display faq category management page
		$this->faqAdminView->dispFaqAdminCategoryInfo();
		//test for display faq category addition setup page
		$this->faqAdminView->dispFaqAdminFaqAdditionSetup();
		//test for display faq module deletion page
		$this->faqAdminView->dispFaqAdminDeleteFaq();
	}

	// Test FAQ Admin Controller: insert faq module, update faq module and delete faq module
	public function testFaqAdminController(){
		//test for insert a faq module 
		$oModuleAdminModel = &getAdminModel("module");
		$args->module = 'faq';
		$faq_modules = $oModuleAdminModel->getModuleMidList($args)->data;
		$before_modules_count = sizeOf($faq_modules);
		
		$this->InsertFaqModule();
		$faq_modules = $oModuleAdminModel->getModuleMidList($args)->data;
		$after_modules_count = sizeOf($faq_modules);
		//assert the number of faq modules increased by 1
		$this->assertEquals(1, $after_modules_count-$before_modules_count);

		//test for update the faq module
		$module_srl = $faq_modules[$after_modules_count]->module_srl;
		$mid = $faq_modules[$after_modules_count]->mid;
		$this->UpdateFaqModule($module_srl,$mid);
		$faq_modules = $oModuleAdminModel->getModuleMidList($args)->data;
		$updated_description = $faq_modules[$after_modules_count]->description;
		//assert the description of the module has been updated
		$this->assertEquals('FAQ module updated', $updated_description);

		//test for delete the faq module
		$current_modules_count = $after_modules_count;
		$module_srl = $faq_modules[$after_modules_count]->module_srl;
		$this->DeleteFaqModule($module_srl);
		$faq_modules = $oModuleAdminModel->getModuleMidList($args)->data;
		$after_modules_count = sizeOf($faq_modules);
		$this->assertEquals(1, $current_modules_count-$after_modules_count);

	}

	// Test FAQ View: Faq home page, Faq writting page amd Faq delete page
	public function testFaqView()
    {
		//model a login user
		$memberController = &getController("member");
		$output = $memberController->procMemberLogin('xe_admin','@xe_part@');

		//model a existing faq module
		$oModuleAdminModel = &getAdminModel("module");
		$args->module = 'faq';
		$faq_modules = $oModuleAdminModel->getModuleMidList($args)->data;
		$module_srl = $faq_modules[1]->module_srl;
		$oModuleModel = &getModel("module");
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		
		$this->faqView->module_info = $module_info;
		$this->faqView->init();

		//test for display the faq home page
		$this->faqView->dispFaqContent();
		//test for display the faq writing page, only login user
		$this->faqView->dispFaqWrite();
		//test for display the faq deletion page, only login user
		$this->faqView->dispFaqDelete();
	}

	// Test FAQ Controller: insert question, update question and delete question
	public function testFaqControllerManageQuestion()
    {
		//model a login user
		$memberController = &getController("member");
		$output = $memberController->procMemberLogin('xe_admin','@xe_part@');

		//model: get a faq module
		$oModuleAdminModel = &getAdminModel("module");
		$args->module = 'faq';
		$faq_modules = $oModuleAdminModel->getModuleMidList($args)->data;
		$obj->module_srl = $faq_modules[1]->module_srl;

		//test for insert a question to the faq
		$this->faqModel->init();
		$question_list = $this->faqModel->getQuestionList($obj)->data;
		$before_question_count = sizeOf($question_list);
		$this->InsertFaqQuestion($obj->module_srl);
		$question_list = $this->faqModel->getQuestionList($obj)->data;
		$after_question_count = sizeOf($question_list);
		//assert the number of faq questions increased by 1
		$this->assertEquals(1, $after_question_count-$before_question_count);

		//test for update the question of the faq
		$question_srl = $question_list[1]->question_srl;
		$this->UpdateFaqQuestion($question_srl);
		$this->faqModel->init();
		unset($GLOBALS['XE_QUESTION_LIST'][$question_srl]);
		$oQuestion = $this->faqModel->getQuestion($question_srl);
		//assert the question and answer have been updated
		$this->assertEquals('test question update', $oQuestion->get('question'));
		$this->assertEquals('test answer update', $oQuestion->get('answer'));

		//test for delete the question of the faq
		$before_question_count = sizeOf($question_list);
		$this->DeleteFaqQuestion($question_srl);
		$question_list = $this->faqModel->getQuestionList($obj)->data;
		$after_question_count = sizeOf($question_list);
		//assert the number of faq questions decreased by 1
		$this->assertEquals(1, $before_question_count-$after_question_count);

	}

	// Test FAQ Controller: insert category, update category and delete category
	public function testFaqControllerManageCategories()
    {
		//model a login user
		$memberController = &getController("member");
		$output = $memberController->procMemberLogin('xe_admin','@xe_part@');

		//model: get a faq module
		$oModuleAdminModel = &getAdminModel("module");
		$args->module = 'faq';
		$faq_modules = $oModuleAdminModel->getModuleMidList($args)->data;
		$module_srl = $faq_modules[1]->module_srl;

		//test for insert a faq category
		$category_list = $this->faqModel->getCategoryList($module_srl);
		$before_categories_count = sizeOf($category_list);
		$this->InsertFaqCategory($module_srl);
		$category_list = $this->faqModel->getCategoryList($module_srl);
		$after_categories_count = sizeOf($category_list);
		//assert the number of faq questions increased by 1
		$this->assertEquals(1, $after_categories_count-$before_categories_count);

		//test for update the faq category
		$category_srl = $category_list[$after_categories_count-1]->category_srl;
		$this->UpdateFaqCategory($module_srl,$category_srl);
		$oCategory = $this->faqModel->getCategory($category_srl);
		//assert the title of the category has been updated
		$this->assertEquals('test category update', $oCategory->title);

		//test for delete the faq category
		$before_categories_count = $after_categories_count;
		$this->DeleteFaqCategory($category_srl);
		$category_list = $this->faqModel->getCategoryList($module_srl);
		$after_categories_count = sizeOf($category_list);
		//assert the number of faq questions decreased by 1
		$this->assertEquals(1, $before_categories_count-$after_categories_count);

	}

	// Insert FAQ module
	public function InsertFaqModule(){
		$randon_string = $this->genRandomString();
		$args->faq_name = "test_faq_module_".$randon_string;
		$args->use_category = 'Y';
		$args->allow_keywords = 'Y';
		$this->faqAdminController->init();
		$this->faqAdminController->procFaqAdminInsertFaq($args);
	}

	// Update FAQ module
	public function UpdateFaqModule($module_srl,$mid){
		$args->module_srl = $module_srl;
		$args->faq_name = $mid;
		$args->description = "FAQ module updated";
		$this->faqAdminController->init();
		$this->faqAdminController->procFaqAdminInsertFaq($args);
	}

	// Delete FAQ module
	public function DeleteFaqModule($module_srl){
		$this->faqAdminController->init();
		Context::set("module_srl",$module_srl);
		$output = $this->faqAdminController->procFaqAdminDeleteFaq();
	}

	// Insert FAQ question
	public function InsertFaqQuestion($module_srl){
		$moduleModel = &getModel("module");
		$module_info = $moduleModel->getModuleInfoByModuleSrl($module_srl);
		$this->faqController->module_info = $module_info;
		Context::set("question", "test question", 1);
        Context::set("answer", "test answer", 1);
		$this->faqController->init();
		$this->faqController->procFaqInsertQuestion();
	}

	// Update FAQ question
	public function UpdateFaqQuestion($question_srl){
		Context::set("question_srl", $question_srl, 1);
		Context::set("question", "test question update", 1);
		Context::set("answer", "test answer update", 1);
		$this->faqController->procFaqInsertQuestion();
	}

	// Delete FAQ question
	public function DeleteFaqQuestion($question_srl){
		Context::set("question_srl", $question_srl, 1);
		$this->faqController->procFaqDeleteQuestion();
	}

	// Insert FAQ category
	public function InsertFaqCategory($module_srl){
		$args->module_srl = $module_srl;
		$args->title = "test category";
		$this->faqController->procFaqInsertCategory($args);
	}

	// Update FAQ category
	public function UpdateFaqCategory($module_srl,$category_srl){
		$args->module_srl = $module_srl;
		$args->category_srl = $category_srl;
		$args->title = "test category update";
		$this->faqController->procFaqInsertCategory($args);
	}

	// Delete FAQ category
	public function DeleteFaqCategory($category_srl){
		Context::set('category_srl',$category_srl);
		$this->faqController->procFaqDeleteCategory();
	}

	public function genRandomString() {
		$length = 5;
		$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
		$string ='';    

		for ($p = 0; $p < $length; $p++) {
			$string .= $characters[mt_rand(0, strlen($characters))];
		}
		return $string;
	}

}

?>