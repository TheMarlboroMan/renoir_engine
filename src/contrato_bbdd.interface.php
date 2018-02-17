<?php
/*
Estos son los mínimos que tiene que proporcionar una clase que se adhiera a las
especificaciones que tenemos para este motor. Realmente son todos aspectos
relacionados con el acceso a base de datos.
*/

interface Contrato_bbdd
{
	public function NOMBRE_CLASE();
	public function TABLA();
	public function ID();
	//public function &DICCIONARIO(); //En realidad no forma parte.
	public function ID_INSTANCIA();
	public function MUT_ID($id);
	//public function &CONSULTA();	//Ni esta.
}
?>
