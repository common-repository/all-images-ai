(function( $ ) {
	'use strict';

	$(document).ready(function() {

		let page = 0;
		let $grid;
		let block_scrolling_event = false;

		if ( wp.media ) {

			let l10n = wp.media.view.l10n;
			wp.media.view.MediaFrame.Select.prototype.browseRouter = function( routerView ) {
				routerView.set({
					upload: {
						text:     l10n.uploadFilesTitle,
						priority: 20
					},
					browse: {
						text:     l10n.mediaLibraryTitle,
						priority: 40
					},
					all_images: {
						text:     "All-Images",
						priority: 60
					}
				});
			};

			wp.media.view.Modal.prototype.on( "open", function() {
				if($(wp.media.frame.modal.clickedOpenerEl).is('#insert-media-button, #set-post-thumbnail')) {
					if($('#menu-item-all_images').hasClass('active')) {
						getTabContent();
					}
				} else {
					$('#menu-item-all_images').hide();
				}

			});
			$(document).on('click', '#menu-item-all_images', function (e) {
				getTabContent();
			});

		}

		function getTabContent() {

			$.ajax({
				url:wp_data.ajaxurl,
				method:'GET',
				data:{
					'action':'get_main_content',
					'nonce':wp_data.nonce
				},
				success:function (response) {
					$('.media-frame-content').html(response.data.html);
					search_callback();
				}
			});

		}

		function initPagination() {
			page = 1;
			let $element, element;
			if($('.media-frame-content').length) {
				$element = $('.media-frame-content');
				element = $element[0];
			} else {
				$element = $(window);
				element = document.body;
			}
			let lastScrollTop = 0, myScrollTop;
			element.onscroll = (e)=>{
				if(!element.scrollTop) {
					myScrollTop = $(document).scrollTop();
				} else {
					myScrollTop = element.scrollTop;
				}
				if (myScrollTop < lastScrollTop){
					// upscroll
					return;
				}
				lastScrollTop = myScrollTop <= 0 ? 0 : myScrollTop;
				if (myScrollTop + element.offsetHeight>= element.scrollHeight && !block_scrolling_event){
					block_scrolling_event = true;
					page++;
					$('.page-load-status').show();
					$.ajax({
						url:wp_data.ajaxurl,
						method:'POST',
						data:{
							'action':'get_image_results',
							'nonce':wp_data.nonce,
							'post_id':wp_data.post_id,
							'search':$('#all-image-search-input').val(),
							'page':page
						},
						success:function (response) {
							let currentScroll = $element.scrollTop();
							let $html = $(response.data.html);
							$html.css('visibility', 'hidden').css('opacity', 0);
							$('.all-images-results-grid').append($html);
							$('.all-images-results-grid').imagesLoaded(function(){
								$grid.masonry('destroy');
								$grid.removeData('masonry');
								$grid.masonry({
									itemSelector: '.all-images-image-wrapper'
								});
								$element.scrollTop(currentScroll);
								$('.all-images-image-wrapper').css('visibility', 'visible').animate({
									opacity:1
								},500);
								$('.page-load-status').hide();
								block_scrolling_event = false;
							});
						}
					});
				}
			}
		}

		let error_handler = function(response) {
			$('.page-load-status').hide();
			$('#all-images-form button').prop('disabled', false);
			alert(response.responseJSON.data.error);
		};

		let search_callback = function (e) {
			if(e) e.preventDefault();
			if($('.all-images-results-grid').data('masonry')) {
				$('.all-images-results-grid').masonry('destroy');
				$('.all-images-results-grid').removeData('masonry');
			}
			$('.all-images-results-grid *').remove();
			$('#all-images-form button').prop('disabled', true);
			$('.page-load-status').show();
			$.ajax({
				url:wp_data.ajaxurl,
				method:'POST',
				data:{
					'action':'get_image_results',
					'post_id':wp_data.post_id,
					'nonce':wp_data.nonce,
					'search':$('#all-image-search-input').val(),
					'page':1
				},
				success:function (response) {
					let $html = $(response.data.html);
					$html.css('visibility', 'hidden').css('opacity', 0);
					$('.all-images-results-grid').html($html);
					$('.all-images-results-grid').imagesLoaded(function(){
						// do stuff after images are loaded here
						$('.all-images-image-wrapper').css('visibility', 'visible').animate({
							opacity:1
						},500);
						if($('.all-images-results-grid').data('masonry')) {
							$('.all-images-results-grid').masonry('destroy');
							$('.all-images-results-grid').removeData('masonry');
						}
						$grid = $('.all-images-results-grid').masonry({
							itemSelector: '.all-images-image-wrapper'
						});
						$('.page-load-status').hide();
					});
					$('#all-images-form button').prop('disabled', false);
					initPagination();
				},
				error:error_handler
			});
		}

		let import_callback = function(e, elem) {
			e.preventDefault();
			let i_id = $(elem).find('.modal-form').attr('data-image-id');
			let $overlay = $('.image-overlay[data-image-id='+i_id+']');
			let $default = $overlay.clone();
			let post_data = {
				'fields':$(elem).find('.modal-form').serialize(),
				'nonce':wp_data.nonce,
				'action':'select_image_for_library'
			};
			$overlay.addClass('pinned-overlay');
			$overlay.find('a').replaceWith('<img class="loader-gif" src="'+wp_data.loader_url+'">');
			$('.tb-close-icon').trigger('click');
			$('.ai-modal-window.open').removeClass('open');
			$('.ai-shadow').removeClass('ai-shadow');

			$.ajax({
				url:wp_data.ajaxurl,
				method:'POST',
				data:post_data,
				success:function (response) {
					if(wp_data.post_id) {
						if(wp.media.frame.content.get('gallery')) {
							$('.media-frame-router .media-menu-item:visible').eq(1).trigger('click');
							wp.media.frame.content.get('gallery').collection.props.set({ignore: (+ new Date())});
						} else {
							wp.media.frame.content.mode('browse');
							wp.media.frame.content.view.views._views[".media-frame-content"][0].views._views[""][1].collection.props.set({ignore:(+(new Date()))});
						}
						setTimeout(function() {
							$('li.attachment[data-id='+response.data.attachment_id+']').trigger('click');
						}, 800);
					} else {
						$overlay.find('.loader-gif').replaceWith('<span class="dashicons dashicons-yes"></span>');
					}
				},
				error:function(response) {
					error_handler(response);
					$overlay.replaceWith($default);
				}
			});
		}

		$(document).on('submit', '#all-images-form', search_callback);
		if($('#all-images-form').length) {
			search_callback();
		}

		$(document).on('submit', '.all-images-image-wrapper.big form.modal-form', function(e) {
			import_callback(e, $(this).closest('.all-images-image-wrapper'));
		});
		$(document).on('click', '.all-images-image-wrapper.big .trigger-download', function(e) {
			import_callback(e, $(this).closest('.all-images-image-wrapper'));
		});

		$(document).on('click', '.all-images-image-wrapper a.settings.open-modal', function (e) {
			e.preventDefault();
			$('.ai-modal-window.open').removeClass('open');
			$(this).closest('.all-images-image-wrapper').find('.ai-modal-window').toggleClass('open');
			if($('.media-modal').length && $('.media-modal').is(':visible')) {
				$('.all-images-wrap').toggleClass('ai-shadow');
			} else {
				$('#wpwrap').toggleClass('ai-shadow');
			}
		});

		$(document).on('click', '.all-images-image-wrapper .button-cancel,  .ai-modal-window .button-close', function (e) {
			e.preventDefault();
			$('.ai-modal-window.open').removeClass('open');
			$('.ai-shadow').removeClass('ai-shadow');
		});

		if($('#postimagediv').length) {

			$('#postimagediv .inside').append('<p><a href="'+wp_data.form_url+'">'+wp_data.featured_label+'</a></p>');

		}

		// BULK
		if($('.allimages-form').length) {

			if($('#automatic-generation-form').length) {
				$('#automatic-generation-form').validate();
			}
			if($('#bulk-generation-form').length) {
				$('#bulk-generation-form').validate();
			}

			let last_line_index = 1;
			let hasFeatured;
			let $tr;
			let nbLines;

			// ON IMAGE TYPE CHANGE
			let imageTypeChangeHandler = function(item, changeValues) {
				$tr = $(item).closest('tr');
				switch($(item).val()) {
					case 'featured':
						$tr.find('.hide-for-featured').hide();
						if(changeValues) {
							$tr.find('.prompt-field').val('post_title');
							$tr.find('.prompt-field option[value="corresponding_h2"]').remove();
							$tr.find('.picking-field').val('auto');
						}
						toggleFeaturedOptions();
						break;
					case 'content':
						$tr.find('.hide-for-featured').show();
						if(changeValues) {
							$tr.find('.position-field').val('start');
							$tr.find('.prompt-field option[value="corresponding_h2"]').remove();
							$tr.find('.prompt-field').val('post_title');
							$tr.find('.picking-field').val('auto');
							$tr.find('.size-field').val('full');
						}
						toggleFeaturedOptions();
						break;
					default:
						break;
				}
			};
			$(document).on('change', '.image-type-field', function() {
				imageTypeChangeHandler($(this), true);
			});

			$('.form-table').each(function(i, item) {

				nbLines = $(item).find('.allimages-image-row').length;
				if(nbLines) {

					// EXITING ROWS
					$(item).find('.allimages-image-row').each(function(i2, item2) {
						imageTypeChangeHandler($(item2).find('.image-type-field'), false);
					});
					if(nbLines == 1) {
						$(item).find('.button-delete').hide();
					}

				} else {

					let lineData = lineTemplate.replace(/\{n\}/g, 1).replace(/\{p\}/g, $(item).data('post-type'));
					$(item).append(lineData);
					$(item).find('.hide-for-featured, .button-delete').hide();

				}

			});


			function generateLine(index, $elem) {
				let lineData = lineTemplate.replace(/\{n\}/g, index).replace(/\{p\}/g, $elem.closest('table').data('post-type'));
				$elem.closest('table').find('.allimages-image-row').last().after(lineData);
				$elem.closest('table').find('.allimages-image-row').last().removeClass('allimages-image-row-template');
				toggleFeaturedOptions();
			}

			function toggleDeleteButtons() {
				$('.form-table').each(function(i, item) {
					if($(item).find('.allimages-image-row').length > 1) {
						$(item).find('.button-delete').show();
					} else {
						$(item).find('.button-delete').hide();
					}
				});
			}

			function resetNumbers() {
				$('.form-table').each(function(i2, item2) {
					$(item2).find('.allimages-image-row').each(function(i, item) {
						$(item).find('.image-number').text(i+1);
					});
				});
			}

			function toggleFeaturedOptions() {
				$('.form-table').each(function(i2, item2) {
					hasFeatured = false;
					$(item2).find('.image-type-field').each(function (i, item) {
						if ($(item).val() == 'featured') {
							$(item).closest('table').find('.image-type-field').not($(item)).find('option[value=featured]').prop('disabled', true);
							hasFeatured = true;
						}
					});
					if(!hasFeatured) {
						$(item2).find('.image-type-field option[value=featured]').prop('disabled', false);
					}
				});
			}

			function get_selected_posts() {

				$.ajax({
					url:wp_data.ajaxurl,
					method:'POST',
					data:{
						'action':'get_selected_posts',
						'nonce':wp_data.nonce,
						'fields':$('#bulk-generation-form').serialize()
					},
					success:function (response) {
						let nb_posts = response.data.post_ids.length;
						$('#launch-generations').prop('disabled', nb_posts === 0);
						$('p.number-of-posts').text(response.data.message);
						if ($('#fields-posts-selection').length) {
							$('#fields-posts-selection input[type="hidden"]').remove();
							$.each(response.data.post_ids, function( index, value ){
								const has_post = $('#fields-posts-selection input[value="'+value+'"]').length > 0;

								if (has_post) {
									return;
								}

								$('#fields-posts-selection').append(
									$('<input>').attr({
										type: 'hidden',
										name: 'posts[]',
										value: value
									})
								);
							});
						}
					}
				});

			}

			$(document).on('change', '#bulk-generation-form-filters', get_selected_posts);
			if($('#fields-posts-selection').length) {
				get_selected_posts();
			}

			// ON POSITION CHANGE OR ON IMAGE TYPE CHANGE
			let correspondingH2 = $('option[value="corresponding_h2"]').first().text();
			$(document).on('change', '.position-field', function() {
				$tr = $(this).closest('tr');
				switch($(this).val()) {
					case 'start':
						$tr.find('.prompt-field option[value="corresponding_h2"]').remove();
						break;
					default:
						if(!$tr.find('.prompt-field option[value="corresponding_h2"]').length) {
							$tr.find('.prompt-field').append($('<option>', {
								value: 'corresponding_h2',
								text: correspondingH2
							}));
						}
						break;
				}
			});

			// ADD LINE
			$(document).on('click', '#allimages-add-line', function() {
				last_line_index = $(this).closest('table').find('.allimages-image-row').length+1;
				generateLine(last_line_index, $(this));
				$(this).closest('table').find('.allimages-image-row').last().find('.hide-for-featured').hide();
				if ($(this).closest('table').find('.position-field').last().val() === 'start') {
					$(this).closest('table').find('.prompt-field option[value="corresponding_h2"]').remove();
				}
				if ($(this).closest('table').find('.image-type-field').last().val() === 'featured') {
					$(this).closest('table').find('.prompt-field option[value="corresponding_h2"]').remove();
				}
				toggleDeleteButtons();
			});

			// DELETE LINE
			$(document).on('click', '.button-delete', function() {
				$(this).closest('tr').remove();
				toggleDeleteButtons();
				resetNumbers();
			});

			// ON SUBMIT BULK FORM / SHOW MODAL
			$('#modal-window-bulk-form').dialog({
				title: wp_data.processing_label,
				dialogClass: 'wp-dialog no-close',
				autoOpen: false,
				draggable: false,
				width: 'auto',
				modal: true,
				resizable: false,
				closeOnEscape: false,
				position: {
					my: "center",
					at: "center",
					of: window
				}
			});

			$(document).on('submit', '#bulk-generation-form', function (e) {
				e.preventDefault();

				$('#launch-generations').replaceWith('<img class="loader-gif small-loader" src="'+wp_data.loader_url+'">');

				var progressbar = $("#modal-window-bulk-form .progressbar"),
					progressLabel = $(".progress-label"),
					nbErrors = 0;

				progressbar.progressbar({
					value: false,
					change: function() {
						progressLabel.text( progressbar.progressbar( "value" ).toFixed(2) + "%" );
					},
					complete: function() {
						if (nbErrors > 0) {
							progressLabel.text(wp_data.complete_with_error_label);

							$('<a>',{
								text: wp_data.link_label,
								href: wp_data.redirect_url
							}).appendTo('#bulk-form-result');
						} else {
							progressLabel.text(wp_data.complete_label);
							setTimeout(function () {
								window.location = wp_data.redirect_url
							}, 500);
						}
					}
				});

				$('#modal-window-bulk-form').dialog('open');

				var nb_posts = $('#bulk-generation-form input[name^="posts"]').length;
				var nb_posts_processed = 0;

				if (nb_posts > 0) {
					nextAjax(0);
				}

				function nextAjax(i) {
					const post_id = $('#bulk-generation-form input[name^="posts"]').eq(i).val();

					$.ajax({
						url: wp_data.ajaxurl,
						method: 'POST',
						data: {
							'action': 'launch_generation',
							'nonce': wp_data.nonce,
							'post_id': post_id,
							'fields': $('#bulk-generation-form').serialize()
						},
						success: function () {
							nextPost(i);
						},
						error: function (response) {
							nbErrors++;

							if (response.responseJSON && response.responseJSON.data && response.responseJSON.data.message) {
								$('#bulk-form-result').append(
									$('<p>', {
										class: 'error'
									}).html(response.responseJSON.data.message)
								);
							}

							nextPost(i);
						}
					});
				}

				function nextPost(i) {
					nb_posts_processed++;

					let value = Math.min((nb_posts_processed / nb_posts) * 100, 100);
					progressbar.progressbar("value", value);

					if (i < nb_posts-1) {
						setTimeout(function() {
							nextAjax(i+1);
						}, 500);
					}
				}
			});

		}

		// GENERATIONS
		if($('#generations-list').length) {

			var pendingIds = [];
			$('.gen-pending').each(function(i, item) {
				pendingIds.push($(item).closest('tr').find('.generation-id').data('id'));
			});

			var nb_generations = pendingIds.length;
			var nb_generations_processed = 0;

			if (nb_generations > 0) {
				setTimeout(function() {
					checkGeneration(0);
				}, 30000);
			}

			function checkGeneration(i, id = null) {
				if (id === null) {
					id = pendingIds[i];
				}

				$.ajax({
					url:wp_data.ajaxurl,
					method:'POST',
					data:{
						'action':'check_generation',
						'nonce':wp_data.nonce,
						'id':id
					},
					success: function (response) {
						if(response.data.ready) {
							$('tr[data-id="'+id+'"] .status').html(response.data.status);
							$('tr[data-id="'+id+'"] .images').html(response.data.images);
						} else {
							if(response.data.in_progress) {
								if($('tr[data-id="'+id+'"] .images').find('.small-image').length != $(response.data.images).length) {
									$('tr[data-id="'+id+'"] .images').html(response.data.images);
								}
							} else {
								$('tr[data-id="'+id+'"] .status').html(response.data.status);
							}
							setTimeout(function() {
								checkGeneration(null, id);
							}, 30000);
						}

						if (i !== null) {
							nextGeneration(i);
						}
					},
					error: function (response) {
						setTimeout(function() {
							checkGeneration(null, id);
						}, 30000);

						if (i !== null) {
							nextGeneration(i);
						}
					}
				});
			}

			function nextGeneration(i) {
				nb_generations_processed++;

				if (i < nb_generations-1) {
					setTimeout(function() {
						checkGeneration(i+1);
					}, 1000);
				}
			}

			let gen_import = function(e, elem) {
				e.preventDefault();
				let $this = $(elem);
				let td = $this.closest('td');
				let gen_id = $this.closest('tr').find('.generation-id').data('generation-id');
				let $overlay = $this.closest('.image-overlay');
				let $modalform = $this.closest('.small-image').find('.ai-modal-window form');
				let $default = $overlay.clone();
				$overlay.addClass('pinned-overlay');
				$this.replaceWith('<img class="loader-gif" src="'+wp_data.loader_url+'">');

				$.ajax({
					url:wp_data.ajaxurl,
					method:'POST',
					data:{
						'action':'select_generation_image',
						'fields':$modalform.serialize(),
						'generation_id':gen_id,
						'nonce':wp_data.nonce,
						'image_id':$this.data('image-id')
					},
					success:function (response) {
						td.html(response.data.images);
					},
					error:function(response) {
						error_handler(response);
						$overlay.replaceWith($default);
					}
				});
			};

			$(document).on('click', '.all-images-image-wrapper.small-image .image-overlay a', function (e) {
				gen_import(e, this);
			});
			$(document).on('submit', '.all-images-image-wrapper.small-image .ai-modal-window form', function (e) {
				$('.ai-modal-window.open').removeClass('open');
				$('.ai-shadow').removeClass('ai-shadow');
				gen_import(e, $(this).closest('.all-images-image-wrapper').find('.image-overlay a') );
			});

		}


		// ACCORDION
		if($('#allimages-automatic-form').length) {

			let allClosed = true;
			let openedText = $('#collapse-accordions').data('opened-text');
			let closedText = $('#collapse-accordions').text();

			$('#collapse-accordions').on('click', function() {
				if(allClosed) {
					$('.ui-accordion-header').removeClass('ui-corner-all').addClass('ui-accordion-header-active ui-state-active ui-corner-top').attr({'aria-expanded':'true', 'aria-selected':'true','tabindex':'0'});
					$('.ui-accordion-header .ui-icon').removeClass('ui-icon-triangle-1-e').addClass('ui-icon-triangle-1-s');
					$('.ui-accordion-content').addClass('ui-accordion-content-active').attr({'aria-expanded':'true','aria-hidden':'false'}).show();
					$(this).text(openedText);
					allClosed = false;
				} else {
					$('.ui-accordion-header').removeClass('ui-accordion-header-active ui-state-active ui-corner-top').addClass('ui-corner-all').attr({'aria-expanded':'false', 'aria-selected':'false','tabindex':'-1'});
					$('.ui-accordion-header .ui-icon').removeClass('ui-icon-triangle-1-s').addClass('ui-icon-triangle-1-e');
					$('.ui-accordion-content').removeClass('ui-accordion-content-active').attr({'aria-expanded':'false','aria-hidden':'true'}).hide();
					$(this).text(closedText);
					allClosed = true;
				}
			});
			$(".ui-accordion-content").show();

			function toggle_fields() {
				$('#allimages-automatic-form .accordion-content').each(function(i, item) {
					if(!$(item).find('.active-field').is(':checked')) {
						$(item).find(':input').not('.active-field').prop('disabled', true);
					} else {
						$(item).find(':input').prop('disabled', false);
					}
				});
			}
			$('.accordion-wrapper').accordion({
				heightStyle: "content",
				collapsible: true,
				active: false
			});
			toggle_fields();
			$(document).on('change', '#allimages-automatic-form', toggle_fields);
		}

	});

})( jQuery );
