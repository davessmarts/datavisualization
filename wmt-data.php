<!DOCTYPE html>
<meta charset="utf-8">

<style> /* set the CSS */

body { font: 12px Arial;}		/* set the default text for anything occuring in the of the html */

path { 
	stroke: steelblue;			/* the line colour for paths is steelblue */
	stroke-width: 2;			/* the line width for paths is 2 pixels */
	fill: none;					/* don't fill the area bounded by any path elements */
}

.axis path,
.axis line {
	fill: none;					/* don't fill areas bounded by the axis line */
	stroke: grey;				/* make the axis line grey */
	stroke-width: 1;			/* make the width of the axis lines 1 pixel */
	shape-rendering: crispEdges;/* make the edges sharp */
}

</style>
<body>

<script type="text/javascript" src="d3/d3.v3.js"></script> 	<!-- load the d3.js library -->	
<script type="text/javascript" src="//code.jquery.com/jquery-2.1.0.min.js"></script>
<script type="text/javascript" src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script>


<form name="table">
	<select id="table-select">
		<option value="top-queries">top queries</option>
		<option value="top-pages">top pages</option>
		<option value="latest-backlinks">latest backlinks</option>
		<option value="internal-links">internal links</option>
		<option value="external-links">external links</option>
		<option value="content-keywords">content keywords</option>
	</select>
	<label for="from">From</label>
	<input type="text" id="from" name="from">
	<label for="to">to</label>
	<input type="text" id="to" name="to">
</form>

<form name="graph-table">
	<select id="x-axis">
		<option value="keyword">Keyword</option>
		<option value="something">Something</option>
	</select>
	<select id="y-axis">
		<option value="keyword">Keyword</option>
		<option value="something">Something</option>
	</select>
</form>

<script>

// Set the dimensions of the canvas / graph
var	margin = {top: 30, right: 20, bottom: 30, left: 50},	// sets the width of the margins around the actual graph area
	width = 600 - margin.left - margin.right,				// sets the width of the graph area
	height = 270 - margin.top - margin.bottom;				// sets the height of the graph area

// Parse the date / time
var	parseDate = d3.time.format("%Y-%d-%m").parse;			// pasrses in the date / time in the format specified

// Set the ranges
var	x = d3.time.scale().range([0, width]);					// scales the range of values on the x axis to fit between 0 and 'width'
var	y = d3.scale.linear().range([height, 0]);				// scales the range of values on the y axis to fit between 'height' and 0

// Define the axes
var	xAxis = d3.svg.axis().scale(x)							// defines the x axis function and applies the scale for the x dimension
	.orient("bottom").ticks(5);								// tells what side the ticks are on and how many to put on the axis

var	yAxis = d3.svg.axis().scale(y)							// defines the y axis function and applies the scale for the y dimension
	.orient("left").ticks(5);								// tells what side the ticks are on and how many to put on the axis

// Define the line
var	valueline = d3.svg.line()								// set 'valueline' to be a line
	.x(function(d) { return x(d.date); })					// set the x coordinates for valueline to be the d.date values
	.y(function(d) { return y(d.close); });					// set the y coordinates for valueline to be the d.close values

// Adds the svg canvas
var	svg = d3.select("body")									// Explicitly state where the svg element will go on the web page (the 'body')
	.append("svg")											// Append 'svg' to the html 'body' of the web page
		.attr("width", width + margin.left + margin.right)	// Set the 'width' of the svg element
		.attr("height", height + margin.top + margin.bottom)// Set the 'height' of the svg element
	.append("g")											// Append 'g' to the html 'body' of the web page
		.attr("transform", "translate(" + margin.left + "," + margin.top + ")"); // in a place that is the actual area for the graph

var jsonRes = 	$.ajax({
  type: "POST",
  dataType: "json",
  url: "wmt-get-data.php",
  data: "query=Select " + $('#x-axis').val() + "," + $('#y-axis').val() + "From test." + $('#table-select').val(),
  success: success
});	

//allows for data selection
$(function() {
    $( "#from" ).datepicker({
      defaultDate: "+1w",
      changeMonth: true,
	  dateFormat: "yyyy-dd-mm",
      numberOfMonths: 3,
      onClose: function( selectedDate ) {
        $( "#to" ).datepicker( "option", "minDate", selectedDate );
      }
    });
    $( "#to" ).datepicker({
      defaultDate: "+1w",
      changeMonth: true,
	  dateFormat: "yyyy-dd-mm",
      numberOfMonths: 3,
      onClose: function( selectedDate ) {
        $( "#from" ).datepicker( "option", "maxDate", selectedDate );
      }
    });
  });
//updates select options with proper column information
$( "#table-select" ).change(function() {
	$.ajax({
	Type: "POST",
	data: "query=Show Columns From test." + $('#table-select').val(),
	url: "wmt-get-data.php",
	dataType: "json",
	success: function(data) {
		var options, index, select, option;

		// Get the raw DOM object for the select box
		select = document.getElementById('x-axis');
		select2 = document.getElementById('y-axis');
		// Clear the old options
		select.options.length = 0;
		select2.options.length = 0;
		// Load the new options
		options = $.parseJSON(data); // Or whatever source information you're working with
		for (index = 0; index < options.length; ++index) {
		option = options[index];
		select.options.add(new Option(option.text, option.value));
		}
		for (index = 0; index < options.length; ++index) {
		option = options[index];
		select2.options.add(new Option(option.text, option.value));
		}
	}
	});  
});
		
// Get the data
d3.json(jsonRes, function(error, data) {				// Go to the data folder (in the current directory) and read in the data.tsv file
	data.forEach(function(d) {								// For all the data values carry out the following
		d.date = parseDate(d.date);							// Parse the date from a set format (see parseDate)
		d.close = +d.close;									// makesure d.close is a number, not a string
	});

	// Scale the range of the data
	x.domain(d3.extent(data, function(d) { return d.date; }));		// set the x domain so be as wide as the range of dates we have.
	y.domain([0, d3.max(data, function(d) { return d.close; })]);	// set the y domain to go from 0 to the maximum value of d.close

	// Add the valueline path.
	svg.append("path")										// append the valueline line to the 'path' element
		.attr("class", "line")								// apply the 'line' CSS styles to this path
		.attr("d", valueline(data));						// call the 'valueline' finction to draw the line

	// Add the X Axis
	svg.append("g")											// append the x axis to the 'g' (grouping) element
		.attr("class", "x axis")							// apply the 'axis' CSS styles to this path
		.attr("transform", "translate(0," + height + ")")	// move the drawing point to 0,height
		.call(xAxis);										// call the xAxis function to draw the axis

	// Add the Y Axis
	svg.append("g")											// append the y axis to the 'g' (grouping) element
		.attr("class", "y axis")							// apply the 'axis' CSS styles to this path
		.call(yAxis);			// call the yAxis function to draw the axis

 	
});

</script>
</body>
