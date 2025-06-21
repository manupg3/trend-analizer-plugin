(function(){

  if (window.Chart && !window.Chart.isStub) {
    return; // Chart.js already available
  }

  window.pendingCharts = window.pendingCharts || [];

  if (!window.Chart || window.Chart.isStub) {
    window.Chart = function(ctx, config){
      // Queue chart creation until Chart.js finishes loading
      window.pendingCharts.push([ctx, config]);
    };
    window.Chart.isStub = true;
  }

  var script = document.createElement('script');
  script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
  script.onload = function(){
    console.info('Chart.js loaded');
    // Chart constructor replaced by real one from library
    window.pendingCharts.forEach(function(args){ new window.Chart(args[0], args[1]); });
    window.pendingCharts.length = 0;
  };
  script.onerror = function(){
    console.error('Failed to load Chart.js from CDN');
  };
  document.head.appendChild(script);
  if (!window.Chart) {
    var script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
    script.onload = function(){ console.info('Chart.js loaded'); };
    script.onerror = function(){ console.error('Failed to load Chart.js from CDN'); };
    document.head.appendChild(script);
    window.Chart = function(ctx, config){
      console.warn('Chart.js stub loaded. Full functionality requires Chart.js 3.9.1.');
    };
  }
})();
