<!DOCTYPE html>
<!-- 作品分享页面 -->
<html>
    <head>
        <?php $musicName = ""; ?>
        @if ($data->work->workName)
            <?php $musicName = $data->work->workName; ?>
        @else 
            <?php $musicName = $data->work->music->name; ?>
        @endif
        <?php $channelString = Config::get('tudc.currentChannel') == "singplus" ? (Config::get('tudc.channels.singplus.appId') == "singplus" ? "SingPlus" : "GaaoPlus") : "BoomSing" ?>
        <?php $thirdName = Config::get('tudc.currentChannel') == "singplus" ? (Config::get('tudc.channels.singplus.appId') == "singplus" ? "Sing+" : "Gaao+") : "BoomSing" ?>
        <?php $isMyWork = $data->userInfo && $data->work && $data->userInfo->user_id == $data->work->user->userId ? true : false ?>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
        <meta name="description" content="{{$data->work->description}}" />
        <meta property="fb:app_id" content="1836155316648956"/>
        <meta property="og:title" content="@if ($isMyWork) I've recorded a song with {{$thirdName}},listen and comment @else Listen to {{$data->work->user->nickname}} sing {{$musicName}} @endif" />
        <meta property="og:description" content="@if ($isMyWork) Cover by {{$data->work->user->nickname}} @else {{$data->work->description}} @endif"/>
        <meta property="og:image" content="{{ $data->work->cover }}"/>
        <meta property="al:web:url" content="{{ secure_url(sprintf('c/page/works/%s', $data->work->workId)) }}"/>

        <meta property="al:android:url" content="singplus://share/workDetail?workId={{$data->work->workId}}">
        <!-- <meta property="al:android:package" content="com.karaoke.singplus"> -->
        <meta property="al:android:app_name" content="{{$thirdName}}">

        <title>@if ($isMyWork) I've recorded a song with {{$thirdName}},listen and comment @else Listen to {{$data->work->user->nickname}} sing {{$musicName}} @endif</title>
        <link rel="stylesheet" href="/c/css/normalize.css" />
        <link rel="stylesheet" href="/c/third/owlcarousel/assets/owl.carousel.min.css">
        <link rel="stylesheet" href="/c/third/owlcarousel/assets/owl.theme.default.min.css">
        <link rel="stylesheet" href="/c/third/layer_mobile/need/layer.css">
        <link rel="stylesheet" href="/c/css/common.css" />
        <link rel="styleSheet" href="/c/css/workDetail.css" />
    </head>

    <body class="gray-body detail-page" aaa="#{{$channelString}}">
    <!-- 引导打开app -->
    <div class="guide-download">
        @if ($channelString == 'SingPlus')
            <img src="/c/images/ic_launcher.png" class="guide-download-logo" />
        @elseif ($channelString == 'GaaoPlus')
            <img src="/c/images/ic_launcher_gaao.png" class="guide-download-logo" />
        @else
            <img src="/c/images/ic_launcher_boom.jpg" class="guide-download-logo" />
        @endif
        <span class="guide-download-text"><?php $thirdName = Config::get('tudc.currentChannel') == "singplus" ? (Config::get('tudc.channels.singplus.appId') == "singplus" ? "Karaoke on the go" : "When karaoke meets India
") : "#1 karaoke app in Africa" ?></span>
        <a href="javascript:;" class="guide-download-btn">Open Now</a>
    </div>
    <!-- <div class="header">
        <span class="header-title" id="title">{{ $data->work->music->name }}</span>
    </div> -->

    <div class="main-panel bg-white">
        <div class="wd-play-panel" id="playArea">
            <!--播放幻灯片区域-->
            <div class="wd-slides">
                @if (count($data->work->slides) > 1)
                    <div class="owl-carousel" id="owlCarousel">
                @else
                    <div class="owl-carousel owl-carousel-item" id="owlCarousel">
                @endif
                
                    @if (count($data->work->slides) > 0)
                        @foreach ($data->work->slides as $image)
                            <div class ="item"><img class="response-img" src="{{ $image }}"/></div>
                        @endforeach
                    @else
                        <div class ="item"><img class="response-img" src="{{ $data->work->cover }}"/></div>
                    @endif
                </div>
            </div>

            <!--播放歌词区域-->
            <div class="wd-lyric" id="lyricPanel">
              
            </div>

            <!--音频播放-->
            <div class="media-container">
                <audio id="media" width="300" src=""></audio> 
            </div>

            <img class="favouriteBtn" src="/c/images/favourite.png" />
        </div>

        <div class="wd-infos-panel">
            <div class="avatar-box">
                @if ($data->work->chorusType == 1) 
                    <div class="avatar-box-item">
                        <img class="avatar" id="avatar" src="{{ $data->work->user->avatar }}" />
                        <span class="nickname" id="nickname">{{ $data->work->user->nickname }}</span>
                    </div>
                    <div class="avatar-box-item">
                        <img class="andicon" src="/c/images/add.png">
                        <img class="avatar" id="avatar" src="/c/images/default_avatar1.png" />
                        <span class="nickname" id="nickname">{{ $data->work->chorusStartInfo->chorusCount }} Joins</span>
                    </div>
                @elseif ($data->work->chorusType == 10)
                    <div class="avatar-box-item">
                        <img class="avatar" id="avatar" src="{{ $data->work->user->avatar }}" />
                        <span class="nickname" id="nickname">{{ $data->work->user->nickname }}</span>
                    </div>
                    <div class="avatar-box-item">
                        <img class="andicon" src="/c/images/add.png">
                        <img class="avatar" id="avatar" src="{{ $data->work->chorusJoinInfo->author->avatar }}" />
                        <span class="nickname" id="nickname">{{ $data->work->chorusJoinInfo->author->nickname }}</span>
                    </div>
                @else
                    <img class="avatar" id="avatar" src="{{ $data->work->user->avatar }}" />
                    <span class="nickname" id="nickname">{{ $data->work->user->nickname }}</span>
                @endif    
            </div>

            <p class="wd-info-title">{{ $musicName }}</p>

            <p class="wd-info-content" id="content">{{ $data->work->description }}</p>

            <div class="wd-info-bot clearfix">
                <div class="playnum-wrap">
                    <img class="icon" src="/c/images/play_dark.png"/>
                    <span class="nums" id="playNums">{{ $data->work->listenCount }}</span>
                    <img class="icon" src="/c/images/favourite_dark.png" />
                    <span class="nums" id="favouriteNums">{{ $data->work->favouriteCount }}</span>
                    <img class="icon" src="/c/images/comment_dark.png" />
                    <span class="nums" id="favouriteNums">{{ $data->work->commentCount }}</span>
                </div>

                <span class="date" id="publishDate">{{ $data->work->createdAt->format(config('datetime.format.default.datetime')) }}</span>
            </div>
        </div>

        <div class="divide-bar"></div>
    </div>

    <div class="page-bottom">
        <ul class="wd-nav">
            <li class="wd-nav-item">
                <a href="javascript:;" id="commentLink" class="wd-nav-link">Comment<span class="navbar"></span></a>
            </li>
            <li class="wd-nav-item wd-nav-item-select">
                <a href="javascript:;" id="recordLink" class="wd-nav-link">Recordings<img src="/c/images/recording_icon.png" class="recording_icon"><span class="navbar"></span></a>
            </li>
        </ul>
        
        <div class="wd-comments">
            <ul class="wd-comment-list" id="comments">
              @foreach ($data->comments as $comment)
                <li class="wd-comment">
                  <img class="avatar" src="{{ $comment->author->avatar }}"/>
                  <div class="content">
                    <div class="content-top">
                      <span class="nickname">{{ $comment->author->nickname }}</span>
                      <span class="date">{{ $comment->createdAt->format(config('datetime.format.default.datetime')) }}</span>
                    </div>
                    <p class="content-text">
                      @if ($comment->repliedCommentId) 
                        Reply {{$comment->repliedUser->nickname}}：{{ $comment->content }} 
                      @elseif ($comment->commentType == 2)
                        Reshared a cover：{{ $comment->content }}
                      @elseif ($comment->commentType == 1)
                        Joined to Collab：{{ $comment->content }}
                      @else
                        {{ $comment->content }}
                      @endif
                    </p>
                  </div>
                </li>
              @endforeach
            </ul>
            <a class="loadmore">Load More</a>
        </div>

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
        <p class="ad-con-divide"></p>
        <div class="ad-con">
            @if ($channelString == 'SingPlus')
                <img class="ad-con-bg" src="/c/images/ad_guide.png"/>
            @elseif ($channelString == 'GaaoPlus')
                <img class="ad-con-bg" src="/c/images/ad_guide_gaao.png"/>
            @else
                <img class="ad-con-bg" src="/c/images/ad_guide_boom.png"/>
            @endif
            <div class="ad-bot-link-wrap clearfix">
                @if ($channelString != 'GaaoPlus')
                    <a id="gpDown" href="javascript:;" class="ad-con-link">
                        <img class="ad-con-gp-icon" src="/c/images/gp_button.png"/>
                    </a>
                @endif
                <a id="usDown" href="javascript:;" class="ad-con-link-right">
                    <img class="ad-con-gp-icon" src="/c/images/download.png"/>
                </a>
            </div>
        </div>
    </div>

    <script>
      var resourceUrl = "{{ $data->work->resource }}";
      var lyricUrl = "{{ $data->work->music->lyric }}";
      var workId = "{{ $data->work->workId }}";
    </script>

    <script src="/c/js/jquery.min.js" type="text/javascript"></script>
    <script src="/c/js/utils.js" type="text/javascript"></script>
    <script src="/c/third/audio/audio.js" type="text/javascript"></script>
    <script src="/c/third/owlcarousel/owl.carousel.min.js"></script>  
    <script src="/c/third/layer_mobile/layer.js" type="text/javascript"></script> 
    <script src="/c/js/lyrc.h5.js" type="text/javascript"></script>
    <script src="/c/js/common.js" type="text/javascript"></script>
    <script src="/c/js/workDetail.js" type="text/javascript"></script>

    <script type="text/javascript">
      (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
      (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
      m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
      })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

      ga('create', 'UA-90402878-3', 'auto');
      ga('send', 'pageview', location.href.replace("works/"+workId, "works"));
    </script>
    </body>
</html>
