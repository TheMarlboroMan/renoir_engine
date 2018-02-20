Renoir Engine
=============

# About

This is my very own framework (more like... set of tools) for developing PHP applications. This code is actually very old, but I am working very hard on creating a new version.

This is the "development" version: it should not be used with legacy applications. Of course, I don't endorse you using this for your own projects.

# Roadmap.

Coming up next:
	Create the database_entity_sql class... Which actually works as kind of a repository in doctrine, now that I think about it.
		- I'd like to keep it simple.

# Bits and pieces.

I'd like to split the project in different concerns...

??database => perhaps this dissapears, as PDO already does everything this did.
	database_query
		consulta_mysql.class.php
	database_query_exception
		excepcion_consulta_mysql.exception.php

orm
	database_entity_sql
		base_textos_sql.abstract.class.php
	m_to_n_relationship
		abstraccion_relacion_m_n.class.php
	relationship
		relaciones.abstract.class.php

files
	file_abstraction
		abstraccion_archivo.class.php
	file_exception
		excepcion_ficheros.exception.php

email
	base_email
		email_base.class.php

tools
	paginator
		paginacion.class.php
	i16n
		idioma.class.php
	toolkit
		parts of herramientas.class.php

trash
	contenedor_variables.class.php
	herramientas_html.class.php
	herramientas_img.class.php
