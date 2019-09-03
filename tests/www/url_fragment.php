<html>

<head>
  <title>URL Fragment Javascript Test</title>
  
  <script>
  
  function addLoadEvent(func) {
    var oldonload = window.onload;
	  if (typeof window.onload != 'function') {
	    window.onload = func;
	  } else {
	    window.onload = function() {
	      if (oldonload) {
	        oldonload();
	      }
	      func();
	    }
	  }
	}
	
	addLoadEvent(
	  function() {
	  	document.getElementById('onloadEvent').innerHTML=location.hash;
	  }
	)
	
  </script>
  
</head>

<body>
	
	 <h1 id='onloadEvent'>No Javascript when finding location.hash!</h1>
 
</body>

</html>
