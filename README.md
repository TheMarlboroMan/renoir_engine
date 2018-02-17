# renoir_engine

This is the "development" version: it should not be used with legacy applications.

I'd like to split the project in different concerns...

??database => perhaps this dissapears, as PDO already does everything this did.
	database_query
		consulta_mysql.class.php
	database_query_exception
		excepcion_consulta_mysql.exception.php

orm
	database_entity
		contenido_bbdd.class.php
	??database_entity_dynamic	=> 	This is a very contrived solution.
		contenido_bbdd_dinamico.class.php
	database_entity_sql
		base_textos_sql.abstract.class.php
	??entity_contract		=>	No need, I guess...
		contrato_bbdd.interface.php
	m_to_n_relationship
		abstraccion_relacion_m_n.class.php
	relationship
		relaciones.abstract.class.php
	crud
		comunicacion_bbdd.class.php
	??manejador_propiedades.abstract.class.php
		likely to dissapear.

files
	file_abstraction
		abstraccion_archivo.class.php
	file_exception
		excepcion_ficheros.exception.php

email
	base_email
		email_base.class.php

tools
	ini_config.class.php
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
