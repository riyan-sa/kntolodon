(function(){
  function init(){
    var btn = document.getElementById('btn-logout');
    if(btn){
      btn.addEventListener('click', function(){
        window.location.href = 'index.php?page=login&action=logout';
      });
    }
  }
  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
