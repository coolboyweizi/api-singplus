// 工具函数

var utils = {
	getParams: function() {
		var result = {};
		var searchArr = location.search.substr(1).split("&");
		for (var i = 0; i < searchArr.length; i++) {
			if (searchArr[i]) {
				var itemArr = searchArr[i].split("=");
				result[itemArr[0]] = itemArr[1];
			}
		}

		return result;
	},

	getRootUrl: function() {
		var procotol = "https:";
		if("file:" !== location.protocol.toLocaleLowerCase()){
			procotol = location.protocol.toLocaleLowerCase();
		}
		var host = "staging.api.sing.plus";
		if("" !== location.host){
			host = location.host;
		}
		return procotol + "//" + host + "/";
	}
}