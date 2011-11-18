jQuery(document).ready(function($) {
	var ainput = $('#adduser-email');
	
	$(ainput).after('<ul id="add-to-blog-users"></ul>');
	$(ainput).bind('keyup',function(event){
		if( ( 45 < event.keyCode && event.keyCode < 91 ) || event.keyCode == 8 ) {
			$(ainput).addClass('loading');
		}
	});
	
	var ulist = $('#add-to-blog-users');
	
	var options = {
		serviceUrl: ajaxurl,
		fnFormatResult: a2bFormatResult,
		width: 300,
		delimiter: /(,|;)\s*/,
		onSelect: function(dname,user_id){ btest(dname,user_id); },
		deferRequestBy: 500, // miliseconds
		params: { 
			action: 'add_to_blog_find_user'
		},
		noCache: true //set to true, to disable caching
	};
	
	a = $(ainput).autocomplete(options);

	function btest(dname,user_id){
		$(ulist).append('<li class="atb-user" id="atb-user-' + user_id + '">' + dname + '</li>');
	}
	
	function a2bFormatResult(value, data, currentValue) {
		$(ainput).removeClass('loading');
		
		var a2breEscape = new RegExp('(\\' + ['/', '.', '*', '+', '?', '|', '(', ')', '[', ']', '{', '}', '\\'].join('|\\') + ')', 'g');
		var a2bpattern = '(' + currentValue.replace(a2breEscape, '\\$1') + ')';
		return value.replace(new RegExp(a2bpattern, 'gi'), '<strong>$1<\/strong>');
	}
	
},(jQuery));