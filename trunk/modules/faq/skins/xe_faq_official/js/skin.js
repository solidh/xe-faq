jQuery(function($){
	$('ul.faq_lst').find('li').each(function(index){
		$(this).find('.btn_show,.title').click(function(){
			$(this).parent().find('a').blur();
			if($(this).parent().attr('class') == 'on')
				$(this).parent().attr('class','off');
			else
				$(this).parent().attr('class','on');
			return false;
		});
	});
});