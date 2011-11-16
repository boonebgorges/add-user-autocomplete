jQuery(document).ready(function($) {
	var options = {
		serviceUrl: ajaxurl,
		width: 300,
		delimiter: /(,|;)\s*/,
		onSelect: function(){alert('tr');},
		deferRequestBy: 0, //miliseconds
		params: { 
			action: 'add_to_blog_find_user', 
			blog_id: $('#adduser-blog-id').val() 
		},
		noCache: true //set to true, to disable caching
	};
	
	a = $('#adduser-email').autocomplete(options);

	
},(jQuery));