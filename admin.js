jQuery(document).ready(function(e){
  populateChoices();
	jQuery('#preview-theme').live('click', function(e){
		// Figure out which preview image to load.
		var selected_theme = jQuery('#theme option:selected').val() 
		// Load it.
		var image_src = plugin_base_url + 'previews/widget-' + selected_theme + '.jpg';
		jQuery('#preview-pane').html('<img src="' + image_src + '"/>');
		jQuery('#ww-message').html('');
		return false;
	});
});



function populateSelect(select_id, data)
{
  var yoda = [];
  var vader = data;//eval('(' + data + ')');
  yoda.push('<option value=""></option>');
  for(var i=0;i<vader.length;i++)
  {
    yoda.push('<option value="' + vader[i][0] + '">' + vader[i][1] + '</option>');
  }
  jQuery('#' + select_id).html(yoda.join(''));
}

/** Populates select boxes and choices, if they've already been made. **/

function populateChoices()
{
  if (section_id_selected != '' &&
      topic_id_selected != '')
  {
    jQuery.getJSON(jsonp_base_url + 'api/choices-jsonp/section/1?jsoncallback=?',
              {},
              function(data){
                populateSelect('section_id', data);
                jQuery('#section_id option[value=' + section_id_selected + ']').attr('selected', 'selected');
    });
    jQuery.getJSON(jsonp_base_url + 'api/choices-jsonp/topic/' + section_id_selected + '?jsoncallback=?',
              {},
              function(data){
                populateSelect('topic_id', data);
                jQuery('#topic_id option[value=' + topic_id_selected + ']').attr('selected', 'selected');            
    });
    jQuery.getJSON(jsonp_base_url + 'api/choices-jsonp/category/' + topic_id_selected + '?jsoncallback=?',
              {},
              function(data){
                populateSelect('category_id', data);
                jQuery('#category_id option[value=' + category_id_selected + ']').attr('selected', 'selected');            
    });    
  }
  else
  {
    // Choices haven't been made yet.
    // Hide everything but step 1, and clear previous values.
    jQuery('.step-2,.step-3,.step-4,.step-5').hide();
    jQuery('#author-account').val('');
  }
  
  	jQuery('.step-1').live('change', function(e){
  		if (validProfileURL(jQuery('#author-account').val()))
  		{
				// Valid profile URL so clear errors.
				jQuery('#profile-url-errors').html('');
				
  			// Re-populate only if there aren't current selections
  			if (section_id_selected == '' &&
  				topic_id_selected == '')
  			{
  				// Populate next step, and display.
  				jQuery.getJSON(jsonp_base_url + 'api/choices-jsonp/section/1?jsoncallback=?',
  		              '',
  		              function(data){
  		              populateSelect('section_id', data);
  				});
  	  		    jQuery('.step-2').show();  				
  			}
  			else
  			{
  				// Show everything since we already have selections
  				jQuery('.step-2,.step-3,.step-4,.step-5').show();
  			}

  		}
  		else
  		{
				// Profile URL is invalid
				jQuery('#profile-url-errors').html('Please enter in the full URL of your profile page.');
  			// We don't have enough data yet, so hide everything else.
  			jQuery('.step-2,.step-3,.step-4,.step-5').hide();
  		}
  	});
  
    jQuery('.step-2').live('change',function(e){
      jQuery.getJSON(jsonp_base_url + 'api/choices-jsonp/topic/' + jQuery('#section_id option:selected').val() + '?jsoncallback=?',
                {},
                function(data){
                  populateSelect('topic_id', data);
                  jQuery('.step-1,.step-2,.step-3').show();
                  jQuery('.step-4,.step-5').hide();
              });
    });
    
    jQuery('.step-3').live('change',function(e){
      jQuery.getJSON(jsonp_base_url + 'api/choices-jsonp/category/' + jQuery('#topic_id option:selected').val() + '?jsoncallback=?',
                {},
                function(data){
                  populateSelect('category_id', data);
                  jQuery('.step-1,.step-2,.step-3,.step-4,.step-5').show();
                  //jQuery('.step-5').hide();
              });
    });
    
    jQuery('.step-4').live('change', function(e){
      jQuery('.step-1,.step-2,.step-3,.step-4,.step-5').show();
    });
    
    jQuery('#widget-create').live('submit', function(e){
      // If they've already created a widget, update. Otherwise, create.
      var jsonp_url = jsonp_base_url + 'api/widget/';
      if (section_id_selected != '' &&
          topic_id_selected != '')
      {
        jsonp_url = jsonp_url + 'update?' + (jQuery(this).serialize()).replace(/ww_/g, '').replace(/&rss_uri=.+&?/g, '') + '&jsoncallback=?';
      }
      else
      {
        jsonp_url = jsonp_url + 'create?' + (jQuery(this).serialize()).replace(/ww_/g, '').replace(/&rss_uri=.+&?/g,'') + '&with_security_code=1&jsoncallback=?';
      }
			
			// Don't perform the update/create unless the profile URL is valid and there
			// is at least a section and topic chosen.
			if (isReadyForSubmission())
			{
				jQuery.getJSON(jsonp_url,
                '',
                function(data){
                  // Populate the rss url and security code hidden fields, and post changes.
                  if (data['url'] != '' && data['security_code'] != '')
                  {
                    jQuery('#rss_uri').val(data['url']);
                    jQuery('#security_code').val(data['security_code']);
                    jQuery('#widget_config_id').val(data['widget_config_id']);
                    jQuery.post(jQuery('#widget-create').attr('action'),
                                jQuery("#widget-create").serialize(),
                                function(e){
                                  section_id_selected = jQuery('#section_id option:selected').val();
                                  topic_id_selected = jQuery('#topic_id option:selected').val();
                                  category_id_selected = jQuery('#category_id option:selected').val();                                  
                                });
                    jQuery('#ww-message').html('Your widget has been successfully udpated/created! ' +
																							 'Now <a href="/wp-admin/widgets.php">click here</a> ' +
																							 'and drag the Suite101 Writer\'s Widget to your sidebar.');
                  }
                  else
                  {
                    jQuery('#ww-message').html('There was a problem updating/creating your widget. Please ' +
                	  	  					   'ensure that you have entered your member ID, and made ' +
                			  				   'selections for Section and Topic.');
                  }
          });
			}
			jQuery('#preview-pane').html('');
      return false;
    });
}

function isReadyForSubmission()
{
	var ret = false;
	if (validProfileURL(jQuery('#author-account').val()) &&
			jQuery('#section_id option:selected').val() != '' &&
			jQuery('#topic_id option:selected').val() != '')
	{
		ret = true;
	}
	return ret;
}

/** Validation for Profile URL **/

function validProfileURL(url)
{
	var ret = false;
	var re = new RegExp('http://www.suite101.com/profile.cfm/[a-z0-9]+');
	var m = re.exec(url);
	if (m != null)
	{
		ret = true;
	}
	return ret;
}

/** JSONP callbacks for updates. **/

function updateSection(data)
{
  populateSelect('section_id', data);
}

function updateTopic(data)
{
  populateSelect('topic_id', data);
}

function updateCategory(data)
{
  populateSelect('category_id', data);
}
