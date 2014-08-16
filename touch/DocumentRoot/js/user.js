/*
 * Cookie
 */
var Cookie={
	read:function(name){
		var value = document.cookie.match('(?:^|;)\\s*' + name + '=([^;]*)');
		return (value) ? decodeURIComponent(value[1]) : null;
	},
	write:function(value){
		var str = value.name + '=' + encodeURIComponent(value.value);
			if(value.domain){ str += '; domain=' + value.domain;}
			if(value.path){ str += '; path=' + value.path;}
			if(value.day){
				var time = new Date();
				time.setTime(time.getTime()+value.day*24*60*60*1000);
				str += '; expires=' + time.toGMTString();
			}
		document.cookie = str;
		return;
	},
	dispose:function(name){
		var str = this.read(name);
		this.write({name:name,value:str,day:-1});
		return;
	}
}

/**获取用户信息**/
;function getLoginUserInfo(){
	var U = {
		//utf8_decode
		utf8_decode : function(str_data) {
			var i = 0, ac = 0, c1 = 0, c2 = 0, c3 = 0 ,tmp_arr = [];
			str_data += '';
			while (i < str_data.length) {
				c1 = str_data.charCodeAt(i);
				if (c1 < 128) {
					tmp_arr[ac++] = String.fromCharCode(c1);
					i++;
				} else if (c1 > 191 && c1 < 224) {
					c2 = str_data.charCodeAt(i + 1);
					tmp_arr[ac++] = String.fromCharCode(((c1 & 31) << 6) | (c2 & 63));
					i += 2;
				} else {
					c2 = str_data.charCodeAt(i + 1);
					c3 = str_data.charCodeAt(i + 2);
					tmp_arr[ac++] = String.fromCharCode(((c1 & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
					i += 3;
				}
			}
			return tmp_arr.join('');
		},
		//base64_decode
		base64_decode : function(data) {
			var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=", o1, o2, o3, h1, h2, h3, h4, bits, i = 0, ac = 0,dec = "",tmp_arr = [];
			if (!data) {
				return data;
			}
			data += '';
			do {
				h1 = b64.indexOf(data.charAt(i++));
				h2 = b64.indexOf(data.charAt(i++));
				h3 = b64.indexOf(data.charAt(i++));
				h4 = b64.indexOf(data.charAt(i++));
				bits = h1 << 18 | h2 << 12 | h3 << 6 | h4;
				o1 = bits >> 16 & 0xff;
				o2 = bits >> 8 & 0xff;
				o3 = bits & 0xff;
				if (h3 == 64) {
					tmp_arr[ac++] = String.fromCharCode(o1);
				} else if (h4 == 64) {
					tmp_arr[ac++] = String.fromCharCode(o1, o2);
				} else {
					tmp_arr[ac++] = String.fromCharCode(o1, o2, o3);
				}
			} while (i < data.length);
			var dec = '';
			for(var i=0; i<tmp_arr.length; i++) {
				dec += tmp_arr[i];
			}
			dec = U.utf8_decode(dec);
			return dec;
		},
		unserialize : function(data) {  
			var that = this,
			utf8Overhead = function (chr) {
			  var code = chr.charCodeAt(0);
			  if (code < 0x0080) {
				return 0;
			  }
			  if (code < 0x0800) {
				return 1;
			  }
			  return 2;
			},
			error = function (type, msg, filename, line) {
			  throw new that.window[type](msg, filename, line);
			},
			read_until = function (data, offset, stopchr) {
			  var i = 2, buf = [], chr = data.slice(offset, offset + 1);

			  while (chr != stopchr) {
				if ((i + offset) > data.length) {
				  error('Error', 'Invalid');
				}
				buf.push(chr);
				chr = data.slice(offset + (i - 1), offset + i);
				i += 1;
			  }
			  return [buf.length, buf.join('')];
			},
			read_chrs = function (data, offset, length) {
			  var i, chr, buf;

			  buf = [];
			  for (i = 0; i < length; i++) {
				chr = data.slice(offset + (i - 1), offset + i);
				buf.push(chr);
				length -= utf8Overhead(chr);
			  }
			  return [buf.length, buf.join('')];
			},
			_unserialize = function (data, offset) {
			  var dtype, dataoffset, keyandchrs, keys, contig,
				length, array, readdata, readData, ccount,
				stringlength, i, key, kprops, kchrs, vprops,
				vchrs, value, chrs = 0,
				typeconvert = function (x) {
				  return x;
				};

			  if (!offset) {
				offset = 0;
			  }
			  dtype = (data.slice(offset, offset + 1)).toLowerCase();

			  dataoffset = offset + 2;

			  switch (dtype) {
				case 'i':
				  typeconvert = function (x) {
					return parseInt(x, 10);
				  };
				  readData = read_until(data, dataoffset, ';');
				  chrs = readData[0];
				  readdata = readData[1];
				  dataoffset += chrs + 1;
				  break;
				case 'b':
				  typeconvert = function (x) {
					return parseInt(x, 10) !== 0;
				  };
				  readData = read_until(data, dataoffset, ';');
				  chrs = readData[0];
				  readdata = readData[1];
				  dataoffset += chrs + 1;
				  break;
				case 'd':
				  typeconvert = function (x) {
					return parseFloat(x);
				  };
				  readData = read_until(data, dataoffset, ';');
				  chrs = readData[0];
				  readdata = readData[1];
				  dataoffset += chrs + 1;
				  break;
				case 'n':
				  readdata = null;
				  break;
				case 's':
				  ccount = read_until(data, dataoffset, ':');
				  chrs = ccount[0];
				  stringlength = ccount[1];
				  dataoffset += chrs + 2;

				  readData = read_chrs(data, dataoffset + 1, parseInt(stringlength, 10));
				  chrs = readData[0];
				  readdata = readData[1];
				  dataoffset += chrs + 2;
				  if (chrs != parseInt(stringlength, 10) && chrs != readdata.length) {
					error('SyntaxError', 'String length mismatch');
				  }
				  break;
				case 'a':
				  readdata = {};

				  keyandchrs = read_until(data, dataoffset, ':');
				  chrs = keyandchrs[0];
				  keys = keyandchrs[1];
				  dataoffset += chrs + 2;

				  length = parseInt(keys, 10);
				  contig = true;

				  for (i = 0; i < length; i++) {
					kprops = _unserialize(data, dataoffset);
					kchrs = kprops[1];
					key = kprops[2];
					dataoffset += kchrs;

					vprops = _unserialize(data, dataoffset);
					vchrs = vprops[1];
					value = vprops[2];
					dataoffset += vchrs;

					if (key !== i)
					  contig = false;

					readdata[key] = value;
				  }

				  if (contig) {
					array = new Array(length);
					for (i = 0; i < length; i++)
					  array[i] = readdata[i];
					readdata = array;
				  }

				  dataoffset += 1;
				  break;
				default:
				  error('SyntaxError', 'Unknown / Unhandled data type(s): ' + dtype);
				  break;
			  }
			  return [dtype, dataoffset - offset, typeconvert(readdata)];
			}
		  ;

		  return _unserialize((data + ''), 0)[2];
		}
	}
	
	try{
		var str = U.unserialize( U.base64_decode( Cookie.read('user_info_app') || '') );
		str
	}catch(exception){
		str = {
			'user_id': 0,
			'user_name' : ''
		}	
	}
	
	return str;
}



