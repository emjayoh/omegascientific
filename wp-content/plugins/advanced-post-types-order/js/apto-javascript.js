
    function toggle_thumbnails()
        {
            jQuery('#opt_sortable .post_type_thumbnail').toggle();        
        }
        
    function apto_change_taxonomy(element)
        {
            //select the default category (0)
            jQuery('#apto_form #cat').val(jQuery("#apto_form #cat option:first").val());
            jQuery('#apto_form').submit();
        }