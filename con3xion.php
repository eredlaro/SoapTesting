<?php 

	class Conexion{
		public function conectar(){
			try {
				$conexion = new PDO('mysql:host=45.55.130.73;port=3306;dbname=timfa','abdala','5Ctna31?');
				$conexion->exec('SET CHARACTER SET utf8');
				$conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

				return $conexion;
			} catch (PDOException $e) {
				return "ERROR DE CONEXION". $e->getMessage. $e->getLine.$e;
			}
		}
		public static function conectarlocal(){
			try {
				$conexionl = new PDO('mysql:host=localhost;dbname=timfac','root','elara29');
				$conexionl->exec('SET CHARACTER SET utf8');
				$conexionl->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

				return $conexionl;
			} catch (Exception $e) {
				return "ERROR DE CONEXION". $e->getMessage. $e->getLine;
			}
		}
		
	}