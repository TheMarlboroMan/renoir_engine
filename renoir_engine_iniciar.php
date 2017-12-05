<?php
class Constantes_renoir_engine
{
	const RUTA_ENGINE='/opt/lampp/htdocs/renoir_engine/';
//	const RUTA_ENGINE='/www/hosting/caballorenoir.net/renoir_engine/';
};

require(Constantes_renoir_engine::RUTA_ENGINE.'class_motor/contrato_bbdd.interface.php');
require(Constantes_renoir_engine::RUTA_ENGINE.'class_motor/consulta_mysql.class.php');
require(Constantes_renoir_engine::RUTA_ENGINE.'class_motor/comunicacion_bbdd.class.php');
require(Constantes_renoir_engine::RUTA_ENGINE.'class_motor/manejador_propiedades.abstract.class.php');
require(Constantes_renoir_engine::RUTA_ENGINE.'class_motor/contenido_bbdd.class.php');
require(Constantes_renoir_engine::RUTA_ENGINE.'class_motor/contenido_bbdd_dinamico.class.php');
require(Constantes_renoir_engine::RUTA_ENGINE.'class_motor/paginacion.class.php');
require(Constantes_renoir_engine::RUTA_ENGINE.'class_motor/contenedor_variables.class.php');
require(Constantes_renoir_engine::RUTA_ENGINE.'class_motor/base_textos_sql.abstract.class.php');

require(Constantes_renoir_engine::RUTA_ENGINE.'class_motor/herramientas.class.php');
require(Constantes_renoir_engine::RUTA_ENGINE.'class_motor/herramientas_html.class.php');
require(Constantes_renoir_engine::RUTA_ENGINE.'class_motor/herramientas_img.class.php');

require(Constantes_renoir_engine::RUTA_ENGINE.'class_motor/idioma.class.php');
require(Constantes_renoir_engine::RUTA_ENGINE.'class_motor/email_base.class.php');
require(Constantes_renoir_engine::RUTA_ENGINE.'class_motor/abstraccion_archivo.class.php');
require(Constantes_renoir_engine::RUTA_ENGINE.'class_motor/abstraccion_relacion_m_n.class.php');

require(Constantes_renoir_engine::RUTA_ENGINE.'class_motor/excepcion_ficheros.exception.php');
require(Constantes_renoir_engine::RUTA_ENGINE.'class_motor/excepcion_consulta_mysql.exception.php');
?>
