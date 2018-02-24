<?php

/* Old stuff... */

require_once('src/contrato_bbdd.interface.php');
require_once('src/consulta_mysql.class.php');
require_once('src/comunicacion_bbdd.class.php');
require_once('src/manejador_propiedades.abstract.class.php');
require_once('src/contenido_bbdd.class.php');
require_once('src/contenido_bbdd_dinamico.class.php');
require_once('src/paginacion.class.php');
require_once('src/contenedor_variables.class.php');
require_once('src/base_textos_sql.abstract.class.php');

require_once('src/herramientas.class.php');
require_once('src/herramientas_html.class.php');
require_once('src/herramientas_img.class.php');

require_once('src/idioma.class.php');
require_once('src/email_base.class.php');
require_once('src/abstraccion_archivo.class.php');
require_once('src/abstraccion_relacion_m_n.class.php');

require_once('src/excepcion_ficheros.exception.php');
require_once('src/excepcion_consulta_mysql.exception.php');

require_once('src/ini_config.class.php');

/* New stuff... */

require_once("src/orm/autoload.php");
require_once("src/tools/autoload.php");
require_once("src/view/autoload.php");
