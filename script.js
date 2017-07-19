jQuery(window).load(function(){	
jQuery('.smbox').hover(function(){
  if(jQuery(this).find('div div.smbox_info').length>0){
	  jQuery(this).find('div div.smbox_info').stop().animate({'top':'0'});
  }
  },
  function(){
	  jQuery(this).find('div div.smbox_info').stop().animate({'top':'300px'});
  });	
});

function FeedImageLoaded( v, id){
	var image = new Image();
	image.src = v;
	image.onload = function () {
		jQuery('#'+id).attr('src', image.src);
		jQuery('#'+id).removeClass('loader');
	}
}