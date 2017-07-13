
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Discussion Visualization</title>
    <meta charset="utf-8" http-equiv="encoding">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel = "stylesheet" type = "text/css" href = "heatstyle.css" />
    <script type="text/javascript" src="https://d3js.org/d3.v3.min.js"></script>
    <script src="http://labratrevenge.com/d3-tip/javascripts/d3.tip.v0.6.3.js"></script>

  </head>

<body>

<div id="chart"></div>


<script>

var margin = { top: 50, right: 0, bottom: 20, left: 150 },
    width = 1000 - margin.left - margin.right,
    height = 800 - margin.top - margin.bottom,
    tileWidth = Math.floor(width/8),
    tileHeight = 100,
    legendElementHeight = tileWidth,
    buckets = 4,
    colors = ["#dbdbdb", "#acdce2", "#5fcad8", "#00bfd8"], 
    legendLabels = ["not mentioned", "low coherence", "medium coherence", "high coherence"]; 

//load data
//TODO: sort by postnum before drawing
d3.csv("heatdata.csv", function(d) {
 return {
      row: +d.row,
      col: +d.col,
      user: d.user,
      postnum: +d.postnum,
      coherence: d.coherence,
      theme: d.theme
    };
})
.get(function(error, rows) {
    console.log(rows);
    drawHeatMap(rows);
  });
 
function drawHeatMap(rows) {
  var svg = d3.select("#chart").append("svg")
  .attr("width", width + margin.left + margin.right)
  .attr("height", height + margin.top + margin.bottom)
  .append("g")
  .attr("transform", "translate(" + margin.left + ","  + margin.top + ")");

  //mouseover tooltip/word cloud
  // var tooltip = svg.selectAll(".cloud")
  //   .data(rows, function(d) { return d.row + ':' + d.col; })
  //   .enter()
  //   .append("div")
  //   .attr("x", function(d) {return d.row * tileWidth;})
  //   .attr("y", function(d) {return d.col * tileHeight;})
  //   .attr("width", function(d) {return tileWidth;})
  //   .attr("height", function(d) {return tileHeight;})
  //   .style("visibility", "hidden")
  //   .style("z-index", "10")
  //   .text(function(d) { return d.postnum;});
  var tip = d3.tip()
    .attr("class", "d3-tip")
    .offset([-10, 0])
    .html(function(d) {return "Posts: " + d.postnum;});

  svg.call(tip);

  var heatmap = svg.selectAll(".heatmap")
  .data(rows, function(d) {
    return d.row + ':' +d.col;
  })
  .enter()
  .append("svg:rect")
  .attr("x", function(d) { return d.row * tileWidth; })
  .attr("y", function(d) { return d.col * tileHeight; })
  .attr("width", function(d) { return tileWidth; })
  .attr("height", function(d) { return tileHeight; })
  .style("fill", function(d) {
    if(d.coherence == "not mentioned") return colors[0];
    else if(d.coherence == "low") return colors[1];
    else if(d.coherence == "medium") return colors[2];
    else return colors[3];
  })
  .on("mouseover", tip.show)
  .on("mouseout", tip.hide);

  var userPic = svg.selectAll(".userPic")
    .data(rows, function(d) {
      return d.row + ':' + d.col;
    })
    .enter()
    .append("circle")
    .attr("cx", function(d) { return -50;})
    .attr("cy", function(d) {return d.col*tileHeight + tileHeight/2;})
    .attr("r", 30)
    .style("fill", "grey")
    .style("shape-rendering", "geometricPrecision");

  var nameLabels = svg.selectAll(".nameLabel")
    .data(rows, function(d) {
      return d.row + ':' + d.col;
    })
    .enter()
    .append("text")
    .attr("x", function(d) { return -50;})
    .attr("y", function(d) { return d.col * tileHeight + tileHeight;})
    .text(function(d) { return d.user;})
    .style("text-anchor", "middle");

  var colorScale = d3.scale.quantile()
              .domain([0, buckets - 1, d3.max(rows, function (d) { return d.value; })])
              .range(colors);

  var legend = svg.selectAll(".legend")
    .data([0].concat(colorScale.quantiles()), function(d) { return d;});

  legend.enter().append("g")
    .attr("class", "legend");

  legend.append("rect")
    .attr("x", function(d) { return tileWidth*5 + 20;})
    .attr("y", function(d, i) {return legendElementHeight * i;} )
    .attr("width", tileWidth/3)
    .attr("height", legendElementHeight )
    .style("fill", function(d, i) { return colors[i]; });

  legend.append("text")
    .attr("class", "legend_label")
    .attr("x", function(d) { return tileWidth*5 + 60; })
    .attr("y", function(d, i) { return legendElementHeight*i + legendElementHeight/2})
    .text(function(d, i) { return legendLabels[i]; });

  var themeLabel = svg.selectAll(".theme")
    .data(rows, function(d) { return d.row + ':' + d.col;})
    .enter()
    .append("text")
    .attr("x", function(d) { return d.row * tileWidth + 10; })
    .attr("y", function(d) { return d.col * tileHeight + tileHeight/2; })
    .text(function(d) { return d.theme;})
    .attr("class", "theme_label");




}
    
</script>

  <button onclick="sortTiles('post_amount')">Sort by post amount</button>
  <button onclick="sortTiles('coherence')">Sort by coherence</button>
</body>
</html>