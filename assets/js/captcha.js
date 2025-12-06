(function(){
  function refresh(img){
    if(!img) return;
    var src = img.getAttribute('data-src') || img.getAttribute('src') || '';
    var base = src.split('?')[0];
    var eek = Date.now().toString();
    img.setAttribute('src', base + '?r=' + eek);
    img.setAttribute('data-src', base); 
  }

  function init(){
    var img = document.getElementById('captchaImage');
    var btn = document.getElementById('refresh-captcha');
    if(img){
      refresh(img);
      img.addEventListener('click', function(){ refresh(img); });
    }
    if(btn){
      btn.addEventListener('click', function(e){ e.preventDefault(); refresh(img); });
    }
  }

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
