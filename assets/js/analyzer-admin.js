jQuery(document).ready(function($) {
  function t(k){ return (wta_i18n.strings && wta_i18n.strings[k]) || k; }
  function esc(s){
    return String(s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }
  var resultDiv = $('#wta-analysis-result');

  function showLoader(t){ t.html('<div class="loader"></div>'); }
  function hideLoader(t){ t.empty(); }

  // Al cargar, traer datos guardados
  showLoader(resultDiv);
  $.post({
    url: wta_ajax_object.ajax_url,
    data:{ action:'wta_get_analysis_data', nonce:wta_ajax_object.get_nonce },
    success(resp){
      hideLoader(resultDiv);
      if(resp.success){
        generateAnalysisContent(resp.data, resultDiv);
      } else {
        resultDiv.html('<p>'+ esc(resp.data||t('click_analyze')) +'</p>');
      }
    },
    error(){
      hideLoader(resultDiv);
      resultDiv.html('<p class="error">'+t('error_fetch')+'</p>');
    }
  });

  // Botón Analizar Tendencias
  $('#wta-analyze-trends-button').on('click', function(e){
    e.preventDefault();
    showLoader(resultDiv);
    $.post({
      url: wta_ajax_object.ajax_url,
      data:{ action:'wta_analyze_trends', nonce:wta_ajax_object.nonce },
      success(resp){
          console.log("RESPUESTA", resp);
        hideLoader(resultDiv);
        if(resp.success){
          generateAnalysisContent(resp.data, resultDiv);
        } else {
          resultDiv.html('<p class="error">'+esc(resp.data)+'</p>');
        }
      },
      error(){
        hideLoader(resultDiv);
        resultDiv.html('<p class="error">'+t('error_analysis')+'</p>');
      }
    });
  });

  function generateAnalysisContent(storedData, container){
      
    var data = storedData.data;
    var ts   = storedData.timestamp *1000;
    var ago  = timeAgo(Date.now()-ts);
    var diff = Date.now() - ts;
    var sevenDays = 7 * 24 * 60 * 60 * 1000;
    var totals = storedData.data.totals || { revenue:0, quantity_sold:0, customers:0 };

    
    var $msgId     = 'wta-disable-message';
    var $btn     = $('#wta-analyze-trends-button');
    var wrapper  = $btn.parent('.wta-btn-wrapper');
    var tooltip  = wrapper.find('.wta-tooltip');

   wrapper.siblings('.wta-last-analysis').remove();

function formatMoney(n){ return '$' + n.toLocaleString(undefined, {minimumFractionDigits:2}); }
function formatInt(n){ return n.toLocaleString(); }


var cardsHtml = `
  <div class="wta-cards" style="display:flex; gap:16px; margin-bottom:24px;">
    <div class="wta-card" style="flex:1; padding:16px; background:#fff; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05);">
      <h4 style="font-size:14px;color:#71717A;margin:0 0 8px;">${t('total_sales')}</h4>
      <p style="font-size:24px; font-weight:bold; margin:0;color:#000;">${ formatMoney(totals.revenue) }</p>
       <div class="div-icono-card-dashboard card-ventas-totales">  
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-bag h-6 w-6 text-emerald-600"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><path d="M3 6h18"></path><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
      </div>
    </div>
    <div class="wta-card" style="flex:1; padding:16px; background:#fff; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05);">
      <h4 style="font-size:14px;color:#71717A;margin:0 0 8px;">${t('products_sold')}</h4>
      <p style="font-size:24px; font-weight:bold; margin:0;color:#000;">${ formatInt(totals.quantity_sold) }</p>
      <div class="div-icono-card-dashboard card-productos-vendidos">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bar-chart3 h-6 w-6 text-sky-600"><path d="M3 3v18h18"></path><path d="M18 17V9"></path><path d="M13 17V5"></path><path d="M8 17v-3"></path></svg>
      </div>
    </div>
    <div class="wta-card" style="flex:1; padding:16px; background:#fff; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05);">
      <h4 style="font-size:14px;color:#71717A;margin:0 0 8px;">${t('customers')}</h4>
      <p style="font-size:24px; font-weight:bold; margin:0;color:#000;">${ formatInt(totals.customers) }</p>
    <div class="div-icono-card-dashboard card-clientes">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users h-6 w-6 text-violet-600"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
    </div>
    </div>
  </div>
`;

// Insertamos las cards al principio del container
container.html(cardsHtml);



if (diff < sevenDays) {
  // 1) Deshabilitar botón
  $btn.prop('disabled', true)
      .css({ 'pointer-events': 'none', cursor: 'not-allowed' });

  // 2) Crear wrapper y tooltip si aún no existen
  if (!wrapper.length) {
    $btn.wrap('<div class="wta-btn-wrapper" style="position:relative; display:inline-block;"></div>');
    wrapper = $btn.parent();
    wrapper.append(`
      <span class="wta-tooltip" style="
        display: none;
        position: absolute;
        bottom: 120%;
        left: 100%;
        transform: translateX(-50%);
        background: rgba(0,0,0,0.75);
        color: #fff;
        padding: 5px 8px;
        border-radius: 4px;
        font-size: 12px;
        white-space: nowrap;
        z-index: 1000;
      ">
        ${t('need_7_days')}
      </span>
    `);
    tooltip = wrapper.find('.wta-tooltip');
  }

  // 3) Mostrar/ocultar tooltip con hover sobre el wrapper
  wrapper
    .off('mouseenter.tooltip mouseleave.tooltip')
    .on('mouseenter.tooltip', function(){
      tooltip.fadeIn(100);
    })
    .on('mouseleave.tooltip', function(){
      tooltip.fadeOut(100);
    });

  // 4) Añadir mensaje de último análisis
  wrapper.after(`
    <p class="wta-last-analysis" style="
      font-size: 13px;
      color: #004085;
      margin: 10px 0;
      background: #cce5ff;
      display: inline-block;
      padding: 5px 13px;
      border-radius: 3px;
    ">
      <em>${t('last_analysis').replace('%s', ago)}</em>
    </p>
  `);

} else {
  // Restaurar botón habilitado
  $btn.prop('disabled', false)
      .css({ 'pointer-events': '', cursor: '' });

  // Limpiar wrapper, tooltip y mensaje si existían
  if (wrapper.length) {
    wrapper.off('.tooltip');
    wrapper.find('.wta-tooltip').remove();
    wrapper.siblings('.wta-last-analysis').remove();
    $btn.unwrap();
  }
}



    var html = '';
    html += '<div class="wta-tabs">';
    html += '<ul class="wta-tab-links">';
      html += '<li class="active"><a href="#tab1">'+t('tab_top')+'</a></li>';
      html += '<li><a href="#tab2">'+t('tab_least')+'</a></li>';
      html += '<li><a href="#tab3">'+t('tab_trend')+'</a></li>';
      html += '<li><a href="#tab4">'+t('tab_suggestions')+'</a></li>';
      html += '<li><a href="#tab5">'+t('tab_geo')+'</a></li>';
      html += '<li><a class="disabled-button" style="padding-top:7px;" href="#">'+t('tab_comp')+'</a></li>';
      html += '<li><a class="disabled-button" style="padding-top:7px;" href="#">'+t('tab_forecast')+'</a></li>';
    html += '</ul>';
    html += '<div class="wta-tab-content">';
      // TAB 1
      html += '<div id="tab1" class="wta-tab active">';
      if(data.top_selling_products?.length){
        html += '<h3>'+t('top_products')+'</h3>';
        html += '<table class="wta-table"><thead><tr><th>'+t('table_name')+'</th><th>'+t('table_qty')+'</th><th>'+t('table_rev')+'</th><th>'+t('table_avg')+'</th></tr></thead><tbody>';
        data.top_selling_products.forEach(function(p){
          html += '<tr><td>'+esc(p.name)+'</td><td>'+p.quantity_sold+'</td><td>$'+p.total_revenue.toFixed(2)+'</td><td>$'+p.average_price.toFixed(2)+'</td></tr>';
        });
        html += '</tbody></table>';
        html += '<div class="wta-chart-resume"><div><canvas id="top-selling-chart"></canvas></div>' +
                '<div class="container-customer-analysis"><h2>'+t('customer_analysis')+'</h2>' +
                '<div class="container-customer-analysis-lis">';
        data.top_selling_products.forEach(function(prod){
          if(prod.customer_analysis){
            html += '<div class="product-customer-analysis"><details>' +
                '<summary>'+t('product_analysis').replace('%s', esc(prod.name))+'</summary>';
         if (prod.customer_analysis.location_distribution) {
                  html += '<h5>'+t('location_analysis')+'</h5>';
                  const locs = prod.customer_analysis.location_distribution;
                
                  // Si viene un array de objetos, montamos una lista
                  if (Array.isArray(locs)) {
                    html += '<ul>';
                    locs.forEach(loc => {
                      html += `<li>${esc(loc.city)}: ${loc.sales}</li>`;
                    });
                    html += '</ul>';
                  }
                  // Si viene un string, lo mostramos tal cual
                  else {
                    html += '<p>' + esc(locs) + '</p>';
                  }
                }

            if(prod.customer_analysis.other_trends){
              html += '<h5>'+t('other_trends')+'</h5><p>'+esc(prod.customer_analysis.other_trends)+'</p>';
            }
            html += '</details></div>';
          }
        });
        html += '</div></div></div>';
      } else {
        html += '<p>'+t('no_top_data')+'</p>';
      }
      html += '</div>';

      // TAB 2
      html += '<div id="tab2" class="wta-tab">';
      if(data.least_selling_products?.length){
        html += '<h3>'+t('least_products')+'</h3>';
        html += '<table class="wta-table"><thead><tr><th>'+t('table_name')+'</th><th>'+t('table_qty')+'</th><th>'+t('table_rev')+'</th><th>'+t('table_avg')+'</th></tr></thead><tbody>';
        data.least_selling_products.forEach(function(p){
          html += '<tr><td>'+esc(p.name)+'</td><td>'+p.quantity_sold+'</td><td>$'+p.total_revenue.toFixed(2)+'</td><td>$'+p.average_price.toFixed(2)+'</td></tr>';
        });
        html += '</tbody></table>';
        html += '<div class="wta-chart-resume"><div><canvas id="least-selling-chart"></canvas></div>' +
                '<div class="container-customer-analysis"><h2>'+t('customer_analysis')+'</h2>' +
                '<div class="container-customer-analysis-lis">';
        data.least_selling_products.forEach(function(prod){
          if(prod.customer_analysis){
            html += '<div class="product-customer-analysis"><details>' +
                '<summary>'+t('product_analysis').replace('%s', esc(prod.name))+'</summary>';
            if(prod.customer_analysis.location_distribution){
              html += '<h5>'+t('location_analysis')+'</h5><p>'+esc(prod.customer_analysis.location_distribution)+'</p>';
            }
            if(prod.customer_analysis.other_trends){
              html += '<h5>'+t('other_trends')+'</h5><p>'+esc(prod.customer_analysis.other_trends)+'</p>';
            }
            html += '</details></div>';
          }
        });
        html += '</div></div></div>';
      } else {
        html += '<p>'+t('no_least_data')+'</p>';
      }
      html += '</div>';

      // TAB 3
      html += '<div id="tab3" class="wta-tab">';
      html += data.trends ? '<h3>'+t('trends_title')+'</h3><p>'+esc(data.trends)+'</p>' : '<p>'+t('no_trends')+'</p>';
      html += '</div>';

      // TAB 4
      html += '<div id="tab4" class="wta-tab">';
      if(data.sugerencias?.length){
        html += '<p class="wta-soon-text"><svg width="64" height="64" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="#004085" stroke-width="1.5"/><path d="M12 17V11" stroke="#004085" stroke-width="1.5" stroke-linecap="round"/><circle cx="11" cy="9" r="1" fill="#004085"/></svg>'+t('suggestions_intro')+'</p>';
        html += '<ul>';
        data.sugerencias.forEach(s=> html+='<li>'+esc(s)+'</li>');
        html += '</ul>';
      } else {
        html += '<p>'+t('no_suggestions')+'</p>';
      }
      html += '</div>';

      // TAB 5
      html += '<div id="tab5" class="wta-tab">';
      if(data.location_distribution?.length){
        html += '<h3>'+t('geo_sales_title')+'</h3><div class="location-cards-container">';
        data.location_distribution.forEach(loc=>{
          html += '<div class="location-card"><div class="location-icon">📍</div><div class="location-info"><h4>'+esc(loc.city)+'</h4><p>'+t('sales_label')+': '+loc.sales+'</p></div></div>';
        });
        html += '</div>';
      } else {
        html += '<p>'+t('no_geo_data')+'</p>';
      }
      html += '</div>';

    html += '</div></div>';
    container.append(html);

    // Pestañas
    $('.wta-tab-links a').on('click', function(e){
      e.preventDefault();
      var sel = $(this).attr('href');
      $('.wta-tab').hide();
      $(sel).show();
      $(this).parent().addClass('active').siblings().removeClass('active');
      if(sel==='##tab1') drawChart('top-selling-chart',   data.top_selling_products, 'quantity_sold');
      if(sel==='##tab2') drawChart('least-selling-chart', data.least_selling_products, 'quantity_sold');
    });
    $('.wta-tab').hide();
    $('.wta-tab.active').show();
    drawChart('top-selling-chart',   data.top_selling_products, 'quantity_sold');
    drawChart('least-selling-chart', data.least_selling_products, 'quantity_sold');
  }

  function timeAgo(ms){
    var sec=Math.floor(ms/1000), min=Math.floor(sec/60),
        hr=Math.floor(min/60), day=Math.floor(hr/24);
    if(day>0) return day+' '+t('day')+(day>1?'s':'');
    if(hr>0)   return hr+' '+t('hour')+(hr>1?'s':'');
    if(min>0)  return min+' '+t('minute')+(min>1?'s':'');
    return t('few_seconds');
  }

  function drawChart(id, list, key){
    if(!list) return;

    if(!window.Chart||!list) return; 
    var ctx = document.getElementById(id);
    if(!ctx) return;
    var labels=list.map(i=>i.name),
        vals  =list.map(i=>i[key]);

    var palette=[
      '#3366CC','#DC3912','#FF9900','#109618','#990099',
      '#3B3EAC','#0099C6','#DD4477','#66AA00','#B82E2E',
      '#316395','#994499','#22AA99','#AAAA11','#6633CC',
      '#E67300','#8B0707','#651067','#329262','#5574A6'
    ];
    var bg=vals.map(function(_,i){ return palette[i % palette.length]; });

    new Chart(ctx.getContext('2d'), {
      type:'pie',
      data:{ labels, datasets:[{data:vals, backgroundColor:bg, borderColor:'#fff', borderWidth:1}] },
      options:{ responsive:true }
    });
  }

  $('#wta-lang-select').on('change', function(){
    console.log('WTA lang select changed to', $(this).val());
    $('#wta-lang-form').submit();
  });
});
