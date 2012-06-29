(function ($){
	var searchInPlace = function(){
		$('.item', '.search-in-place').live('mouseover mouseout', function(){$(this).toggleClass('active');})
	};

	searchInPlace.prototype = {
		active : null,
		search : '',
		config:{
			min 		 : codepeople_search_in_place.char_number,
			image_width  : 50,
			image_height : 50,
			colors		 : ['#F4EFEC', '#B5DCE1', '#F4E0E9', '#D7E0B1', '#F4D9D0', '#D6CDC8', '#F4E3C9', '#CFDAF0'],
			areas		 : ['div.hentry', '#content', '#main', 'div.content', '#middle', '#container', '#wrapper']
		},
		
		autocomplete : function(){
			var me = this;
			$(("input[name='s']")).attr('autocomplete', 'off').bind('keyup focus', 
				function(){
					var s = $(this),
						v = s.val();
					if(me.checkString(v)){
						me.getResults(s);
					}else{
						if(me.search.indexOf(v) != 0){
							$('.search-in-place').hide();
						}
					}	
				}
			).blur(function(){
				setTimeout(function(){$('.search-in-place').hide();}, 150);
			});
		},
		
		checkString : function(v){
			return this.config.min <= v.length;
		},
		
		getResults : function(e){
			if(e.val() == this.search){
				$('.search-in-place').show();
				return;
			}	
				
			this.search = e.val();
			var me 	= this,
				f	= e.parents('form'), // Forms that contain the search box
				o 	= (f.length) ? f.offset() : null,
				p 	= {'s': me.search},
				s 	= $('<div class="search-in-place"></div>');
			
			// For wp_ajax
			p.action = "search_in_place";
			
			// Stop all search actions
			if(me.active) me.active.abort();
			
			// Remove results container inserted previously
			$('.search-in-place').remove();
			
			// Set the results container
			if(o){
				s.width(f.width()).css({'left' : o.left, 'top' : o.top + f.height()}).appendTo('body');
				me.displayLoading(s);
				
				me.active = jQuery.get( codepeople_search_in_place.root + '/wp-admin/admin-ajax.php', p, function(r){
					me.displayResult(r, s);
					me.removeLoading(r, s);
				}, "json");
			}
		},
		
		displayResult : function(o, e){
			var me = this,
				s = '';
			
			for(var t in o){
				s += '<div class="label">'+t+'</div>';
				var l = o[t];
				for(var i=0, h = l.length; i < h; i++){
					s += '<div class="item">'; 
					if(l[i].thumbnail){ 
						s += '<div class="thumbnail"><img src="'+l[i].thumbnail+'" style="visibility:hidden;float:left;position:absolute;" /></div><div class="data" style="margin-left:'+(me.config.image_width+5)+'px;">';
					}else{
						s += '<div class="data">';
					}	
					
					s += '<span class="title"><a href="'+l[i].link+'">'+l[i].title+'</a></span>'
					if(l[i].resume) s += '<span class="resume">'+l[i].resume+'</span>';
					if(l[i].author) s += '<span class="author">'+l[i].author+'</span>';
					if(l[i].date) s += '<span class="date">'+l[i].date+'</span>';
					s += '</div></div>';
				}
			}
			
			e.prepend(s).find('.thumbnail img').load(function(){
				var size = me.imgSize(this);
				$(this).width(size.w).height(size.h).css('visibility', 'visible');
			});
		},
		
		imgSize : function(e){
			e = $(e);
			
			var w = e.width(),
				h = e.height(),
				nw, nh;
			
			if(w > this.config.image_width){
				nw = this.config.image_width;
				nh = nw/w*h;
				w = nw; h = nh;
			}
			
			if(h > this.config.image_height){
				nh = this.config.image_height;
				nw = nh/h*w;
				w = nw; h = nh;
			}
			
			return {'w':w, 'h':h};
		},
		
		displayLoading : function(e){
			e.append('<div class="label"><div class="loading"></div></div>');
		},
		
		removeLoading : function(c, e){
			var s = (typeof c.length != 'undefined') ? codepeople_search_in_place.empty : '<a href="?s='+this.search+'&submit=Search">'+codepeople_search_in_place.more+' &gt;</a>';
			e.find('.loading').parent().addClass('more').html(s);
			
		},
		
		highlightTerms : function(terms){
			var me = this;
			$.each(terms, function(i, term){
				if(term.length >= codepeople_search_in_place.char_number){
					var color = me.config.colors[i%me.config.colors.length],
						regex = new RegExp('(<[^>]*>)|('+ term.replace(/([-.*+?^${}()|[\]\/\\])/g,"\\$1") +')', 'ig');
					$.each(me.config.areas, function(j, area){
						var area = $(area);
						if(area.length)
							area.html(area.html().replace(regex, function(a, b, c){
								return (a.charAt(0) == '<') ? a : '<mark style="background-color:'+ color +'">' + c + '</mark>';
							}));
					});
				}	
			});
		}
	};

	jQuery(function(){
		var	searchObj = new searchInPlace();
		
		if((codepeople_search_in_place.highlight*1) && codepeople_search_in_place.terms && codepeople_search_in_place.terms.length > 0){
			searchObj.highlightTerms(codepeople_search_in_place.terms);
		}
		
		if((codepeople_search_in_place.identify_post_type)*1){
			$('.type-post').prepend('<div class="search-in-place-type-post">'+codepeople_search_in_place.post_title+'</div>');
			$('.type-page').prepend('<div class="search-in-place-type-page">'+codepeople_search_in_place.page_title+'</div>');
		}
		
		searchObj.autocomplete();
	});	
})(jQuery);

