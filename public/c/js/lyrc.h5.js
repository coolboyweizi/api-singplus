/**
 * Created by plum on 2017/4/10.
 */
function Lyric4H5(lyric_url, callback){
    var lyric_url = lyric_url;
    var _callback = callback ? callback : function(raw){
        console.log(raw);
    }
    var lryic = {
        "tags" : {},
        "lyrics" : []
    };
    var me = this;

    this.loadLyric = function(){
        $.get(
            lyric_url,
            function(raw){
                me.parseLyric(raw);
            }
        );
    };

    this.parseLyric = function(raw){
        var lyrics = raw.split("\n");
        for(var i=0, rows = lyrics.length; i<rows; ++i){
            var line = $.trim(lyrics[i]);
            var regtag = /^\[(\w+):(.*?)\]$/g;
            regtag.compile(regtag);
            var regRowTime = /\[(\d+),(\d+)\](?:([M|F|D]):)?/g
            regRowTime.compile(regRowTime);
            if( regtag.test(line)){
                var tags = line.split(regtag);
                lryic.tags[tags[1]] = tags[2];
            }else{
                var rowTime = line.split(regRowTime);
                var nextVoice = "";
                for(var jmax = rowTime.length, j = jmax -1; j > 1; j-=4){
                    lryic.lyrics.push({
                        "l" : line.replace(regRowTime, ""),
                        "s" : parseInt(rowTime[j-3], 10),
                        "d" : parseInt(rowTime[j-2], 10),
                        "v" : undefined !== rowTime[j-1] ? rowTime[j-1] : nextVoice,
                    });
                    nextVoice = rowTime[j-1];
                }
            }
        }
        lryic.lyrics = lryic.lyrics.sort(function(a, b){
             return a.s < b.s ? -1 : (a.s > b.s ? 1 : 0)
        });
        _callback(lryic)
    };
    this.getCurrentLryic = function(offset){
        if(lryic.lyrics.length < 1) return false;
        var line = null;
        for(var i =0, imax=lryic.lyrics.length; i < imax; ++i){
            if (offset  < lryic.lyrics[i].s){
                continue;
            }
            line = lryic.lyrics[i];
            if(offset >= line.s && offset <= line.s + line.d){
                break;
            }
        }
        if (line){
            return line.l;
        }else{
            return null;
        }
    };
    this.getName = function(){
        var v = lryic.tags["ti"];
        if(v){
            return v;
        }
    };
    this.getAlbumName = function(){
        var v = lryic.tags["al"];
        if(v){
            return v;
        }
    };
    this.getArtist = function(){
        var v = lryic.tags["ar"];
        if(v){
            return v;
        }
    };
    this.loadLyric();
}