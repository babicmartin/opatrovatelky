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

	/*Light Layout Start*/
    $(document).on("click", '#myonoffswitch1', function() {
        if (this.checked) {
            $('body').addClass('light-mode');
            $('body').removeClass('dark-mode');
			$('body').removeClass('color-header');
			$('body').removeClass('dark-header');
			$('body').removeClass('gradient-header');
            $('body').removeClass('light-menu');
			$('body').removeClass('dark-menu');
			$('body').removeClass('gradient-menu');
			$('body').removeClass('color-menu');
			localStorage.setItem('azealightMode', true)
			localStorage.removeItem('azeadarkMode')
			$('#myonoffswitch3').prop('checked', true);
			$('#myonoffswitch6').prop('checked', true);

        }
    });
	/*Light Layout End*/

	/*Dark Layout Start*/
    $(document).on("click", '#myonoffswitch2', function() {
        if (this.checked) {
            $('body').addClass('dark-mode');
			$('body').removeClass('light-mode');
			$('body').removeClass('light-menu');
			$('body').removeClass('color-menu');
			$('body').removeClass('dark-header');
			$('body').removeClass('color-header');
			$('body').removeClass('light-header');
			$('body').removeClass('dark-menu');
			$('body').removeClass('gradient-menu');
			localStorage.setItem('azeadarkMode', true)
			localStorage.removeItem('azealightMode')
			$('#myonoffswitch5').prop('checked', true);
			$('#myonoffswitch8').prop('checked', true);
        }
    });
	/*Dark Layout End*/

	/*Light Menu Start*/
    $(document).on("click", '#myonoffswitch3', function() {
        if (this.checked) {
            $('body').addClass('light-menu');
            $('body').removeClass('color-menu');
			$('body').removeClass('dark-menu');
			$('body').removeClass('gradient-menu');
            localStorage.setItem("light-menu", "True");
        } else {
            $('body').removeClass('light-menu');
            localStorage.setItem("light-menu", "false");
        }
    });
	/*Light Menu End*/

    /*Color Menu Start*/
    $(document).on("click", '#myonoffswitch4', function() {
        if (this.checked) {
            $('body').addClass('color-menu');
			$('body').removeClass('light-menu');
			$('body').removeClass('dark-menu');
			$('body').removeClass('gradient-menu');
            localStorage.setItem("color-menu", "True");
        } else {
            $('body').removeClass('color-menu');
            localStorage.setItem("color-menu", "false");
        }
    });
	/*Color Menu End*/

    /*Dark Menu Start*/
    $(document).on("click", '#myonoffswitch5', function() {
        if (this.checked) {
            $('body').addClass('dark-menu');
			$('body').removeClass('color-menu');
			$('body').removeClass('light-menu');
			$('body').removeClass('gradient-menu');
            localStorage.setItem("dark-menu", "True");
        } else {
            $('body').removeClass('dark-menu');
            localStorage.setItem("dark-menu", "false");
        }
    });
	/*Dark Menu End*/

	/*Gradient Menu Start*/
    $(document).on("click", '#myonoffswitch25', function() {
        if (this.checked) {
            $('body').addClass('gradient-menu');
            $('body').removeClass('color-menu');
			$('body').removeClass('light-menu');
			$('body').removeClass('dark-menu');
            localStorage.setItem("gradient-menu", "True");
        } else {
            $('body').removeClass('gradient-menu');
            localStorage.setItem("gradient-menu", "false");
        }
    });
	/*Gradient Menu End*/

	/*Light Header Start*/
    $(document).on("click", '#myonoffswitch6', function() {
        if (this.checked) {
            $('body').addClass('light-header');
			$('body').removeClass('color-header');
			$('body').removeClass('dark-header');
			$('body').removeClass('gradient-header');
            localStorage.setItem("light-header", "True");
        } else {
            $('body').removeClass('light-header');
            localStorage.setItem("light-header", "false");
        }
    });
	/*Light Header End*/

	/*Color Header Start*/
    $(document).on("click", '#myonoffswitch7', function() {
        if (this.checked) {
            $('body').addClass('color-header');
			$('body').removeClass('light-header');
			$('body').removeClass('dark-header');
			 $('body').removeClass('gradient-header');
            localStorage.setItem("color-header", "True");
        } else {
            $('body').removeClass('color-header');
            localStorage.setItem("color-header", "false");
        }
    });
	/*Color Header End*/

	/*Dark Header Start*/
    $(document).on("click", '#myonoffswitch8', function() {
        if (this.checked) {
            $('body').addClass('dark-header');
			$('body').removeClass('color-header');
			$('body').removeClass('light-header');
			 $('body').removeClass('gradient-header');
            localStorage.setItem("dark-header", "True");
        } else {
            $('body').removeClass('dark-header');
            localStorage.setItem("dark-header", "false");
        }
    });
	/*Dark Header End*/

	/*Gradient Header Start*/
    $(document).on("click", '#myonoffswitch26', function() {
        if (this.checked) {
            $('body').addClass('gradient-header');
			$('body').removeClass('dark-header');
			$('body').removeClass('color-header');
			$('body').removeClass('light-header');
            localStorage.setItem("gradient-header", "True");
        } else {
            $('body').removeClass('gradient-header');
            localStorage.setItem("gradient-header", "false");
        }
    });
	/*Gradient Header End*/

	/*Full Width Layout Start*/
    $('#myonoffswitch9').click(function() {
        if (this.checked) {
            $('body').addClass('layout-fullwidth');
			$('body').removeClass('layout-boxed');
            localStorage.setItem("layout-fullwidth", "True");
        } else {
            $('body').removeClass('layout-fullwidth');
            localStorage.setItem("layout-fullwidth", "false");
        }
    });
	/*Full Width Layout End*/

	/*Boxed Layout Start*/
    $('#myonoffswitch10').click(function() {
        if (this.checked) {
            $('body').addClass('layout-boxed');
			$('body').removeClass('layout-fullwidth');
            localStorage.setItem("layout-boxed", "True");
        } else {
            $('body').removeClass('layout-boxed');
            localStorage.setItem("layout-boxed", "false");
        }
    });
	/*Boxed Layout End*/

	/*Header-Position Styles Start*/
	$('#myonoffswitch11').click(function() {
        if (this.checked) {
            $('body').addClass('fixed-layout');
			$('body').removeClass('scrollable-layout');
            localStorage.setItem("fixed-layout", "True");
        } else {
            $('body').removeClass('fixed-layout');
            localStorage.setItem("fixed-layout", "false");
        }
    });
	$('#myonoffswitch12').click(function() {
        if (this.checked) {
            $('body').addClass('scrollable-layout');
			$('body').removeClass('fixed-layout');
            localStorage.setItem("scrollable-layout", "True");
        } else {
            $('body').removeClass('scrollable-layout');
            localStorage.setItem("scrollable-layout", "false");
        }
    });
	/*Header-Position Styles End*/


	/*Default Sidemenu Start*/
    $(document).on("click", '#myonoffswitch13', function() {
        if (this.checked) {
			$('body').addClass('default-menu');
			$('body').removeClass('sidenav-toggled');
			hovermenu();
			$('body').removeClass('closed-menu');
			$('body').removeClass('icontext-menu');
			$('body').removeClass('icon-overlay');
			$('body').removeClass('hover-submenu');
			$('body').removeClass('hover-submenu1');
        } else {
            $('body').removeClass('default-menu');
        }
    });
	/*Default Sidemenu End*/

	/*Closed Sidemenu Start*/
    $(document).on("click", '#myonoffswitch30', function() {
        if (this.checked) {
			$('body').addClass('closed-menu');
			hovermenu();
			$('body').addClass('sidenav-toggled');
			$('body').removeClass('default-menu');
			$('body').removeClass('icontext-menu');
			$('body').removeClass('icon-overlay');
			$('body').removeClass('hover-submenu');
			$('body').removeClass('hover-submenu1');
        } else {
            $('body').removeClass('closed-menu');
			$('body').removeClass('sidenav-toggled');
        }
    });
	/*Closed Sidemenu End*/


	/*Icon Text Sidemenu Start*/
	$('#myonoffswitch14').click(function() {
        if (this.checked) {
			$('body').addClass('icontext-menu');
			icontext();
			$('body').addClass('sidenav-toggled');
			$('body').removeClass('default-menu');
			$('body').removeClass('icon-overlay');
			$('body').removeClass('closed-menu');
			$('body').removeClass('hover-submenu');
			$('body').removeClass('hover-submenu1');

        } else {
            $('body').removeClass('icontext-menu');
			$('body').removeClass('sidenav-toggled');
        }
    });
	/*Icon Text Sidemenu End*/

	/*Icon Overlay Sidemenu Start*/
	$('#myonoffswitch15').on('click', function () {
        if (this.checked) {
            $('body').addClass('icon-overlay');
            hovermenu();
            $('body').addClass('sidenav-toggled');
            $('body').removeClass('hover-submenu1');
            $('body').removeClass('default-menu');
            $('body').removeClass('closed-menu');
            $('body').removeClass('hover-submenu');
            $('body').removeClass('icontext-menu');
        } else {
            $('body').removeClass('icon-overlay');
            $('body').removeClass('sidenav-toggled');
        }
    });
	/*Icon Overlay Sidemenu End*/

	// HOVER SUBMENU START
	$('#myonoffswitch17').on('click', function () {
        if (this.checked) {
            $('body').addClass('hover-submenu');
            hovermenu();
            $('body').addClass('sidenav-toggled');
            $('body').removeClass('hover-submenu1');
            $('body').removeClass('default-menu');
            $('body').removeClass('closed-menu');
            $('body').removeClass('icon-overlay');
            $('body').removeClass('icontext-menu');
            $('.app-sidebar').removeClass('sidemenu-scroll');
        } else {
            $('body').removeClass('hover-submenu');
            $('body').removeClass('sidenav-toggled');
        }
    });
	// HOVER SUBMENU END

	// HOVER SUBMENU STYLE-1 START
    $('#myonoffswitch18').on('click', function () {
        if (this.checked) {
            $('body').addClass('hover-submenu1');
            hovermenu();
            $('body').addClass('sidenav-toggled');
            $('body').removeClass('hover-submenu');
            $('body').removeClass('default-menu');
            $('body').removeClass('closed-leftmenu');
            $('body').removeClass('icon-overlay');
            $('body').removeClass('icontext-menu');
            $('.app-sidebar').removeClass('sidemenu-scroll');
        } else {
            $('body').removeClass('hover-submenu1');
            $('body').removeClass('sidenav-toggled');
        }
    });

    // HOVER SUBMENU STYLE-1 END

	// RTL STYLE START
	$('#myonoffswitch55').on('click', function () {
		if (this.checked) {
			$('body').addClass('rtl');

			$('#slide-left').removeClass('d-none');
			$('#slide-right').removeClass('d-none');
			$("html[lang=en]").attr("dir", "rtl");
			$('body').removeClass('ltr');
			$("head link#style").attr("href", $(this));
			(document.getElementById("style").setAttribute("href", "../assets/plugins/bootstrap/css/bootstrap.rtl.min.css"));
			var carousel = $('.owl-carousel');
			$.each(carousel, function (index, element) {
				// element == this
				var carouselData = $(element).data('owl.carousel');
				carouselData.settings.rtl = true; //don't know if both are necessary
				carouselData.options.rtl = true;
				$(element).trigger('refresh.owl.carousel');
			});
			localStorage.setItem('azeartl', true)
			localStorage.removeItem('azealtr')
			if (!document.querySelector('body').classList.contains('login-page') && !document.querySelector('body').classList.contains('error-bg')) {
				checkHoriMenu();
			}
		}
	});

	if ($("body").hasClass("rtl")) {
		$('body').addClass('rtl');

		$('#slide-left').removeClass('d-none');
		$('#slide-right').removeClass('d-none');
		$("html[lang=en]").attr("dir", "rtl");
		$('body').removeClass('ltr');
		$("head link#style").attr("href", $(this));
		(document.getElementById("style").setAttribute("href", "../assets/plugins/bootstrap/css/bootstrap.rtl.min.css"));
		var carousel = $('.owl-carousel');
		$.each(carousel, function (index, element) {
			// element == this
			var carouselData = $(element).data('owl.carousel');
			carouselData.settings.rtl = true; //don't know if both are necessary
			carouselData.options.rtl = true;
			$(element).trigger('refresh.owl.carousel');
		});

	}
	// RTL STYLE END

	// LTR STYLE START
	$('#myonoffswitch54').on('click', function () {
		if (this.checked) {
			$('body').addClass('ltr');

			$('#slide-left').removeClass('d-none');
			$('#slide-right').removeClass('d-none');
			$("html[lang=en]").attr("dir", "ltr");
			$('body').removeClass('rtl');
			$("head link#style").attr("href", $(this));
			(document.getElementById("style").setAttribute("href", "../assets/plugins/bootstrap/css/bootstrap.min.css"));
			var carousel = $('.owl-carousel');
			$.each(carousel, function (index, element) {
				// element == this
				var carouselData = $(element).data('owl.carousel');
				carouselData.settings.rtl = false; //don't know if both are necessary
				carouselData.options.rtl = false;
				$(element).trigger('refresh.owl.carousel');
			});
			localStorage.setItem('azealtr', true)
			localStorage.removeItem('azeartl')
			if (!document.querySelector('body').classList.contains('login-page') && !document.querySelector('body').classList.contains('error-bg')) {
				checkHoriMenu();
			}
		}
	});
	// LTR STYLE END

	$(document).ready (function(){
		let bodyRtl = $('body').hasClass('rtl');
		if (bodyRtl) {
				$('body').addClass('rtl');
				localStorage.setItem("rtl", "True");
				$("head link#style").attr("href", $(this));
				(document.getElementById("style")?.setAttribute("href", "../assets/plugins/bootstrap/css/bootstrap.rtl.min.css"));
			}
			else {
				$('body').removeClass('rtl');
				localStorage.setItem("rtl", "false");
				$("head link#style").attr("href", $(this));
				(document.getElementById("style")?.setAttribute("href", "../assets/plugins/bootstrap/css/bootstrap.min.css"));
			};
	});

	// HORIZONTAL
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

	$('#myonoffswitch35').on('click', function () {
		if (this.checked) {
			sidemenudropdown();
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
			localStorage.setItem("azeahorizontal", true);
			localStorage.removeItem("azeasidebarMini");
			localStorage.removeItem("azeahorizontalHover");
			if (!document.querySelector('body').classList.contains('login-page') && !document.querySelector('body').classList.contains('error-bg')) {
				checkHoriMenu();
				responsive();
			}
		}
	});

	$('#myonoffswitch34').on('click', function () {
		if (this.checked) {
			sidemenudropdown();
			$('body').removeClass('horizontal');
			$('body').removeClass('horizontal-hover');
			$(".main-content").removeClass("hor-content");
			$(".main-content").addClass("app-content");
			$(".main-container").removeClass("container");
			$(".main-container").addClass("container-fluid");
			$(".hor-header").addClass("app-header");
			$(".app-header").removeClass("hor-header");
			$(".app-sidebar").removeClass("horizontal-main")
			$(".main-sidemenu").removeClass("container")
			$('#slide-left').removeClass('d-none');
			$('#slide-right').removeClass('d-none');
			$('body').addClass('sidebar-mini');
			localStorage.setItem("azeasidebarMini", "true");
			localStorage.removeItem("azeahorizontal");
			localStorage.removeItem("azeahorizontalHover");
			// menuClick();
			responsive();
		}
	});

	$('#myonoffswitch111').on('click', function () {
		if (this.checked) {
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
			$('#slide-left').removeClass('d-none');
			$('#slide-right').removeClass('d-none');
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
			localStorage.setItem("azeahorizontalHover", true);
			localStorage.removeItem("azeasidebarMini");
			localStorage.removeItem("azeahorizontal");

		}

	});

    // HORIZONTAL HOVER
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
    else {
    }

})(jQuery);


function checkOptions() {

    // rtl
    if (document.querySelector('body').classList.contains('rtl')) {
        $('#myonoffswitch24').prop('checked', true);
    }
    // horizontal
    if (document.querySelector('body').classList.contains('horizontal')) {
        $('#myonoffswitch35').prop('checked', true);
    }
    // horizontal-hover
    if (document.querySelector('body').classList.contains('horizontal-hover')) {
        $('#myonoffswitch111').prop('checked', true);
    }
    // light header
    if (document.querySelector('body').classList.contains('header-light')) {
        $('#myonoffswitch6').prop('checked', true);
    }
    // color header
    if (document.querySelector('body').classList.contains('color-header')) {
        $('#myonoffswitch7').prop('checked', true);
    }
    // gradient header
    if (document.querySelector('body').classList.contains('gradient-header')) {
        $('#myonoffswitch20').prop('checked', true);
    }
    // dark header
    if (document.querySelector('body').classList.contains('dark-header')) {
        $('#myonoffswitch8').prop('checked', true);
    }

    // light menu
    if (document.querySelector('body').classList.contains('light-menu')) {
        $('#myonoffswitch3').prop('checked', true);
    }
    // color menu
    if (document.querySelector('body').classList.contains('color-menu')) {
        $('#myonoffswitch4').prop('checked', true);
    }
    // gradient menu
    if (document.querySelector('body').classList.contains('gradient-menu')) {
        $('#myonoffswitch19').prop('checked', true);
    }
    // dark menu
    if (document.querySelector('body').classList.contains('dark-menu')) {
        $('#myonoffswitch5').prop('checked', true);
    }
}
checkOptions()


// RESET SWITCHER TO DEFAULT
function resetData() {
    $('#myonoffswitch34').prop('checked', true);
    $('#myonoffswitch54').prop('checked', true);
    $('#myonoffswitch1').prop('checked', true);
    $('#myonoffswitch3').prop('checked', true);
    $('#myonoffswitch6').prop('checked', true);
    $('#myonoffswitch9').prop('checked', true);
    $('#myonoffswitch11').prop('checked', true);
    $('#myonoffswitch13').prop('checked', true);
    $('body')?.removeClass('dark-mode');
    $('body')?.removeClass('dark-menu');
    $('body')?.removeClass('color-menu');
    $('body')?.removeClass('gradient-menu');
    $('body')?.removeClass('dark-header');
    $('body')?.removeClass('color-header');
    $('body')?.removeClass('gradient-header');
    $('body')?.removeClass('layout-boxed');
    $('body')?.removeClass('icontext-menu');
    $('body')?.removeClass('icon-overlay');
    $('body')?.removeClass('closed-leftmenu');
    $('body')?.removeClass('hover-submenu');
    $('body')?.removeClass('hover-submenu1');
    $('body')?.removeClass('sidenav-toggled');
    $('body')?.removeClass('scrollable-layout');
    $('body')?.removeClass('rtl');
    $('body')?.addClass('ltr');
    //Vertical
    $('body').removeClass('horizontal');
    $('body').removeClass('horizontal-hover');
    $(".main-content").removeClass("hor-content");
    $(".main-content").addClass("app-content");
    $(".main-container").removeClass("container");
    $(".main-container").addClass("container-fluid");
    $(".app-header").removeClass("hor-header");
    $(".hor-header").addClass("app-header");
    $(".app-sidebar").removeClass("horizontal-main")
    $(".main-sidemenu").removeClass("container")
    $('#slide-left').removeClass('d-none');
    $('#slide-right').removeClass('d-none');
    $('body').addClass('sidebar-mini');

    //ltr
    $('#slide-left').removeClass('d-none');
    $('#slide-right').removeClass('d-none');
    $("html[lang=en]").attr("dir", "ltr");
    $('body').removeClass('rtl');
    $("head link#style").attr("href", $(this));
    (document.getElementById("style").setAttribute("href", "../assets/plugins/bootstrap/css/bootstrap.min.css"));
    var carousel = $('.owl-carousel');
    $.each(carousel, function (index, element) {
        // element == this
        var carouselData = $(element).data('owl.carousel');
        carouselData.settings.rtl = false; //don't know if both are necessary
        carouselData.options.rtl = false;
        $(element).trigger('refresh.owl.carousel');
    });
    localStorage.setItem('azealtr', true)
    localStorage.removeItem('azeartl')
    if (!document.querySelector('body').classList.contains('login-page') && !document.querySelector('body').classList.contains('error-bg')) {
        checkHoriMenu();
		sidemenudropdown();
		responsive();
    }

}


