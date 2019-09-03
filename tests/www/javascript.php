<html>

<head>
  <title>Javascript Onload Test</title>
  
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
	  	document.getElementById('onloadEvent').innerHTML='Hello from window.onLoad';
	  }
	)
	
  </script>
  
</head>

<body>
	
	 <h1 id='onloadEvent'>No Javascript</h1>
 
</body>

</html>
