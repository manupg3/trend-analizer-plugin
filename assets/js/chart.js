(function(){
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
