/**
 * @file   modules/faq/js/daq_admin.js
 * @author NHN (developers@xpressengine.com)
 * @brief  faq module template javascript
 **/


function completeQuestionInserted(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var mid = ret_obj['mid'];
    var question_srl = ret_obj['question_srl'];
    var category_srl = ret_obj['category_srl'];


    var url;
    if(!question_srl)
    {
        url = current_url.setQuery('mid',mid).setQuery('act','');
    }
    else
    {
        url = current_url.setQuery('mid',mid).setQuery('question_srl',question_srl).setQuery('act','');
    }
    if(category_srl) url = url.setQuery('category',category_srl);
    location.href = url;
}

/* after insert faq module */
function completeInsertFaq(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    var page = ret_obj['page'];
    var module_srl = ret_obj['module_srl'];

    alert(message);

    var url = current_url.setQuery('act','dispFaqAdminFaqInfo');
    if(module_srl) url = url.setQuery('module_srl',module_srl);
    if(page) url.setQuery('page',page);
    location.href = url;
}

/* after delete faq module */
function completeDeleteFaq(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];
    alert(message);

    var url = current_url.setQuery('act','dispFaqAdminContent').setQuery('module_srl','');
    if(page) url = url.setQuery('page',page);
    location.href = url;
}

/* mass configuration*/
function doCartSetup(url) {
    var module_srl = new Array();
    jQuery('#fo_list input[name=cart]:checked').each(function() {
        module_srl[module_srl.length] = jQuery(this).val();
    });

    if(module_srl.length<1) return;

    url += "&module_srls="+module_srl.join(',');
    popopen(url,'modulesSetup');
}

function createCategory(obj){
	var title = jQuery("input[name=category_title]",obj.form).val();
	var module_srl = jQuery("input[name=module_srl]",obj.form).val();

	if(title == '') return false;

	var params = new Array();
	params['mid'] = current_mid;
	params['module_srl'] = module_srl;
	params['title'] = title;

	var response_tags = new Array('error','message','page','mid');
	exec_xml('faq', 'procFaqInsertCategory', params, completeInsertCategory, response_tags);

}

function completeInsertCategory(ret_obj, response_tags, args, fo_obj) {
	var error = ret_obj['error'];
	var message = ret_obj['message'];
	var mid = ret_obj['mid'];
	document.location.href=current_url.setQuery('category_srl','');
}

function updateCategory(obj){
	var title = jQuery("input[name=category_title]",obj.form).val();
	var module_srl = jQuery("input[name=module_srl]",obj.form).val();
	var category_srl = jQuery("input[name=category_srl]",obj.form).val();

	if(title == '' || category_srl == '') return false;

	var params = new Array();
	params['mid'] = current_mid;
	params['module_srl'] = module_srl;
	params['category_srl'] = category_srl;
	params['title'] = title;


	var response_tags = new Array('error','message','page','mid','selected_category');
	exec_xml('faq', 'procFaqInsertCategory', params, completeInsertCategory, response_tags);

}

function deleteCategory(obj){
	var category_srl = jQuery("input[name=category_srl]",obj.form).val();

	var params = new Array();
	params['mid'] = current_mid;
	params['category_srl'] = category_srl;

	var response_tags = new Array('error','message','page','mid');
	exec_xml('faq', 'procFaqDeleteCategory', params, completeDeleteCategory, response_tags);
}

function completeDeleteCategory(ret_obj, response_tags, args, fo_obj) {
	var error = ret_obj['error'];
	var message = ret_obj['message'];
	var mid = ret_obj['mid'];
	document.location.href=current_url.setQuery('category_srl','');
}