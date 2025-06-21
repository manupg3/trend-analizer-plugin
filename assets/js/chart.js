(function(){
  function PieChart(ctx, data){
    var canvas = ctx.canvas;
    var width  = canvas.parentNode.clientWidth || canvas.width || 300;
    var height = width;
    canvas.width  = width;
    canvas.height = height;
    var total  = 0;
    if(!data || !data.datasets || !data.datasets[0]) return;
    var values = data.datasets[0].data;
    for(var i=0;i<values.length;i++) total += values[i];
    var start = -Math.PI/2;
    var colors = ['#4dc9f6','#f67019','#f53794','#537bc4','#acc236','#166a8f','#00a950','#58595b','#8549ba'];
    for(var i=0;i<values.length;i++){
      var val = values[i];
      var angle = (val/total)*Math.PI*2;
      ctx.beginPath();
      ctx.moveTo(width/2,height/2);
      ctx.arc(width/2,height/2,width/2,start,start+angle);
      ctx.closePath();
      ctx.fillStyle = colors[i%colors.length];
      ctx.fill();
      start += angle;
    }
  }

  window.Chart = function(ctx, config){
    if(!ctx || !config) return;
    if(config.type === 'pie'){
      PieChart(ctx, config.data);
    } else {
      console.warn('Mini Chart only supports pie charts.');
    }
  };
})();
