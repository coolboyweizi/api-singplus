/*
* 频率控制 返回函数连续调用时，fn 执行频率限定为每多少时间执行一次
* @param fn {function}  需要调用的函数
* @param delay  {number}    延迟时间，单位毫秒
* @param immediate  {bool} 给 immediate参数传递false 绑定的函数先执行，而不是delay后后执行。
* @return {function}实际调用函数
*/
var throttle = function (fn,delay, immediate, debounce) {
	var curr = +new Date(),//当前事件
	last_call = 0,
	last_exec = 0,
	timer = null,
	diff, //时间差
	context,//上下文
	args,
	exec = function () {
	last_exec = curr;
	fn.apply(context, args);
	};
	return function () {
	curr= +new Date();
	context = this,
	args = arguments,
	diff = curr - (debounce ? last_call : last_exec) - delay;
	clearTimeout(timer);
	if (debounce) {
	if (immediate) {
	timer = setTimeout(exec, delay);
	} else if (diff >= 0) {
	exec();
	}
	} else {
	if (diff >= 0) {
	exec();
	} else if (immediate) {
	timer = setTimeout(exec, -diff);
	}
	}
	last_call = curr;
	}
};

$(function() {
	var title = $('#title'),
		avatar = $('#avatar'),
		nickname = $('#nickname'),
		fans = $('#fans'),
		playNums = $('#playNums'),
		favouriteNums = $('#favouriteNums'),
		publishDate = $('#publishDate');

	var owlCarouselEl = $('#owlCarousel'),
		commentInput = $('#commentInput'),
		commentBtn = $('#commentBtn'),
		playArea = $('#playArea'),
		media = $('#media'),
		commentsEl = $('#comments'),
		lyricPanel = $('#lyricPanel'),
		loadMoreEl = $('.loadmore');
	var lyric4H5;

	// 判断是否是pc
	function IsPC() {
	    var userAgentInfo = navigator.userAgent;
	    var Agents = ["Android", "iPhone",
	                "SymbianOS", "Windows Phone",
	                "iPad", "iPod"];
	    var flag = true;
	    for (var v = 0; v < Agents.length; v++) {
	        if (userAgentInfo.indexOf(Agents[v]) > 0) {
	            flag = false;
	            break;
	        }
	    }
	    return flag;
	}

	function isSingplus() {
		return location.host.indexOf("api.sing.plus") > -1 ? true : false;
	}

	function isBoomSing() {
		return location.host.indexOf("api.boom.sing.plus") > -1 ? true : false;
	}

	function getNativeAppUrl(workId) {
		return isBoomSing() ? 'boomsing://share/workDetail?workId=' + workId :
			'singplus://share/workDetail?workId=' + workId;
	}

	function getNativeGpUrl() {
		return isBoomSing() ? 'market://details?id=com.karaoke.boomsing&referrer=utm_source%3Dshare' :
			'market://details?id=com.karaoke.singplus&referrer=utm_source%3Dshare';
	}

	function getWebGpUrl() {
		return isBoomSing() ? 'http://market.android.com/details?id=com.karaoke.boomsing&referrer=utm_source%3Dcontest' :
		'http://market.android.com/details?id=com.karaoke.singplus&referrer=utm_source%3Dcontest';
	}

	function initLyric(lyric) {
		lyric4H5 = new Lyric4H5(lyric, function(a){console.log(a)});
	}

	$(".favouriteBtn").click(function() {
		needDownloadAppDialog();
	});

	$('.guide-download-btn').click(function() {
		if (navigator.userAgent.match(/android/i)) {
			openAndroid();
		}
	});

	function openNative(nativeUrl, failedCallback) {
		var ifrSrc = nativeUrl;
	    var ifr = document.createElement('iframe');
	    ifr.src = ifrSrc;
	    ifr.style.display = 'none';
		var t = Date.now();
		document.body.appendChild(ifr);

		setTimeout(function() {
		 	document.body.removeChild(ifr);
			if(!t || Date.now() - t < 600) {
				failedCallback && failedCallback();
			}
		}, 500);
	}

	function openAndroid() {
	    var ifrSrc = getNativeAppUrl(workId);
	    openNative(ifrSrc, function() {
	    	openNative(getNativeGpUrl(), function() {
	    		location.href = getWebGpUrl();
	    	})
	    })
	}

	// 初始化音频播放器
	function initPlayer(address) {
		if (!address) return;
		media.attr("src", address);
		audiojs.events.ready(function() {
     		var as = audiojs.createAll();
     		as[0].element.addEventListener("timeupdate", function(a) {
     			if (lyric4H5 && a) {
     				lyricPanel.text(lyric4H5.getCurrentLryic(this.currentTime * 1000));
     			}
     		});

	        // as[0].element.addEventListener("loadeddata", function(a) {

	        // });

	        as[0].element.addEventListener("canplay", function(a) { 
	          if (as[0].element.currentTime <= 0) {
	            as[0].element.currentTime = 0.1;
	          }

	          if (IsPC()) {
	          	as[0].play();
	          }
	        });

     		as[0].element.addEventListener("play", function(a) {
	        	document.removeEventListener('touchstart', forceSafariPlayAudio, false);
	        });

	        document.addEventListener('touchstart', forceSafariPlayAudio, false);

	        function forceSafariPlayAudio(e) {
	        	if (!$(e.target).parent('.play-pause').length) {
					as[0].play();
				}
	        }
   		});
	}

	// 动态计算播放器高度 宽高比例1:1
	function compluateAdjustMusicBoxHeight() {
		playArea.height($('body').width());
	}

	// 提示下载app
	function needDownloadAppDialog() {
		layer.open({
            content: 'Psst! You need to download Sing Plus app to interact with the singer. Free download now?',  // 这里需要翻译
            btn: ['okay', 'maybe later'],
            yes: function(index) {
            	if (navigator.userAgent.match(/android/i)) {
					openAndroid();
				}
            }
        });
	}

	// 评论框事件
	function setCommentListener() {
		commentBtn.click(function() {
			needDownloadAppDialog();
		})

		commentInput.attr("readonly", "readonly");
		commentInput.click(function() {
			needDownloadAppDialog();
			return true;
		})
	}

	function setCommentsListener() {
		commentsEl.on("click", ".wd-comment", function() {
			needDownloadAppDialog();
		})
	}

	if (resourceUrl) {
		initPlayer(resourceUrl);
	}

	if (lyricUrl) {
		initLyric(lyricUrl);
	}

	// 初始化轮播图
	if (owlCarouselEl.find(".item").length > 1) {
		owlCarouselEl.owlCarousel({
			items: 1,
			loop: true,
			autoplay: true,
			mouseDrag: false,
			touchDrag: false,
			nav: false,
			dots: false,
			autoplaySpeed: 3000,
			autoplayTimeout: 5000
		});
	}

	compluateAdjustMusicBoxHeight();
	setCommentListener();
	setCommentsListener();

	var showComment = (function() {
		var page = 0;
		var pageShowItem = 3;
		//var loadMoreEl = $('.loadmore');
		var commentItems = commentsEl.find(".wd-comment");
		var commentsLength = commentItems.length;
		var clickLock = false;
		loadMoreEl.click(function() {
			if (clickLock) { return; }
			clickLock = true;
			setTimeout(function() {
				showComment();
				clickLock = false;
			}, 300)
		})

		return function() {
			if ((page + 1)  * pageShowItem >= commentsLength) {
				loadMoreEl.hide();
			} else {
				loadMoreEl.css({'display': "block"});
			}

			if (page * pageShowItem < commentsLength) {
				page ++;
				$.each(commentItems, function(i) {
					if (page * pageShowItem > i) {
						commentItems.eq(i).css({"display": "flex"});
					} else {
						commentItems.eq(i).hide();
					}
				})
			}
		}
	})();

	showComment();

	$('#commentLink').click(function() {
		$('.wd-comments').show();
		$('.wd-recordings').hide();
		$('.wd-nav-item').removeClass('wd-nav-item-select');
		$(this).parent().addClass('wd-nav-item-select');
	});

	$('#recordLink').click(function() {
		$('.wd-comments').hide();
		$('.wd-recordings').show();
		$('.wd-nav-item').removeClass('wd-nav-item-select');
		$(this).parent().addClass('wd-nav-item-select');
	})

	$('#commentLink').click(function() {
		$('.wd-comments').show();
		$('.wd-recordings').hide();
		$('.wd-nav-item').removeClass('wd-nav-item-select');
		$(this).parent().addClass('wd-nav-item-select');
		$('.wd-nav-item').find('.navbar').hide();
		$(this).find('.navbar').show();
	});

	$('#recordLink').click(function() {
		$('.wd-comments').hide();
		$('.wd-recordings').show();
		$('.wd-nav-item').removeClass('wd-nav-item-select');
		$(this).parent().addClass('wd-nav-item-select');
		$('.wd-nav-item').find('.navbar').hide();
		$(this).find('.navbar').show();
	});

	$('#recordLink').click();

	var wdNavConEl = $('.wd-nav-con'),
		wdInfoTitleEl = $('.wd-info-title'),
		mainPanelEl = $('.main-panel'),
		pageBottomEl = $('.page-bottom');

	var pbTop = pageBottomEl.offset().top,
		infoTitleTop = wdInfoTitleEl.offset().top;

	if (infoTitleTop > 0 && pbTop > 0) {
		renderPageBottomStatus(true);
	}

	function renderPageBottomStatus(isSuspend) {
		if (isSuspend) {
			pageBottomEl.css({position: 'fixed', top: infoTitleTop});
			mainPanelEl.css({paddingBottom: 200});
		} else {
			pageBottomEl.css({position: ''})
			mainPanelEl.css({paddingBottom: 0})
		}
	}

	$(window).scroll(function() {
		console.log(pbTop + ":" + infoTitleTop);
		if (infoTitleTop > 0 && pbTop > 0) {
			if ($('body').scrollTop() >= pbTop - infoTitleTop) {
				renderPageBottomStatus(false);
			} else {
				renderPageBottomStatus(true);
			}
		}
	});

	// 精选图片位置
	function calculateSize(el, frameWidth, frameHeight, realWidth, realHeight) {
		if (realWidth <=0 || realHeight <=0) {
			return;
		}
		
		if (realWidth != realHeight) {
            if (realWidth > realHeight) {
                var width = (frameWidth / realHeight) * realWidth;//等比缩放宽度
                var height = frameHeight;//跟div高度一致
                var left = '-' + ((frameHeight / realHeight) * realWidth - frameHeight) / 2 + 'px';//设置图片相对自己位置偏移为img标签的宽度-高度的一半
            	el.css({width: width, height: height, marginLeft: left}).addClass('wd-recordings-cover-auto');
            } else if (realWidth < realHeight) {
                var width = frameHeight;//跟div高度一致
                var height = (frameWidth / realWidth) * realHeight;//等比缩放高度
                var top = '-' + ((frameWidth / realWidth) * realHeight - frameWidth) / 2 + 'px';//设置图片相对自己位置偏移为img标签的高度-宽度的一半
            	el.css({width: width, height: height, marginTop: top,  maxWidth: "none"}).addClass('wd-recordings-cover-auto');
            }
		}
	}

	var frameWidth = frameHeight = imageWidth = $('.wd-recordings-cover').eq(0).width();
	if (imageWidth > 0) {
		$('.wd-recordings-top').css({height: imageWidth});
		var imgArr = $(".wd-recordings-cover");
		$.each(imgArr, function(index) {
			var _this = imgArr.eq(index);
			var realWidth = _this.width();
			var realHeight = _this.height();
			if (realWidth > 0 && realHeight > 0) {
				console.log($(this).attr("src"));
				calculateSize(_this, imageWidth, imageWidth, realWidth, realHeight);
			} else {
				_this.load(function() {
					calculateSize(_this, imageWidth, imageWidth, _this.width(), _this.height());
				});
			}
		})
	}

	function setLogoIcon() {
		if (isSingplus()) {
			$('.guide-download-logo').attr("src", "/c/images/ic_launcher.png");
		} else if (isBoomSing()){
			$('.guide-download-logo').attr("src", "/c/images/ic_launcher_boom.png");
		}
	}

	function setBottomBg() {
		if (isSingplus()) {
			$('.ad-con-bg').attr('src', "/c/images/ad_guide.png");
		} else if (isBoomSing()) {
			$('.ad-con-bg').attr('src', "/c/images/ad_guide_boom.png");
		}
	}

	$('#gpDown').click(function() {
		openNative(getNativeGpUrl(), function() {
			location.href = getWebGpUrl();
		})
	})

	$('#usDown').click(function() {
		if (isBoomSing()) {
			location.href = "https://www.boomsing.me/wp-content/uploads/app-pc-release.apk";
		} else if (isSingplus()) {
			location.href = "https://www.sing.plus/wp-content/uploads/app-pc-release.apk";
		}
	})

	setLogoIcon();
	setBottomBg();
})
