jQuery(document).ready(function($) {
	var ainput = $('#adduser-email');
	
	$(ainput).after('<ul id="add-to-blog-users"></ul>');
	$(ainput).bind('keyup',function(event){
		// Delete, backspace, 0-9, a-z
		if( ( 45 < event.keyCode && event.keyCode < 91 ) || event.keyCode == 8 ) {
			$(ainput).addClass('loading');
		}
	});
	
	var ulist = $('#add-to-blog-users');
	
	var options = {
		serviceUrl: ajaxurl,
		width: 300,
		delimiter: /(,|;)\s*/,
		onSelect: function(dname,user_id){ a2bAddItem(dname,user_id); },
		onReturn: function(){$(ainput).removeClass('loading');},
		deferRequestBy: 500, // miliseconds
		params: { 
			action: 'add_to_blog_find_user'
		},
		noCache: true //set to true, to disable caching
	};
	
	a = $(ainput).autocomplete(options);

	function a2bAddItem(dname,user_id){
		$(ulist).append('<li class="atb-user" id="atb-user-' + user_id + '">' + dname + '</li>');
	}
	
},(jQuery));