function region_selector(json) {
	var opt_url = 'http://mobile.oakknight.com/index.php?_c=region';
	var prov = $("#" + json['prov']) || null;
	var city = $("#" + json['city']) || null;
	var dist = $("#" + json['dist']) || null;
	var region_id = $("#" + json['region_id']) || null;
	var region_name = $("#" + json['region_name']) || null;
	var current_region = $("#current_region") || null;
	cacheData = {};

	
	//get city data
	function getData(dom, val, defau, fn){
		if(!cacheData['val']){
			$.ajax({
				type: 'get',
				url: opt_url,
				data: {pid:val},
				dataType: 'json',
				timeout: 0,
				success:function(data){
					if(data.status){
						cacheData[val] = data["retval"];
						applyData(dom, data["retval"], defau, fn);
					}
				},
				error: function(xhr, type){
					alert('网络超时,请重试' );
				}
			})
		}else{
			applyData(dom, cacheData[val], defau, fn);
		}
	}
	//apply city data
	function applyData(dom, data, defau, fn){
		//dom.length = 0;
		dom.html('');
		for(var i=0;i<data.length;i++){
			//dom.append('<option value="' + data[i]['region_id'] + '>' + data[i]['region_name'] + '</option>');	
			dom[0].options.add(new Option(data[i]['region_name'],data[i]['region_id']));
		}
		if(fn) fn();
		if(defau) dom.value = defau;
		add_value(dom);
	};
	//get option value
	function getoptionValue(dom){
		var op = dom[0].options, str = '';
		for(var i=0;i<op.length;i++){
			if(op[i].value==dom.val()) str = op[i].innerHTML;
		}
		return str;
	}
	//add value
	function add_value(dom){
		if(dom.val() > 0) {
			region_id.val( dom.val() );
		}
		region_name.val(getoptionValue(prov) + "\t" + getoptionValue(city) + "\t" + getoptionValue(dist));
		current_region.html(getoptionValue(prov) + "\t" + getoptionValue(city) + "\t" +  getoptionValue(dist));
	}

	this.init = function (){
	var _region = region_id.val();
		if(_region){
			if(_region>10000){
				getData(city, _region.substr(0,2), _region.substr(0,4));
				getData(dist, _region.substr(0,4), _region);
			}else if(region_id.value>100){
				getData(city, _region.substr(0,2), _region);
				getData(dist, _region.substr(0,4));
			}
			getData(dist, _region.substr(0,4), _region);
			prov.val(_region.substr(0,2));
		};	
		
		prov.on('change', function(e){ 
			getData(city, prov.val(), null, function(){ getData(dist, city.val()); });
		});
		/**/
		city.on('change', function(e){ 
			getData(dist, city.val());
		});
		
		dist.on('change', function(e){ 
			var tt = prov.val(); //         
			add_value(dist);
		});
		
		/*
		
		Event.add(prov,'change',function(){
			getData(city, prov.value, null,function(){getData(dist, city.value)});
		});
		
		Event.add(city,'change',function(){
			getData(dist, city.value);
		})
		
		Event.add(dist,'change',function(){
			var tt = prov.value; //         
			add_value(dist);
		})
		*/
	};
}

//ymall_region = new region_selector(json);
//ymall_region.init();