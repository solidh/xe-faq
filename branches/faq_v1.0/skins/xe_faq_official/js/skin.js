jQuery(function($){
	$('ul.faq_lst').find('li').each(function(index){
		$(this).attr('class','off')
		$(this).find('.btn_show,.title').click(function(){
			$(this).parent().toggleClass('on off');
			return false;
		});
	});
});