(function($) {
    "use strict";

	// ______________ Page Loading
	$(window).on("load", function(e) {
		$("#global-loader").fadeOut("slow");
	})

	// ______________Cover Image
	$(".cover-image").each(function() {
		var attr = $(this).attr('data-image-src');
		if (typeof attr !== typeof undefined && attr !== false) {
			$(this).css('background', 'url(' + attr + ') center center');
		}
	});

	$('.table-subheader').click(function(){
		$(this).nextUntil('tr.table-subheader').slideToggle(100);
	});

	// ______________ Horizonatl
	$(document).ready(function() {
      $("a[data-theme]").click(function() {
        $("head link#theme").attr("href", $(this).data("theme"));
        $(this).toggleClass('active').siblings().removeClass('active');
      });

      $("a[data-effect]").click(function() {
        $("head link#effect").attr("href", $(this).data("effect"));
        $(this).toggleClass('active').siblings().removeClass('active');
      });
    });

	// ______________Full screen
	$("#fullscreen-button").on("click", function toggleFullScreen() {
      if ((document.fullScreenElement !== undefined && document.fullScreenElement === null) || (document.msFullscreenElement !== undefined && document.msFullscreenElement === null) || (document.mozFullScreen !== undefined && !document.mozFullScreen) || (document.webkitIsFullScreen !== undefined && !document.webkitIsFullScreen)) {
        if (document.documentElement.requestFullScreen) {
          document.documentElement.requestFullScreen();
        } else if (document.documentElement.mozRequestFullScreen) {
          document.documentElement.mozRequestFullScreen();
        } else if (document.documentElement.webkitRequestFullScreen) {
          document.documentElement.webkitRequestFullScreen(Element.ALLOW_KEYBOARD_INPUT);
        } else if (document.documentElement.msRequestFullscreen) {
          document.documentElement.msRequestFullscreen();
        }
      } else {
        if (document.cancelFullScreen) {
          document.cancelFullScreen();
        } else if (document.mozCancelFullScreen) {
          document.mozCancelFullScreen();
        } else if (document.webkitCancelFullScreen) {
          document.webkitCancelFullScreen();
        } else if (document.msExitFullscreen) {
          document.msExitFullscreen();
        }
      }
    })

	// ______________Quantity Cart Increase & Descrease
	$(function () {
		$('.add').on('click',function(){
			var $qty=$(this).closest('div').find('.qty');
			var currentVal = parseInt($qty.val());
			if (!isNaN(currentVal)) {
				$qty.val(currentVal + 1);
			}

		});
		$('.minus').on('click',function(){
			var $qty=$(this).closest('div').find('.qty');
			var currentVal = parseInt($qty.val());
			if (!isNaN(currentVal) && currentVal > 0) {
				$qty.val(currentVal - 1);
			}
		});
	});

	// __________MODAL

	// showing modal with effect
	$('.modal-effect').on('click', function(e) {
		e.preventDefault();
		var effect = $(this).attr('data-bs-effect');
		$('#modaldemo8').addClass(effect);
	});

	// hide modal with effect
	$('#modaldemo8').on('hidden.bs.modal', function(e) {
		$(this).removeClass(function(index, className) {
			return (className.match(/(^|\s)effect-\S+/g) || []).join(' ');
		});
	});

	// ______________Back to top Button
	$(window).on("scroll", function(e) {
    	if ($(this).scrollTop() > 0) {
            $('#back-to-top').fadeIn('slow');
        } else {
            $('#back-to-top').fadeOut('slow');
        }
    });
    $("#back-to-top").on("click", function(e){
        $("html, body").animate({
            scrollTop: 0
        }, 0);
        return false;
    });

	// ______________ Chart-circle
	if ($('.chart-circle').length) {
		$('.chart-circle').each(function() {
			let $this = $(this);

			$this.circleProgress({
			  fill: {
				color: $this.attr('data-color')
			  },
			  size: $this.height(),
			  startAngle: -Math.PI / 4 * 2,
			  emptyFill: '#e2e2e9',
			  lineCap: 'round'
			});
		});
	}

	// ______________ Chart-circle
	if ($('.chart-circle-transparent').length) {
		$('.chart-circle-transparent').each(function() {
			let $this = $(this);

			$this.circleProgress({
			  fill: {
				color: $this.attr('data-color')
			  },
			  size: $this.height(),
			  startAngle: -Math.PI / 4 * 2,
			  emptyFill: 'rgba(0, 0, 0, 0.1)',
			  lineCap: 'round'
			});
		});
	}

	// ______________ Chart-circle
	if ($('.chart-circle-primary').length) {
		$('.chart-circle-primary').each(function() {
			let $this = $(this);

			$this.circleProgress({
			  fill: {
				color: $this.attr('data-color')
			  },
			  size: $this.height(),
			  startAngle: -Math.PI / 4 * 2,
			  emptyFill: 'rgba(112, 94, 200, 0.4)',
			  lineCap: 'round'
			});
		});
	}

	// ______________ Chart-circle
	if ($('.chart-circle-secondary').length) {
		$('.chart-circle-secondary').each(function() {
			let $this = $(this);

			$this.circleProgress({
			  fill: {
				color: $this.attr('data-color')
			  },
			  size: $this.height(),
			  startAngle: -Math.PI / 4 * 2,
			  emptyFill: 'rgba(251, 28, 82, 0.4)',
			  lineCap: 'round'
			});
		});
	}

	// ______________ Chart-circle
	if ($('.chart-circle-success').length) {
		$('.chart-circle-success').each(function() {
			let $this = $(this);

			$this.circleProgress({
			  fill: {
				color: $this.attr('data-color')
			  },
			  size: $this.height(),
			  startAngle: -Math.PI / 4 * 2,
			  emptyFill: 'rgba(66, 196, 138, 0.5)',
			  lineCap: 'round'
			});
		});
	}

	// ______________ Chart-circle
	if ($('.chart-circle-warning').length) {
		$('.chart-circle-warning').each(function() {
			let $this = $(this);

			$this.circleProgress({
			  fill: {
				color: $this.attr('data-color')
			  },
			  size: $this.height(),
			  startAngle: -Math.PI / 4 * 2,
			  emptyFill: 'rgba(255, 171, 0, 0.5)',
			  lineCap: 'round'
			});
		});
	}

	// ______________ Global Search
	$(document).on("click", "[data-bs-toggle='search']", function(e) {
		var body = $("body");

		if(body.hasClass('search-gone')) {
			body.addClass('search-gone');
			body.removeClass('search-show');
		}else{
			body.removeClass('search-gone');
			body.addClass('search-show');
		}
	});
	var toggleSidebar = function() {
		var w = $(window);
		if(w.outerWidth() <= 1024) {
			$("body").addClass("sidebar-gone");
			$(document).off("click", "body").on("click", "body", function(e) {
				if($(e.target).hasClass('sidebar-show') || $(e.target).hasClass('search-show')) {
					$("body").removeClass("sidebar-show");
					$("body").addClass("sidebar-gone");
					$("body").removeClass("search-show");
				}
			});
		}else{
			$("body").removeClass("sidebar-gone");
		}
	}
	toggleSidebar();
	$(window).resize(toggleSidebar);

	$(document).on("click", ".close-btn", function() {
		$("body").removeClass("search-show");
	});

	const DIV_CARD = 'div.card';

	// ______________ Attach Remove
	$(document).on('click', '[data-toggle="remove"]', function(e) {
		let $a = $(this).closest(".attach-supportfiles");
		$a.remove();
		e.preventDefault();
		return false;
	});


	// ______________ Tooltip
	var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
	var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
 	 return new bootstrap.Tooltip(tooltipTriggerEl)
	})


	// ______________ Popover
	var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
	var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
	html:
	return new bootstrap.Popover(popoverTriggerEl)
	})

	// ______________Live Toast
	var toastTrigger = document.getElementById('liveToastBtn')
	var toastLiveExample = document.getElementById('liveToast')
	if (toastTrigger) {
		toastTrigger.addEventListener('click', function () {
			var toast = new bootstrap.Toast(toastLiveExample)
			toast.show()
		})
	}

	// ______________ Card Remove
	$(document).on('click', '[data-bs-toggle="card-close"]', function(e) {
		let $card = $(this).closest(DIV_CARD);
		$card.remove();
		e.preventDefault();
		return false;
	});


	// ______________ Card Remove
	$(document).on('click', '[data-bs-toggle="card-remove"]', function(e) {
		let $card = $(this).closest(DIV_CARD);
		$card.remove();
		e.preventDefault();
		return false;
	});

	// ______________ Card Collapse
	$(document).on('click', '[data-bs-toggle="card-collapse"]', function(e) {
		let $card = $(this).closest(DIV_CARD);
		$card.toggleClass('card-collapsed');
		e.preventDefault();
		return false;
	});

	// ______________ Card Fullscreen
	$(document).on('click', '[data-bs-toggle="card-fullscreen"]', function(e) {
		let $card = $(this).closest(DIV_CARD);
		$card.toggleClass('card-fullscreen').removeClass('card-collapsed');
		e.preventDefault();
		return false;
	});

	// ______________ SWITCHER-toggle ______________//

	$('.layout-setting').on("click", function(e) {
		if (!document.body.classList.contains('dark-mode')) {
			$('body').addClass('dark-mode');
			$('body').removeClass('light-mode');
			$('body').removeClass('light-menu');
			$('body').removeClass('color-menu');
			$('body').removeClass('dark-menu');
			$('body').removeClass('gradient-menu');
			$('body').removeClass('dark-header');
			$('body').removeClass('color-header');
			$('body').removeClass('light-header');
			$('body').removeClass('gradient-header');
            localStorage.setItem("azeadarkMode", true);
            localStorage.removeItem("azealightMode");
			$('#myonoffswitch2').prop('checked', true);
			$('#myonoffswitch5').prop('checked', true);
			$('#myonoffswitch8').prop('checked', true);
		} else {
            $('body').addClass('light-mode');
            $('body').removeClass('dark-mode');
			$('body').removeClass('light-header');
			$('body').removeClass('color-header');
			$('body').removeClass('dark-header');
			$('body').removeClass('gradient-header');
            $('body').removeClass('light-menu');
			$('body').removeClass('dark-menu');
			$('body').removeClass('gradient-menu');
			$('body').removeClass('color-menu');
            localStorage.setItem("azealightMode", true);
            localStorage.removeItem("azeadarkMode");
			$('#myonoffswitch1').prop('checked', true);
			$('#myonoffswitch3').prop('checked', true);
			$('#myonoffswitch6').prop('checked', true);
		}
	});



	  $('.default-menu').on('click', function() {
		var ww = document.body.clientWidth;
		if (ww >= 992) {
			$('body').removeClass('sidenav-toggled');
		}
	});


	// **********  SWITCHER STYLES ***********//

	//######## THEME STYLE ########//

	/*Dark Theme Start*/

    // $('body').addClass('dark-mode');

	/*Dark Theme End*/
	


	//######## MENU STYLE ########//

	/*Light Menu Start*/

    // $('body').addClass('light-menu');

	/*Light Menu End*/

    /*Color Menu Start*/

    // $('body').addClass('color-menu');

	/*Color Menu End*/

    /*Dark Menu Start*/

    // $('body').addClass('dark-menu');

	/*Dark Menu End*/

	/*Gradient Menu Start*/

    // $('body').addClass('gradient-menu');

	/*Gradient Menu End*/



	//######## HEADER STYLE ########//

	/*Light Header Start*/

    // $('body').addClass('light-header');

	/*Light Header End*/

	/*Color Header Start*/

    // $('body').addClass('color-header');

	/*Color Header End*/

	/*Dark Header Start*/

    // $('body').addClass('dark-header');

	/*Dark Header End*/

	/*Gradient Header Start*/

    // $('body').addClass('gradient-header');

	/*Gradient Header End*/



	//######## LAYOUT WIDTH STYLE ########//

	/*Boxed Layout Start*/

    // $('body').addClass('layout-boxed');

	/*Boxed Layout End*/

	//######## HEADER POSITION STYLE ########//

	/*Header-Position Styles Start*/

    // $('body').addClass('scrollable-layout');

	/*Header-Position Styles End*/



	//######## SIDEMENU STYLE ########//

	/*Closed Sidemenu Start*/

	// $('body').addClass('closed-menu');
	// $('body').addClass('sidenav-toggled');
	
	/*Closed Sidemenu End*/

	/*Icon Text Sidemenu Start*/

	// $('body').addClass('icontext-menu');
	// icontext();
	// $('body').addClass('sidenav-toggled');
	
	/*Icon Text Sidemenu End*/

	/*Icon Overlay Sidemenu Start*/

	// $('body').addClass('icon-overlay');
	// hovermenu();
	// $('body').addClass('sidenav-toggled');

	/*Icon Overlay Sidemenu End*/

	// HOVER SUBMENU START
	
	// $('body').addClass('hover-submenu');
	// hovermenu();
	// $('body').addClass('sidenav-toggled');

	// HOVER SUBMENU END

	// HOVER SUBMENU STYLE-1 START
	
	// $('body').addClass('hover-submenu1');
	// hovermenu();
	// $('body').addClass('sidenav-toggled');

	// HOVER SUBMENU STYLE-1 END


			
	//######## RTL STYLE ########//

	// RTL STYLE START

	// $('body').addClass('rtl');

	if ($("body").hasClass("rtl")) {
		$('body').addClass('rtl');

		$('#slide-left').removeClass('d-none');
		$('#slide-right').removeClass('d-none');
		$("html[lang=en]").attr("dir", "rtl");
		$('body').removeClass('ltr');
		$("head link#style").attr("href", $(this));
		(document.getElementById("style").setAttribute("href", "/assets/plugins/bootstrap/css/bootstrap.rtl.min.css"));
		var carousel = $('.owl-carousel');
		$.each(carousel, function (index, element) {
			// element == this
			var carouselData = $(element).data('owl.carousel');
			carouselData.settings.rtl = true; //don't know if both are necessary
			carouselData.options.rtl = true;
			$(element).trigger('refresh.owl.carousel');
		});

	}

	let bodyRtl = $('body').hasClass('rtl');
	if (bodyRtl) {
			$('body').addClass('rtl');
			localStorage.setItem("rtl", "True");
			$("head link#style").attr("href", $(this));
			(document.getElementById("style")?.setAttribute("href", "/assets/plugins/bootstrap/css/bootstrap.rtl.min.css"));
		}
		else {
			$('body').removeClass('rtl');
			localStorage.setItem("rtl", "false");
			$("head link#style").attr("href", $(this));
			(document.getElementById("style")?.setAttribute("href", "/assets/plugins/bootstrap/css/bootstrap.min.css"));
	};
	
	// RTL STYLE END


	//######## HORIZONTAL STYLE ########//

	// HORIZONTAL CLICK START
	// $('body').addClass('horizontal');

	let bodyhorizontal = $('body').hasClass('horizontal');
	if (bodyhorizontal) {
		$('body').addClass('horizontal');
		$(".main-content").addClass("hor-content");
		$(".main-content").removeClass("app-content");
		$(".main-container").addClass("container");
		$(".main-container").removeClass("container-fluid");
		$(".app-header").addClass("hor-header");
		$(".hor-header").removeClass("app-header");
		$(".app-sidebar").addClass("horizontal-main")
		$(".main-sidemenu").addClass("container")
		$('body').removeClass('sidebar-mini');
		$('body').removeClass('sidenav-toggled');
		$('body').removeClass('horizontal-hover');
		$('body').removeClass('default-menu');
		$('body').removeClass('icontext-menu');
		$('body').removeClass('icon-overlay');
		$('body').removeClass('closed-leftmenu');
		$('body').removeClass('hover-submenu');
		$('body').removeClass('hover-submenu1');
        // // To enable no-wrap horizontal style
        document.querySelector('.horizontal .side-menu')?.classList.add('flex-nowrap')
        $('#slide-left').removeClass('d-none');
        $('#slide-right').removeClass('d-none');
        // To enable wrap horizontal style
        // document.querySelector('.horizontal .side-menu').style.flexWrap = 'wrap'
        // $('#slide-left').addClass('d-none');
        // $('#slide-right').addClass('d-none');
		// menuClick();
		if (!document.querySelector('body').classList.contains('login-page') && !document.querySelector('body').classList.contains('error-bg')) {
			checkHoriMenu();
			responsive();
			sidemenudropdown();
		}
		if (window.innerWidth >= 992) {
			let li = document.querySelectorAll('.side-menu li')
			li.forEach((e, i) => {
				e.classList.remove('is-expanded')
			})
			var animationSpeed = 300;
			// first level
			var parent = $("[data-bs-toggle='sub-slide']").parents('ul');
			var ul = parent.find('ul:visible').slideUp(animationSpeed);
			ul.removeClass('open');
			var parent1 = $("[data-bs-toggle='sub-slide2']").parents('ul');
			var ul1 = parent1.find('ul:visible').slideUp(animationSpeed);
			ul1.removeClass('open');
		}
	}
	//HORIZONTAL CLICK END


	//HORIZONTAL HOVER START
	// $('body').addClass('horizontal-hover');
	
    function light() {
        if (document.querySelector('body').classList.contains('light-mode')) {
            $('#myonoffswitch3').prop('checked', true);
            $('#myonoffswitch6').prop('checked', true);
        }
    }
    light();
    let bodyhorizontal1 = $('body').hasClass('horizontal-hover');
    if (bodyhorizontal1) {
        if (window.innerWidth >= 992) {
            let li = document.querySelectorAll('.side-menu li')
            li.forEach((e, i) => {
                e.classList.remove('is-expanded')
            })
            var animationSpeed = 300;
            // first level
            var parent = $("[data-bs-toggle='sub-slide']").parents('ul');
            var ul = parent.find('ul:visible').slideUp(animationSpeed);
            ul.removeClass('open');
            var parent1 = $("[data-bs-toggle='sub-slide2']").parents('ul');
            var ul1 = parent1.find('ul:visible').slideUp(animationSpeed);
            ul1.removeClass('open');
        }
        $('body').addClass('horizontal-hover');
        $('body').addClass('horizontal');
        // // To enable no-wrap horizontal style
        document.querySelector('.horizontal .side-menu')?.classList.add('flex-nowrap')
        $('#slide-left').removeClass('d-none');
        $('#slide-right').removeClass('d-none');
        // To enable wrap horizontal style
        // document.querySelector('.horizontal .side-menu').style.flexWrap = 'wrap'
        // $('#slide-left').addClass('d-none');
        // $('#slide-right').addClass('d-none');
        $(".main-content").addClass("hor-content");
        $(".main-content").removeClass("app-content");
        $(".main-container").addClass("container");
        $(".main-container").removeClass("container-fluid");
        $(".app-header").addClass("hor-header");
        $(".app-header").removeClass("app-header");
        $(".app-sidebar").addClass("horizontal-main")
        $(".main-sidemenu").addClass("container")
        $('body').removeClass('sidebar-mini');
        $('body').removeClass('sidenav-toggled');
        $('body').removeClass('default-menu');
        $('body').removeClass('icontext-menu');
        $('body').removeClass('icon-overlay');
        $('body').removeClass('closed-leftmenu');
        $('body').removeClass('hover-submenu');
        $('body').removeClass('hover-submenu1');
		if (!document.querySelector('body').classList.contains('login-page') && !document.querySelector('body').classList.contains('error-bg')) {
			checkHoriMenu();
			responsive();
		}

    }
	//HORIZONTAL HOVER END


})(jQuery);
