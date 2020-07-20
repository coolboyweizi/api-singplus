<!DOCTYPE html>
<!-- 作品分享页面 -->
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
        <meta name="description" content="The #1 karaoke singing app in India" />
        <!-- <meta property="fb:app_id" content="1836155316648956"/> -->
        <meta property="og:title" content="Sing a song and make friends here in Gaao+" />
        <meta property="og:description" content="The #1 karaoke singing app in India"/>
        <meta property="og:image" content="/c/images/landingpage_bg.png"/>
        <meta property="al:web:url" content="{{secure_url('c/page/loadingPage')}}"/>
        <meta property="al:android:url" content="gaaoplus://">
        <!-- <meta property="al:android:package" content="com.karaoke.singplus"> -->
        <meta property="al:android:app_name" content="Gaaoplus">
        <title>Sing a song and make friends here in Gaao+</title>
        <link rel="stylesheet" href="/c/css/normalize.css" />
        <link rel="stylesheet" href="/c/css/common.css" />
        <link rel="stylesheet" href="/c/css/workDetail.css" />
        <link rel="styleSheet" href="/c/css/landingPage.css" />
    </head>

    <body>

        <div class="land-main">
            <img class="response-img" src="/c/images/landingpage_bg.png" alt="">
            <div class="relate">
                <img class="icon" src="/c/images/landingpage_2.png" alt="">
                <p class="tip">Not yet joined them?</p>
                <a class="down-btn" href="https://www.sing.plus/wp-content/uploads/app-gaaoplus-pc.apk">Download</a>
                <div class="share-con">
                    <div class="share-wrap">
                        <a class="twitter-icon" href="javascript:;"><img src="/c/images/landingpage_1.png" alt=""/></a>
                        <a class="whatsup-icon" href="javascript:;"><img src="/c/images/landingpage_3.png" alt=""/></a>
                        <a class="copy-icon" href="javascript:;"><img src="/c/images/landingpage_4.png" alt=""/></a>
                    </div>
                </div>
            </div>
        </div>

        <p class="recording">Recordings</p>
        <div class="wd-recordings">
            <ul class="wd-recordings-list clearfix" id="recordings">
                @foreach ($data->selections as $selection)
                <li class="wd-recordings-item">
                    <a class="wd-recordings-link" attr-href="{{$selection->shareLink}}" href="javascript:;">
                        <div class="wd-recordings-top">
                            <img class="wd-recordings-cover" src="{{$selection->cover}}" />
                            <span class="wd-recordings-name">{{$selection->music->name}}</span>
                        </div>
                        <div class="wd-recordings-bot">
                            <img class="wd-recordings-avatar" src="{{$selection->user->avatar}}" />
                
                            <span class="wd-recordings-num">{{$selection->listenCount}}</span>
                            <img class="wd-recordings-playicon" src="/c/images/play_dark.png" />
                            <span class="wd-recordings-nickname">{{$selection->user->nickname}}</span>
                        </div>
                    </a>
                </li>
                @endforeach
            </ul>
        </div>

        <div class="ad-con">
            <img class="ad-con-bg" src="/c/images/ad_guide_gaao.png">
            <div class="ad-bot-link-wrap clearfix">
                <a id="usDown" href="https://www.sing.plus/wp-content/uploads/app-gaaoplus-pc.apk" class="ad-con-link-right">
                    <img class="ad-con-gp-icon" src="/c/images/download.png">
                </a>
            </div>
        </div>

        <!-- <div class="ld-guide-download">
            <img src="/c/images/ic_launcher_gaao.png" class="ld-guide-download-logo" />
            <span class="ld-guide-download-text">When karaoke meets India</span>
            <a href="https://www.sing.plus/wp-content/uploads/app-gaaoplus-pc.apk" class="ld-guide-download-btn">Download</a>
        </div> -->

    <script src="/c/js/jquery.min.js" type="text/javascript"></script>
    <script src="/c/js/common.js"></script>
    <script src="/c/js/landingPage.js"></script>
    </body>
</html>
