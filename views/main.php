<!DOCTYPE html>
<html>
<head>
	<meta encoding="utf-8" />
	<title>Enchinga</title>
	<link href="resources/handle.php?t=css&f=960,reset,shared" rel="stylesheet" />
</head>
<body>
	<div id="container" class="container_12">
		<div id="main" class="grid_12 alpha omega">
			<h1>Hola!</h1>
			<p>Para empezar, edita <code>main.php</code>. Si quieres urls bonitos, hay que hacer lo siguiente:</p>
			<h2>Apache <code>.htaccess</code></h2>
			<pre>
RewriteEngine On
#para usar urls de CSS/JS limpios tipo: /css/archivo1,archivo2,archivoN.css
RewriteRule ^(css|js)/([\w,]+)(\.\1)?(l*)$ resources/handle.php?t=$1&f=$2&legible=$4 [NC,L]

RewriteCond %{REQUEST_URI} !-d
RewriteCond %{REQUEST_URI} !-f
RewriteCond $1 !^(index\.php) 
RewriteRule ^/?(.*)/?$ /index.php?/$1 [NC,L];
			</pre>
			<h2>Nginx <code>nginx.conf</code></h2>
			<pre>
#para usar urls de CSS/JS limpios tipo: /css/archivo1,archivo2,archivoN.css
server {
	[...]
	rewrite ^/(css|js)/([\w,]+)(\.\1)?(l*)$ /resources/handle.php?t=$1&f=$2&legible=$4 last;

	location / {
		root /Path/to/instalación/;
		if (-e $request_filename) {
			break;
		}
		rewrite ^/?(.*)/?$ /index.php?/$1 last;
	
		index index.php;
	}
}
			</pre>
			
			<p>Un día que no me de huevita, documento cómo usar los drivers de la base de datos, pero ahí va el hint: <br/><? highlight_string('<?php $this->db->nombreDeLaTabla->get(\'campos,a,seleccionar\')->find(array(\'condicion\'=>\'valor\'));?>');?>.</p>
		</div>
	</div>
	<footer>
		<div id="pato">
			<a href="http://partidosurrealista.com">Partido Surrealista Mexicano</a>
		</div>
		<div id="copirait">
			<span>Todos los derechos reservados, Grupo Surrealista S.A. de C.V. 2011</span>
		</div>
	</footer>
</body>