jQuery(document).ready(function($){

	//Modify select2 ajax request to include a parent_id parameter, used by custom_acf_taxonomy_hierarchy()
	acf.add_filter('select2_ajax_data', function( data, args, $input, field, instance ){

		//var target_field_key = 'field_6658b886e5430'; 

		if(field.data('type') == 'related_terms'){
			var parent_id = 0; //by default we want terms with parent of 0 (top level)
			
			if($input.val() != '' && $input.val() != null){ //if we have chosen one or more terms already
				var values = $input.val();
				parent_id = values.pop(); //get last selected term to use as the parent for the next query
			}
	
	
			data.parent = parent_id;
		}
		//console.log('field', field);

	  return data;

	});
	
});