<?php
include("includes/header.php");
?>
<div id="tools">
	<div class="content">
	<a class="button btn-primary" style="padding: 10px; border: 1px solid #ddddff;" href="index.php">Back to Home Page</a>
	
	<h3>Options</h3>
	<!--<p><input type="checkbox" id="mapTrans"> <label for="mapTrans">Dark Map</label><br></p>-->
	<div id="layer-selectors">
	
	
	<h4>Bronze Age</h4>
	<div class="btn-group" role="group" aria-label="Bronze Age">
	<button type="button" id="layer-4" class="btn btn-default findsLayerSelector" data-toggle="button" data-layer-id="4" data-style-colour="cc8f52" aria-pressed="false" autocomplete="off">Early</button>
	<button type="button" id="layer-5" class="btn btn-default findsLayerSelector" data-toggle="button" data-layer-id="5" data-style-colour="996b3d" aria-pressed="false" autocomplete="off">Middle</button>
	<button type="button" id="layer-6" class="btn btn-default findsLayerSelector" data-toggle="button" data-layer-id="6" data-style-colour="664729" aria-pressed="false" autocomplete="off">Late</button>
	</div>
	
	<h4>Iron Age</h4>
	<div class="btn-group" role="group" aria-label="Iron Age">
	<button type="button" id="layer-7" class="btn btn-default findsLayerSelector" data-toggle="button" data-layer-id="7" data-style-colour="52cc52" aria-pressed="false" autocomplete="off">Early</button>
	<button type="button" id="layer-8" class="btn btn-default findsLayerSelector" data-toggle="button" data-layer-id="8" data-style-colour="3d993d" aria-pressed="false" autocomplete="off">Middle</button>
	<button type="button" id="layer-9" class="btn btn-default findsLayerSelector" data-toggle="button" data-layer-id="9" data-style-colour="296629" aria-pressed="false" autocomplete="off">Late</button>
	</div>
	
	<h4>Lithic Ages</h4>
	<div class="btn-group" role="group" aria-label="Lithic Ages">
	<button type="button" id="layer-10" class="btn btn-default findsLayerSelector" data-toggle="button" data-layer-id="10" data-style-colour="FFFFFF" aria-pressed="false" autocomplete="off">Neo-</button>
	<button type="button" id="layer-11" class="btn btn-default findsLayerSelector" data-toggle="button" data-layer-id="11" data-style-colour="dedea0" aria-pressed="false" autocomplete="off">Paleo-</button>
	<button type="button" id="layer-12" class="btn btn-default findsLayerSelector" data-toggle="button" data-layer-id="12" data-style-colour="dfa0df" aria-pressed="false" autocomplete="off">Meso-</button>
	</div>
	
	<h4>Other Periods</h4>
	
	<?php
	$periods_result = mysqli_query($dbi, "SELECT * FROM periods WHERE id = 3 OR id > 12");
	if(mysqli_affected_rows($dbi) > 0) {
	    while($periods_row = mysqli_fetch_assoc($periods_result)) {
				echo'<button type="button" id="layer-'.$periods_row['id'].'" class="btn btn-default findsLayerSelector" data-toggle="button" data-layer-id="'.$periods_row['id'].'" data-style-colour="'.$periods_row['colour'].'" aria-pressed="false" autocomplete="off">'.$periods_row['name']."</button><br>\n";
		  }
	}
	?>
	
	</div>
	<h4>Filter</h4>
	<p><select id="view-select">
		<option value="all" selected>All</option>
		<option value="in_museum">In Museum only</option>
		<option value="displayed">Displayed only</option>
	</select></p>
	
	<h4>Map Type</h4>
	<p><select id="layer-select">
		<option value="Aerial">Aerial</option>
		<option value="AerialWithLabels" selected>Aerial with labels</option>
		<option value="Road">Road</option>
		<option value="collinsBart">Collins Bart</option>
		<option value="ordnanceSurvey">Ordnance Survey</option>
	</select></p>
	</div>
</div>
<div id="map" class="map"></div>

<!-- Modal -->
<div class="modal fade" id="info-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="myModalLabel">Modal title</h4>
      </div>
      <div class="modal-body">
      </div>
      <div class="modal-footer">
        <a id="more-info" class="btn btn-secondary" href="#" role="button">More Information</a>
        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
var styles = [
	'Road',
	'Aerial',
	'AerialWithLabels',
	'collinsBart',
	'ordnanceSurvey'
];

var iconStyle = new ol.style.Style({
	image: new ol.style.Circle({
		radius: 5,
		fill: new ol.style.Fill({
			color: '#0000FF'
		}),
		stroke: new ol.style.Stroke({
			color: '#000000',
			width: 1.25
		})
	})
});

var layers = [];

var i, ii;
for (i = 0, ii = styles.length; i < ii; ++i) {
	layers.push(new ol.layer.Tile({
		visible: false,
		preload: Infinity,
		source: new ol.source.BingMaps({
			key: 'Asc9RtDOB1ZeVsweSk08ykfIpvaL8w_rdEH5PfGcCBnY1BV1_i9gTX6mlpfmmvQt',
			imagerySet: styles[i]
			// use maxZoom 19 to see stretched tiles instead of the BingMaps
			// "no photos at this zoom level" tiles
			// maxZoom: 19
		}),
		opacity: 0.6
	}));
}
var baseLayersCount = styles.length;

var map = new ol.Map({
	layers: layers,
	controls: ol.control.defaults().extend([
		new ol.control.FullScreen()
	]),
	loadTilesWhileInteracting: true,
	target: 'map',
	view: new ol.View({
		projection: 'EPSG:3857',
		center: ol.proj.transform([-0.4324682, 50.854611], 'EPSG:4326', 'EPSG:3857'),
		zoom: 12
	})
});

// Global variables
var activeLayerGroups = [];

$('#layer-select').change(function() {
	var style = $(this).find(':selected').val();
	var i, ii;
	for (i = 0, ii = styles.length; i < ii; ++i) {
		//console.log(layers[i].name);
		layers[i].setVisible(styles[i] == style);
	}
});
$('#layer-select').trigger('change');

$('#view-select').change(function() {
	var view = $(this).find(':selected').val();
	
	var layers = map.getLayers().forEach(function(layer) {
		var title = layer.get("title");
		
		if (typeof title != 'undefined') {
			
			var partsOfStr = title.split('_');
			var period_id = partsOfStr[0];
			
			console.log("Index of " + period_id + " = " + activeLayerGroups.indexOf(parseInt(period_id)));
			
			if(activeLayerGroups.indexOf(parseInt(period_id)) != -1) {
				console.log("Active layer found, " + period_id);
				console.log("View = " + view);
				console.log("Layer Title = "+ title);
				
				if(view == "all") {
					if(title.includes("_normal")) {
						layer.setVisible(true);
					} else if(title.includes("_in_museum")) {
						layer.setVisible(true);
					} else if(title.includes("_displayed")) {
						layer.setVisible(true);
					}
				} else if(view == "in_museum") {
					if(title.includes("_normal")) {
						layer.setVisible(false);
					} else if(title.includes("_in_museum")) {
						layer.setVisible(true);
					} else if(title.includes("_displayed")) {
						layer.setVisible(true);
					}
				} else if(view == "displayed") {
					if(title.includes("_normal")) {
						layer.setVisible(false);
					} else if(title.includes("_in_museum")) {
						layer.setVisible(false);
					} else if(title.includes("_displayed")) {
						layer.setVisible(true);
					}
				}
			}
		}
	});
});

// display info on click
map.on('click', function(evt) {
	var feature = map.forEachFeatureAtPixel(evt.pixel,
	function(feature, layer) {
		return feature;
	});
	if (feature) {
		$.getJSON( "find.php?find_id="+feature.get('id'), function( json ) {
			$('#info-modal .modal-title').html(json.name);
			if(json.description == true) {
				$('#info-modal #more-info').show();
				$('#info-modal #more-info').attr("href", "wiki/item.php?id=" + feature.get('id'))
			} else {
				$('#info-modal #more-info').hide();
			}
				$('#info-modal .modal-body').html(json.summary);
			$('#info-modal').modal('show');
		});
	} else {
		$('#info-modal').modal('hide');
	}
});

$(".findsLayerSelector").click(function() {
	var thisSelector = $(this);
	thisSelector.prop("disabled", true);
	
	var period_id = $(this).data("layer-id");
	var colourHex = $(this).data("style-colour");
	var thisLayer_normal;
	var thisLayer_in_museum;
	var thisLayer_displayed;
	var view = $('#view-select').find(':selected').val();
	var layers = map.getLayers().forEach(function(layer) {
		if(layer.get("title") == period_id + "_normal") {
			thisLayer_normal = layer;
		}
		if(layer.get("title") == period_id + "_in_museum") {
			thisLayer_in_museum = layer;
		}
		if(layer.get("title") == period_id + "_displayed") {
			thisLayer_displayed = layer;
		}
	});
	
	if(!$(this).hasClass('active')) {
		
		if (typeof thisLayer_normal != 'undefined') {
			if(view == "all") {
				thisLayer_normal.setVisible(true);
				thisLayer_in_museum.setVisible(true);
				thisLayer_displayed.setVisible(true);
			} else if(view == "in_museum") {
				thisLayer_normal.setVisible(false);
				thisLayer_in_museum.setVisible(true);
				thisLayer_displayed.setVisible(true);
			} else if(view == "displayed") {
				thisLayer_normal.setVisible(false);
				thisLayer_in_museum.setVisible(false);
				thisLayer_displayed.setVisible(true);
			}
			
		} else {
			// Get data for new layer - normal
			$.getJSON( "period.php?period_id="+period_id+"&mode=normal", function( json ) {
				var jsonCollection = json;
				
				var vectorSource = new ol.source.Vector();
				
				vectorFormat = new ol.format.GeoJSON();
				var readOptions = {featureProjection: 'EPSG:3857'};
				
				vectorSource.addFeatures(vectorFormat.readFeatures(jsonCollection, readOptions));
				
				strokeColour = "000000";
				
				var layerVisibility = true;
				if(view != "all") {
					layerVisibility = false;
				}
				
				var vectorLayer = new ol.layer.Vector({
					title: period_id + "_normal",
					source: vectorSource,
					style: new ol.style.Style({
						image: new ol.style.Circle({
							radius: 4,
							fill: new ol.style.Fill({
								color: '#'+colourHex,
								
							}),
							stroke: new ol.style.Stroke({
								color: '#'+strokeColour,
								width: 0.75
							})
						})
					}),
					visible: layerVisibility
				});
				
				map.addLayer(vectorLayer);
			});
			
			// Get data for new layer - in museum but not on display
			$.getJSON( "period.php?period_id="+period_id+"&mode=in_museum", function( json ) {
				var jsonCollection = json;
				
				var vectorSource = new ol.source.Vector();
				
				vectorFormat = new ol.format.GeoJSON();
				var readOptions = {featureProjection: 'EPSG:3857'};
				
				vectorSource.addFeatures(vectorFormat.readFeatures(jsonCollection, readOptions));
				
				strokeColour = "000000";
				
				var layerVisibility = true;
				if(view == "displayed") {
					layerVisibility = false;
				}
				
				var vectorLayer = new ol.layer.Vector({
					title: period_id + "_in_museum",
					source: vectorSource,
					style: new ol.style.Style({
						image: new ol.style.Circle({
							radius: 6,
							fill: new ol.style.Fill({
								color: '#'+colourHex,
								
							}),
							stroke: new ol.style.Stroke({
								color: '#'+strokeColour,
								width: 0.75
							})
						})
					}),
					visible: layerVisibility
				});
				
				map.addLayer(vectorLayer);
			});
			
			// Get data for new layer - in museum AND on display
			$.getJSON( "period.php?period_id="+period_id+"&mode=displayed", function( json ) {
				var jsonCollection = json;
				
				var vectorSource = new ol.source.Vector();
				
				vectorFormat = new ol.format.GeoJSON();
				var readOptions = {featureProjection: 'EPSG:3857'};
				
				vectorSource.addFeatures(vectorFormat.readFeatures(jsonCollection, readOptions));
				
				strokeColour = "000000";
				
				var vectorLayer = new ol.layer.Vector({
					title: period_id + "_displayed",
					source: vectorSource,
					style: new ol.style.Style({
						image: new ol.style.Circle({
							radius: 8,
							fill: new ol.style.Fill({
								color: '#'+colourHex,
								
							}),
							stroke: new ol.style.Stroke({
								color: '#'+strokeColour,
								width: 1.25
							})
						})
					})
				});
				
				map.addLayer(vectorLayer);
			});
			
			//alert("Layer loaded from DB, enable.");
			
			// Add to active list
			activeLayerGroups.push(period_id);
		}
		
		thisSelector.prop('disabled', false);
		thisSelector.css('background-color', '#'+colourHex);
		
	} else {
		if (typeof thisLayer_normal != 'undefined') {
			thisLayer_normal.setVisible(false);
			thisLayer_in_museum.setVisible(false);
			thisLayer_displayed.setVisible(false);
		}

		// Re-enable button.
		thisSelector.prop('disabled', false);
		thisSelector.css('background-color', '');
		
		// Remove from active list
		var index = activeLayerGroups.indexOf(period_id);
		activeLayerGroups.splice(index, 1);
	}
});

$('#mapTrans').change(function() {
	if($(this).is(':checked')) {
		var i, ii;
		for (i = 0, ii = styles.length; i < ii; ++i) {
			//console.log(layers[i].name);
			layers[i].setOpacity(0.4);
		}
	} else {
		var i, ii;
		for (i = 0, ii = styles.length; i < ii; ++i) {
			//console.log(layers[i].name);
			layers[i].setOpacity(1);
		}
	}
});

$('#layer-select').trigger('change');

</script>
<?php
include("includes/footer.php");
?>