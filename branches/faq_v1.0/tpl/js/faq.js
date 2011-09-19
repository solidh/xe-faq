/**
 * @file   modules/faq/js/faq.js
 * @author NHN (developers@xpressengine.com)
 * @brief  faq module template javascript
 **/

/* after question inserted */
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


/* after question deleted */
function completeDeleteQuestion(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var mid = ret_obj['mid'];
    var page = ret_obj['page'];

    var url = current_url.setQuery('mid',mid).setQuery('act','').setQuery('question_srl','');
    if(page) url = url.setQuery('page',page);


    location.href = url;
}


